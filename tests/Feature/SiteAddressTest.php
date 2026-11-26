<?php
namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\{SiteSetting, User};
use App\Support\{SiteAddress, PageSeo, GoogleLogin};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteAddressTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;
    private string $original;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/ehg-site-address-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/bootstrap/cache', 0700, true);
        $this->original = "APP_NAME=Test\nAPP_URL=\"https://old.example.test\"\nAPP_KEY=\"base64:unchanged\"\nDB_PASSWORD=\"literal-secret\"\nSESSION_SECURE_COOKIE=true\n";
        file_put_contents($this->directory.'/.env', $this->original);
        $this->app->useEnvironmentPath($this->directory);
        $this->app->useBootstrapPath($this->directory.'/bootstrap');
        $this->assertSame(str_replace('\\','/',$this->directory.'/.env'), str_replace('\\','/',$this->app->environmentFilePath()));
        $this->assertSame(str_replace('\\','/',$this->directory.'/bootstrap/cache/config.php'), str_replace('\\','/',$this->app->getCachedConfigPath()));
        config(['app.url'=>'https://old.example.test']);
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        SiteSetting::firstOrCreate(['id'=>1], ['site_name'=>'Original', 'contact_email'=>'owner@example.test']);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            foreach (['.env', '.env.saved', 'bootstrap/cache/config.php'] as $file) {
                if (is_file($this->directory.'/'.$file)) unlink($this->directory.'/'.$file);
            }
            rmdir($this->directory.'/bootstrap/cache');
            rmdir($this->directory.'/bootstrap');
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function test_address_saves_reopens_and_updates_public_urls_without_changing_secrets(): void
    {
        file_put_contents($this->app->getCachedConfigPath(), '<?php return [];');
        Livewire::test(Settings::class)->assertSet('site_url', 'https://old.example.test')
            ->set('site_name','New name')->set('site_url','https://new.example.test/')
            ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet('site_url','https://new.example.test');
        $saved = \Dotenv\Dotenv::parse(file_get_contents($this->directory.'/.env'));
        $this->assertSame('https://new.example.test', $saved['APP_URL']);
        $this->assertSame('base64:unchanged', $saved['APP_KEY']);
        $this->assertSame('literal-secret', $saved['DB_PASSWORD']);
        $this->assertSame('true', $saved['SESSION_SECURE_COOKIE']);
        $this->assertFileDoesNotExist($this->app->getCachedConfigPath());
        $this->assertSame('New name', SiteSetting::first()->site_name);
        Livewire::test(Settings::class)->assertSet('site_url','https://new.example.test');
        $this->assertSame('https://new.example.test', PageSeo::baseUrl(null));
        $this->assertSame('https://new.example.test/auth/google/callback', GoogleLogin::callbackUrl());
        $this->assertSame('https://new.example.test/storage/branding/logo.png', Storage::disk('public')->url('branding/logo.png'));
        $this->get('/robots.txt')->assertOk()->assertSee('https://new.example.test/sitemap_index.xml');
        $this->get('/sitemap_index.xml')->assertOk()->assertSee('https://new.example.test/');
        Livewire::test(Settings::class)->set('site_url','http://127.0.0.1:8000')->call('save')->assertHasNoErrors();
        $this->assertFalse(config('session.secure'));
        $this->assertSame('false', \Dotenv\Dotenv::parse(file_get_contents($this->directory.'/.env'))['SESSION_SECURE_COOKIE']);
    }

    public function test_invalid_addresses_do_not_change_site_settings_or_configuration(): void
    {
        foreach (['javascript:alert(1)', 'https://user:secret@example.test', 'https://example.test/blog',
            'https://example.test?x=1', 'https://example.test#section', "https://example.test\nAPP_DEBUG=true", 'http://remote.example.test'] as $url) {
            Livewire::test(Settings::class)->set('site_name','Should not save')->set('site_url',$url)
                ->call('save')->assertHasErrors('site_url');
            $this->assertSame($this->original, file_get_contents($this->directory.'/.env'));
            $this->assertSame('Original', SiteSetting::first()->site_name);
        }
    }

    public function test_failed_settings_save_restores_the_original_environment(): void
    {
        try {
            SiteAddress::save('https://new.example.test', function () { throw new \RuntimeException('Database failed'); });
            $this->fail('Expected persistence failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database failed', $exception->getMessage());
        }
        $this->assertSame($this->original, file_get_contents($this->directory.'/.env'));
        $this->assertSame('https://old.example.test', config('app.url'));
    }

    public function test_missing_configuration_reports_a_form_error_without_saving_other_changes(): void
    {
        rename($this->directory.'/.env',$this->directory.'/.env.saved');
        Livewire::test(Settings::class)->set('site_name','Should not save')->set('site_url','https://new.example.test')
            ->call('save')->assertHasErrors('site_url');
        $this->assertSame('Original', SiteSetting::first()->site_name);
        $this->assertFileDoesNotExist($this->directory.'/.env');
    }
}
