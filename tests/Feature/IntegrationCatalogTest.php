<?php

namespace Tests\Feature;

use App\Livewire\Admin\{IntegrationForm, Integrations};
use App\Models\{Integration, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Route};
use Livewire\Livewire;
use Tests\TestCase;

class IntegrationCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Yönetici']));
        Http::preventStrayRequests();
    }

    private function google(): Integration
    {
        return Integration::where('integration_key', 'google_login')->firstOrFail();
    }

    public function test_catalog_is_predefined_google_is_inactive_and_create_is_removed(): void
    {
        $this->admin();
        $this->assertFalse($this->google()->is_active);
        $this->get('/admin/integrations')->assertOk()->assertSee('Google ile Giriş / Kayıt')->assertSee('Pasif')
            ->assertDontSee('Yeni Entegrasyon Ekle')->assertDontSee('wire:click="delete', false)->assertDontSee('toggleActive');
        $this->get('/admin/integrations/create')->assertNotFound();
        $this->assertFalse(Route::has('admin.integrations.create'));
        $this->assertFalse(method_exists(Integrations::class, 'delete'));
        $this->assertFalse(method_exists(Integrations::class, 'toggleActive'));
        $this->get('/admin/integrations/'.$this->google()->id.'/edit')->assertOk()
            ->assertSee('Client ID')->assertSee('Client Secret')->assertSee('Redirect URI')->assertSee('Google Cloud');
        Http::assertNothingSent();
    }

    public function test_settings_are_saved_encrypted_without_connecting_and_secrets_are_not_returned(): void
    {
        $this->admin();
        $record = $this->google();
        $form = Livewire::test(IntegrationForm::class, ['id' => $record->id])
            ->set('fields.client_id', 'example-client-id')->set('fields.client_secret', 'test-only-secret')
            ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet('fields.client_secret', '')
            ->assertSee('Entegrasyon ayarları kaydedildi.');
        $this->assertSame('test-only-secret', $record->fresh()->configuration['client_secret']);
        $stored = DB::table('integrations')->where('id', $record->id)->value('configuration');
        $this->assertStringNotContainsString('test-only-secret', $stored);
        $this->assertStringNotContainsString('example-client-id', $stored);
        $this->assertArrayNotHasKey('configuration', $record->fresh()->toArray());
        $this->get('/admin/integrations/'.$record->id.'/edit')->assertOk()->assertDontSee('test-only-secret')->assertSee('Gizli anahtar kayıtlı.');
        Livewire::test(IntegrationForm::class, ['id' => $record->id])->assertSet('fields.client_secret', '')
            ->set('fields.client_id', 'changed-id')->call('save')->assertHasNoErrors();
        $this->assertSame('test-only-secret', $record->fresh()->configuration['client_secret']);
        $this->assertSame('changed-id', $record->fresh()->configuration['client_id']);
        $this->assertFalse($record->fresh()->is_active);
        Http::assertNothingSent();
    }

    public function test_incomplete_settings_can_be_prepared_but_activation_requires_credentials(): void
    {
        $this->admin();
        $record = $this->google();
        Livewire::test(IntegrationForm::class, ['id' => $record->id])->call('save')->assertHasNoErrors();
        Livewire::test(IntegrationForm::class, ['id' => $record->id])->set('is_active', true)->call('save')->assertHasErrors('fields.client_id');
        $this->assertFalse($record->fresh()->is_active);
        Livewire::test(IntegrationForm::class, ['id' => $record->id])->set('fields.provider', 'custom')
            ->call('save')->assertHasErrors('fields');
        $this->assertSame(count(\App\Support\IntegrationCatalog::all()), Integration::count());
        Http::assertNothingSent();
    }

    public function test_unregistered_legacy_records_cannot_be_configured_as_new_integrations(): void
    {
        $this->admin();
        $legacy = Integration::create(['name' => 'Legacy service', 'provider' => 'arbitrary']);
        $this->get('/admin/integrations')->assertDontSee('Legacy service');
        $this->get('/admin/integrations/'.$legacy->id.'/edit')->assertNotFound();
        $this->assertModelExists($legacy);
    }

    public function test_editor_without_integration_permission_cannot_access_settings_or_replay_save(): void
    {
        $this->admin();
        $admin = auth()->user();
        $record = $this->google();
        $response = $this->get('/admin/integrations/'.$record->id.'/edit')->assertOk();
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $admin->update(['role_name' => 'Editör']);
        $this->get('/admin/integrations')->assertForbidden();
        $this->get('/admin/integrations/'.$record->id.'/edit')->assertForbidden();
        $this->postJson('/livewire/update', ['components' => [[
            'snapshot' => html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'),
            'updates' => ['fields.client_id' => 'unauthorized'],
            'calls' => [['path' => '', 'method' => 'save', 'params' => []]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertNull($record->fresh()->configuration);
    }
}
