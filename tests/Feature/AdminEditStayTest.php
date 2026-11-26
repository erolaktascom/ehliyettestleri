<?php
namespace Tests\Feature;

use App\Livewire\Admin;
use App\Models;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEditStayTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_forms_save_twice_without_leaving_or_duplicating_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Models\User::where('is_admin', true)->firstOrFail());
        $rows = [
            [Admin\AdvertisementForm::class, Models\Advertisement::create(['title'=>'Ad', 'device'=>'desktop', 'location'=>'Header']), 'title'],
            [Admin\AdvertisementForm::class, Models\Advertisement::create(['title'=>'Mobile Ad', 'device'=>'mobile', 'location'=>'Header']), 'title'],
            [Admin\AutomationRuleForm::class, Models\AutomationRule::create(['name'=>'Rule', 'channels'=>'E-posta', 'wait_hours'=>24]), 'name'],
            [Admin\BlogCategoryForm::class, Models\BlogCategory::firstOrFail(), 'name'],
            [Admin\ContentCategoryForm::class, Models\LectureCategory::firstOrFail(), 'name'],
            [Admin\NavigationItemForm::class, Models\NavigationItem::where('menu','desktop')->firstOrFail(), 'name'],
            [Admin\NavigationItemForm::class, Models\NavigationItem::where('menu','mobile')->firstOrFail(), 'name'],
            [Admin\NotificationCampaignForm::class, Models\NotificationCampaign::create(['title'=>'Campaign', 'type'=>'Genel Duyuru', 'target'=>'Tüm Üyeler', 'status'=>'Taslak']), 'title'],
            [Admin\PageForm::class, Models\Page::firstOrFail(), 'title'],
            [Admin\PremiumPlanForm::class, Models\PremiumPlan::create(['name'=>'Plan', 'short_code'=>'test-plan', 'duration_days'=>30, 'price_cents'=>10000, 'order'=>1, 'is_published'=>true]), 'name'],
            [Admin\QuestionTopicForm::class, Models\TopicCategory::firstOrFail(), 'name'],
            [Admin\QuestionImportForm::class, Models\QuestionImport::create(['file_name'=>'test.csv', 'user_id'=>auth()->id(), 'status'=>'Tamamlandı']), 'file_name'],
            [Admin\SeoRedirectForm::class, Models\SeoRedirect::create(['old_url'=>'/stay-source', 'new_url'=>'/stay-target', 'type'=>'301', 'status'=>'Pasif']), 'new_url'],
            [Admin\StaffUserForm::class, Models\User::factory()->create(['is_admin'=>true, 'role_name'=>'Editör']), 'name'],
            [Admin\UserForm::class, Models\User::factory()->create(['is_admin'=>false]), 'name'],
            [Admin\TrafficSignCategoryForm::class, Models\TrafficSignCategory::firstOrFail(), 'name'],
            [Admin\TrafficSignForm::class, Models\TrafficSign::firstOrFail(), 'name'],
        ];
        foreach ($rows as [$form, $record, $field]) {
            $count = $record::count();
            $component = Livewire::test($form, ['id'=>$record->id]);
            foreach ([1,2] as $attempt) {
                $value = $field === 'new_url' ? '/saved-target-'.$attempt : 'Kaydedilen '.$attempt;
                $component->set($field, $value)->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet($field, $value);
                $this->assertSame($value, $record->fresh()->getAttribute($field), $form);
                $this->assertSame($count, $record::count(), $form);
                $this->assertSame(1, substr_count($component->html(), 'data-admin-flash="success"'), $form);
            }
        }
        $role = Models\AdminRole::where('key','editor')->firstOrFail();
        $component = Livewire::test(Admin\AdminRoleForm::class,['id'=>$role->id]);
        foreach ([['advertisements'], ['advertisements','pages']] as $permissions) {
            $component->set('permissions',$permissions)->call('save')->assertHasNoErrors()->assertNoRedirect();
            $this->assertEqualsCanonicalizing($permissions, $role->fresh()->permissions);
            $this->assertSame(1, substr_count($component->html(), 'data-admin-flash="success"'));
        }
    }

    public function test_password_fields_are_cleared_and_repeated_save_keeps_saved_password(): void
    {
        $this->actingAs(Models\User::factory()->create(['is_admin'=>true,'role_name'=>'Yönetici']));
        foreach ([Admin\UserForm::class, Admin\StaffUserForm::class] as $form) {
            $user = Models\User::factory()->create(['is_admin'=>true,'role_name'=>'Editör']);
            $component = Livewire::test($form,['id'=>$user->id])->set('password','test-new-password')
                ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet('password','');
            $hash = $user->fresh()->password;
            $this->assertTrue(Hash::check('test-new-password',$hash));
            $component->set('name','Tekrar Kaydet')->call('save')->assertHasNoErrors()->assertNoRedirect();
            $this->assertSame($hash,$user->fresh()->password);
        }
    }
}
