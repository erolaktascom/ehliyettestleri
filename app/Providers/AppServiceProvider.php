<?php

namespace App\Providers;


use App\Models\SeoSetting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (array_keys(\App\Support\PanelAudit::MODELS) as $name) {
            $class = 'App\\Models\\'.$name;
            $class::observe(\App\Observers\PanelAuditObserver::class);
        }

        Livewire::addPersistentMiddleware([\App\Http\Middleware\AdminMiddleware::class]);

        View::composer('*', function ($view) {
            $request = request();
            if (!$request->attributes->has('_shared_site_settings')) {
                $request->attributes->set('_shared_site_settings', [
                    'siteSetting' => SiteSetting::first(), 'seoSetting' => SeoSetting::first(),
                ]);
            }
            $shared = $request->attributes->get('_shared_site_settings');
            $view->with($shared)->with('siteName', \App\Support\SiteBrand::name($shared['siteSetting']));
        });
    }
}
