<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminModuleRegistryTest extends TestCase
{
    public function test_panel_routes_and_permission_registry_stay_in_sync(): void
    {
        $registered = array_keys(AdminAccess::modules());
        $routed = [];
        // Authentication and the no-permission notice are infrastructure, not modules.
        $authenticationRoutes = ['admin.login', 'admin.login.submit', 'admin.logout'];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName() ?? '';
            $uri = $route->uri();
            $isPanel = $uri === 'admin' || str_starts_with($uri, 'admin/')
                || str_starts_with($name, 'admin.')
                || str_starts_with($route->getActionName(), 'App\\Livewire\\Admin\\');
            if (!$isPanel || in_array($name, $authenticationRoutes, true)) {
                continue;
            }

            $this->assertStringStartsWith('admin.', $name, "Panel rotası adlandırılmalı: {$uri}");
            $this->assertContains(AdminMiddleware::class, app('router')->gatherRouteMiddleware($route),
                "Panel rotası izin denetimi dışında kalmış: {$name}");
            if ($name === 'admin.access') {
                continue;
            }

            $module = explode('.', $name)[1];
            $this->assertContains($module, $registered,
                "Yeni panel bölümü AdminAccess listesine eklenmemiş: {$name}");
            $routed[] = $module;
        }

        $this->assertEqualsCanonicalizing($registered, array_values(array_unique($routed)),
            'Kaldırılan panel bölümleri AdminAccess izin listesinden de çıkarılmalı.');

        foreach ($registered as $module) {
            $landing = Route::getRoutes()->getByName('admin.'.$module);
            $this->assertNotNull($landing, "Bölümün menü/açılış rotası eksik: {$module}");
            $this->assertContains('GET', $landing->methods());
            $this->assertSame([], $landing->parameterNames(), "Menü rotası parametre istememeli: {$module}");
        }

        $this->assertSame([], array_values(array_diff(AdminAccess::defaultEditorPermissions(),
            array_keys(AdminAccess::editorModules()))), 'Varsayılan editör izinlerinde kaldırılmış bir bölüm var.');
    }
}
