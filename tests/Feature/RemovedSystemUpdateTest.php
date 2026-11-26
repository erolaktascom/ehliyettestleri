<?php
namespace Tests\Feature;

use App\Models\{AdminRole, User};
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RemovedSystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_page_command_and_permission_are_removed(): void
    {
        $this->assertArrayNotHasKey('system-update', AdminAccess::modules());
        $this->assertArrayNotHasKey('app:update', Artisan::all());
        $this->assertFalse(class_exists(\App\Livewire\Admin\SystemUpdate::class));
        $this->actingAs(User::factory()->create(['is_admin'=>true]))->get('/admin/system-update')->assertNotFound();
        $this->get('/admin')->assertOk()->assertDontSee('Sistem Güncelleme');
        $editor = AdminRole::where('key','editor')->firstOrFail();
        $editor->update(['permissions'=>['questions','system-update','blog-posts']]);
        $migration = require database_path('migrations/2026_11_03_010000_remove_system_update_permissions.php');
        $migration->up();
        $migration->up();
        $this->assertSame(['questions','blog-posts'], $editor->fresh()->permissions);
    }
}
