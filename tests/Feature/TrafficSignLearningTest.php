<?php

namespace Tests\Feature;

use App\Livewire\Admin\TrafficSignForm;
use App\Models\{TrafficSign, TrafficSignCategory, User, UserSignReview, UserSignLearningProgress};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TrafficSignLearningTest extends TestCase
{
    use RefreshDatabase;

    private function sign(array $attributes = []): TrafficSign
    {
        $category = TrafficSignCategory::firstOrCreate(['slug' => 'uyari'], ['name' => 'Uyarı']);
        return TrafficSign::create(array_merge(['traffic_sign_category_id' => $category->id, 'name' => 'Dikkat', 'meaning' => 'Dikkatli ilerleyin.', 'is_published' => true], $attributes));
    }

    public function test_guest_learning_never_writes_reviews_or_member_positions(): void
    {
        $sign = $this->sign();
        $this->get(route('traffic-signs.learn', $sign->category))->assertOk()
            ->assertSee('>İşaretler</span>', false)
            ->assertViewHas('learnerConfig', fn ($c) => !$c['isMember'] && $c['initialStatuses'] === []);
        $this->postJson(route('traffic-signs.save-review', $sign), ['status' => 'known'])->assertUnauthorized();
        $this->postJson(route('traffic-signs.position', $sign->category), ['sign_id' => $sign->id, 'filter' => 'all'])->assertUnauthorized();
        $this->assertDatabaseCount('user_sign_reviews', 0);
        $this->assertDatabaseCount('user_sign_learning_progress', 0);
    }

    public function test_member_review_and_next_position_are_idempotent_and_private(): void
    {
        $sign = $this->sign(); $next = $this->sign(['name' => 'Sonraki']);
        $member = User::factory()->create(); $other = User::factory()->create();
        UserSignReview::create(['user_id' => $other->id, 'traffic_sign_id' => $sign->id, 'status' => 'review']);
        $this->actingAs($member);
        foreach ([1, 2] as $_) $this->postJson(route('traffic-signs.save-review', $sign), ['status' => 'known', 'next_sign_id' => $next->id, 'filter' => 'all'])->assertOk()->assertJson(['success' => true, 'status' => 'known']);
        $this->assertDatabaseCount('user_sign_reviews', 2);
        $this->assertDatabaseHas('user_sign_reviews', ['user_id' => $other->id, 'traffic_sign_id' => $sign->id, 'status' => 'review']);
        $this->get(route('traffic-signs.learn', $sign->category))->assertOk()
            ->assertSee('>İşaretler</span>', false)
            ->assertDontSee('>Öğrenilen</span>', false)
            ->assertViewHas('learnerConfig', fn ($c) => $c['initialSignId'] === $next->id && $c['initialStatuses'] === [$sign->id => 'known']);
        $this->actingAs($other)->get(route('traffic-signs.learn', $sign->category))->assertViewHas('learnerConfig', fn ($c) => $c['initialSignId'] === null && $c['initialStatuses'] === [$sign->id => 'review']);
    }

    public function test_navigation_remembers_filter_and_does_not_mark_a_sign_learned(): void
    {
        $sign = $this->sign(); $member = User::factory()->create(); $this->actingAs($member);
        $this->postJson(route('traffic-signs.position', $sign->category), ['sign_id' => $sign->id, 'filter' => 'unlearned'])->assertOk();
        $this->get(route('traffic-signs.learn', $sign->category))->assertViewHas('learnerConfig', fn ($c) => $c['initialSignId'] === $sign->id && $c['initialFilter'] === 'unlearned');
        $this->assertDatabaseCount('user_sign_reviews', 0);
        $this->postJson(route('traffic-signs.position', $sign->category), ['sign_id' => null, 'filter' => 'review'])->assertOk();
        $this->assertDatabaseCount('user_sign_learning_progress', 1);
    }

    public function test_drafts_and_foreign_category_positions_are_rejected(): void
    {
        $sign = $this->sign(); $draft = $this->sign(['is_published' => false, 'name' => 'Gizli Levha']);
        $category = TrafficSignCategory::create(['name' => 'Diğer', 'slug' => 'diger']);
        $foreign = $this->sign(['traffic_sign_category_id' => $category->id]);
        $this->actingAs(User::factory()->create());
        foreach ([$draft, $foreign] as $invalid) {
            $this->postJson(route('traffic-signs.position', $sign->category), ['sign_id' => $invalid->id, 'filter' => 'all'])->assertUnprocessable()->assertJsonValidationErrors('sign_id');
            $this->postJson(route('traffic-signs.save-review', $sign), ['status' => 'known', 'next_sign_id' => $invalid->id])->assertUnprocessable();
        }
        $this->postJson(route('traffic-signs.save-review', $draft), ['status' => 'known'])->assertNotFound();
        $this->postJson(route('traffic-signs.save-review', $sign), ['status' => 'wrong'])->assertUnprocessable();
        $this->postJson(route('traffic-signs.position', $sign->category), ['sign_id' => $sign->id, 'filter' => 'bad'])->assertUnprocessable();
        $this->get(route('traffic-signs.learn', $sign->category))->assertDontSee('Gizli Levha')->assertViewHas('signs', fn ($rows) => $rows->count() === 1);
        $this->get('/trafik-isaretleri')->assertViewHas('totalSignsCount', 2);
        $this->assertDatabaseCount('user_sign_reviews', 0);
    }

    public function test_unpublishing_excludes_learned_signs_from_portal_and_category_totals(): void
    {
        $sign = $this->sign(); $draft = $this->sign(['is_published' => false]);
        $member = User::factory()->create();
        foreach ([$sign, $draft] as $item) UserSignReview::create(['user_id' => $member->id, 'traffic_sign_id' => $item->id, 'status' => 'known']);
        $this->actingAs($member)->get('/trafik-isaretleri')->assertViewHas('categories', fn ($rows) => $rows->sum('known_count') === 1 && $rows->sum('signs_count') === 1);
        $this->get('/dashboard')->assertOk()->assertViewHas('knownSignsCount', 1)->assertViewHas('totalSignsCount', 1);
        $sign->update(['is_published' => false]);
        $this->get(route('traffic-signs.learn', $sign->category))->assertOk()->assertSee('Bu kategoride henüz yayımlanmış işaret yok.');
    }

    public function test_panel_upload_replacement_and_publication_preserve_edit_screen(): void
    {
        Storage::fake('public');
        $sign = $this->sign(); $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(TrafficSignForm::class, ['id' => $sign->id])
            ->set('imageUpload', UploadedFile::fake()->image('levha.png', 300, 300))
            ->set('is_published', false)->call('save')->assertHasNoErrors()->assertNoRedirect();
        $old = $sign->fresh()->image_path;
        Storage::disk('public')->assertExists($old);
        $this->assertFalse($sign->fresh()->is_published);
        $this->assertStringContainsString('/storage/traffic-signs/', $sign->fresh()->image_url);
        Livewire::test(TrafficSignForm::class, ['id' => $sign->id])
            ->set('imageUpload', UploadedFile::fake()->image('yeni.png', 200, 200))
            ->set('is_published', true)->call('save')->assertHasNoErrors()->assertNoRedirect();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($sign->fresh()->image_path);
        $this->actingAs(User::factory()->create())->get(route('traffic-signs.learn', $sign->category))->assertViewHas('learnerConfig', fn ($c) => $c['signs'][0]['image'] === $sign->fresh()->image_url);
        Livewire::test(TrafficSignForm::class)->assertSet('is_published', false);
    }

    public function test_invalid_upload_does_not_replace_existing_image(): void
    {
        Storage::fake('public'); $sign = $this->sign(['image_path' => 'traffic-signs/existing.png']);
        Storage::disk('public')->put($sign->image_path, 'existing');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(TrafficSignForm::class, ['id' => $sign->id])
            ->set('imageUpload', UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml'))
            ->call('save')->assertHasErrors('imageUpload');
        $this->assertSame('traffic-signs/existing.png', $sign->fresh()->image_path);
        Storage::disk('public')->assertExists('traffic-signs/existing.png');
    }

    public function test_unsafe_and_old_placeholder_media_are_not_rendered_as_sign_images(): void
    {
        foreach (['javascript:alert(1)', '//bad.test/a.png', 'https://raw.githubusercontent.com/twitter/twemoji/master/assets/svg/26a0.svg', '../secret', 'data:image/svg+xml,abc'] as $path) {
            $this->assertNull((new TrafficSign(['image_path' => $path]))->image_url);
        }
        $this->assertSame('https://example.com/sign.png', (new TrafficSign(['image_path' => 'https://example.com/sign.png']))->image_url);
        $this->assertStringEndsWith('/images/sign.png', (new TrafficSign(['image_path' => '/images/sign.png']))->image_url);
    }
}
