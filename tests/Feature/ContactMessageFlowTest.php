<?php
namespace Tests\Feature;

use App\Livewire\ContactForm;
use App\Livewire\Admin\ContactMessages;
use App\Models\{ContactMessage, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageFlowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['name'=>'İletişim Testi', 'email'=>'contact-test@example.com', 'phone'=>'',
            'subject'=>'Form akış kontrolü', 'message'=>'Mesaj gövdesinde arananbenzersizkelime bulunuyor.'];
    }

    public function test_submission_reaches_admin_search_read_filters_detail_and_delete(): void
    {
        Mail::fake();
        Livewire::test(ContactForm::class)->set($this->payload())->call('submit')
            ->assertHasNoErrors()->assertSet('submitted',true)->assertSet('message','')->assertSee('Mesajınız Alındı!');
        $message = ContactMessage::sole();
        $this->assertFalse($message->is_read);
        $this->actingAs(User::factory()->create(['is_admin'=>true,'role_name'=>'Yönetici']));
        $this->get('/admin/contact-messages')->assertOk()->assertSee($message->subject)->assertSee('Okunmadı');
        foreach ([$message->name,$message->email,$message->subject,'arananbenzersizkelime'] as $search) {
            Livewire::test(ContactMessages::class)->set('search',$search)->set('statusFilter','unread')->assertSee($message->subject)
                ->set('statusFilter','read')->assertDontSee($message->subject);
        }
        $this->get('/admin/contact-messages/'.$message->id)->assertOk()->assertSee($message->message)
            ->assertSee('mailto:'.$message->email, false)->assertSee('E-posta ile Yanıtla');
        $this->assertTrue($message->fresh()->is_read);
        Livewire::test(ContactMessages::class)->set('statusFilter','read')->assertSee($message->subject)
            ->set('statusFilter','unread')->assertDontSee($message->subject);
        Livewire::test(ContactMessages::class)->call('delete',$message->id)->assertHasNoErrors()->assertDontSee($message->subject);
        $this->assertModelMissing($message);
        Mail::assertNothingSent();
    }

    public function test_validation_honeypot_and_classic_post(): void
    {
        Livewire::test(ContactForm::class)->set('email','invalid')->set('message','short')->call('submit')
            ->assertHasErrors(['name','email','subject','message'])->assertSet('submitted',false);
        Livewire::test(ContactForm::class)->set($this->payload())->set('website','spam.example')->call('submit')->assertSet('submitted',true);
        $this->assertDatabaseCount('contact_messages',0);
        $this->from('/iletisim')->post('/iletisim',$this->payload()+['website'=>'spam.example'])->assertRedirect('/iletisim');
        $this->assertDatabaseCount('contact_messages',0);
        $this->from('/iletisim')->post('/iletisim',$this->payload())->assertRedirect('/iletisim')->assertSessionHas('success');
        $this->assertFalse(ContactMessage::sole()->is_read);
    }

    public function test_messages_are_private_to_authorized_staff_and_render_as_text(): void
    {
        $message = ContactMessage::create($this->payload()+['is_read'=>false]);
        $message->update(['message'=>'<script>alert("test")</script>']);
        $path='/admin/contact-messages/'.$message->id;
        $this->get($path)->assertRedirect(route('admin.login'));
        foreach ([['is_admin'=>false], ['is_admin'=>true,'role_name'=>'Editör']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))->get($path)->assertForbidden();
            $this->assertFalse($message->fresh()->is_read);
        }
        $this->actingAs(User::factory()->create(['is_admin'=>true,'role_name'=>'Yönetici']))->get($path)
            ->assertOk()->assertSee($message->message)->assertDontSee($message->message,false);
    }

    public function test_correcting_validation_errors_clears_them_before_starting_another_message(): void
    {
        Livewire::test(ContactForm::class)->set('email','invalid')->call('submit')->assertHasErrors('email')
            ->set($this->payload())->call('submit')->assertHasNoErrors()->assertSet('submitted',true)
            ->set('submitted',false)->assertHasNoErrors()->assertSet('message','');
        $this->assertDatabaseCount('contact_messages',1);
    }

    public function test_both_submission_paths_enforce_the_same_validation_boundaries(): void
    {
        $cases = [
            ['name', 'A'], ['name', '   '], ['name', str_repeat('a',101)],
            ['email', 'invalid'], ['email', 'a@'.str_repeat('b',60).'.'.str_repeat('c',60).'.'.str_repeat('d',25).'.x'],
            ['phone', str_repeat('1',31)], ['subject','ab'], ['subject',str_repeat('a',201)],
            ['message','123456789'], ['message',str_repeat('a',3001)],
        ];
        foreach ($cases as [$field, $value]) {
            $payload = array_replace($this->payload(), [$field=>$value]);
            $this->postJson('/iletisim', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
            Livewire::test(ContactForm::class)->set($payload)->call('submit')
                ->assertHasErrors($field)->assertSet('submitted',false);
        }
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_both_paths_accept_boundary_values_create_one_unread_message_and_preserve_panel_access(): void
    {
        $payload = ['name'=>str_repeat('a',100), 'email'=>'a@'.str_repeat('b',60).'.'.str_repeat('c',60).'.'.str_repeat('d',24).'.x',
            'phone'=>str_repeat('1',30), 'subject'=>str_repeat('a',200), 'message'=>str_repeat('a',3000)];
        $this->from('/iletisim')->post('/iletisim', $payload + ['is_read'=>true])->assertRedirect('/iletisim')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_messages',1);
        $classic = ContactMessage::sole();
        $this->assertFalse($classic->is_read);
        Livewire::test(ContactForm::class)->set($payload)->call('submit')->assertHasNoErrors()->assertSet('submitted',true);
        $this->assertDatabaseCount('contact_messages',2);
        foreach (ContactMessage::all() as $message) {
            $this->assertSame($payload, $message->only(array_keys($payload)));
            $this->assertFalse($message->is_read);
        }
        $this->actingAs(User::factory()->create(['is_admin'=>true]))->get('/admin/contact-messages/'.$classic->id)
            ->assertOk()->assertSee($payload['message']);
    }

    public function test_both_paths_trim_fields_normalize_empty_phone_and_share_honeypot_behavior(): void
    {
        foreach (['spam.example','0'] as $website) {
            $payload=$this->payload()+['website'=>$website];
            $this->from('/iletisim')->post('/iletisim',$payload)->assertRedirect('/iletisim')->assertSessionHas('success');
            Livewire::test(ContactForm::class)->set($payload)->call('submit')->assertSet('submitted',true);
        }
        $this->assertDatabaseCount('contact_messages',0);
        $payload=array_map(fn($value)=>'  '.$value.'  ', $this->payload())+['website'=>'   '];
        $this->from('/iletisim')->post('/iletisim',$payload)->assertRedirect('/iletisim')->assertSessionHasNoErrors();
        Livewire::test(ContactForm::class)->set($payload)->call('submit')->assertHasNoErrors();
        $this->assertDatabaseCount('contact_messages',2);
        $expected=array_replace($this->payload(), ['phone'=>null]);
        foreach (ContactMessage::all() as $message) $this->assertSame($expected,$message->only(array_keys($expected)));
    }
}
