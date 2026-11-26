<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSettingsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_birth_date_cannot_be_in_the_future_and_valid_dates_or_blank_are_saved(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());
        $user = User::factory()->create(['birth_date' => '2000-01-01']);
        $this->actingAs($user);
        $data = ['name' => $user->name, 'email' => $user->email];
        $this->from(route('student.settings.profile'))->put(route('student.settings.profile.update'), $data + ['birth_date' => '2026-11-05'])
            ->assertRedirect(route('student.settings.profile'))->assertSessionHasErrors('birth_date');
        $this->assertSame('2000-01-01', $user->fresh()->birth_date->toDateString());
        $this->put(route('student.settings.profile.update'), $data + ['birth_date' => '2026-11-04'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-04', $user->fresh()->birth_date->toDateString());
        $this->put(route('student.settings.profile.update'), $data + ['birth_date' => ''])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->birth_date);
    }

    public function test_license_selection_preserves_empty_and_legacy_values(): void
    {
        $user = User::factory()->create(['target_license_class' => null]);
        $this->actingAs($user);
        $this->get(route('student.settings.profile'))->assertOk()->assertSee('<option value="" selected>Seçiniz</option>', false);
        $user->update(['target_license_class' => 'A1']);
        $this->get(route('student.settings.profile'))->assertOk()->assertSee('<option value="A1" selected>A1</option>', false);
        $this->put(route('student.settings.profile.update'), ['name' => $user->name, 'email' => $user->email, 'target_license_class' => 'A1'])->assertSessionHasNoErrors();
        $this->assertSame('A1', $user->fresh()->target_license_class);
        $this->put(route('student.settings.profile.update'), ['name' => $user->name, 'email' => $user->email, 'target_license_class' => ''])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->target_license_class);
    }

    public function test_member_dates_are_rendered_in_turkish(): void
    {
        $this->assertSame('tr', config('app.locale'));
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());
        $user = User::factory()->create(['driving_exam_date' => '2026-11-06']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('06 Kasım 2026')->assertDontSee('November');
        $this->assertSame('1 gün önce', now()->subDay()->diffForHumans());
    }
}