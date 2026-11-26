<?php
namespace App\Support\Installation;

use App\Models\{User, SiteSetting, SeoSetting, AdminRole, NavigationItem};
use Illuminate\Support\Facades\{Artisan, DB, Hash};
use RuntimeException;

class Installer
{
    public function __construct(private InstallState $state) {}

    public function install(array $input): void
    {
        $data = InstallState::validate($input);
        $this->state->assertOpen();
        if (in_array(false, $this->state->requirements(), true)) throw new RuntimeException('Önce sunucu gereksinimlerini tamamlayın.');
        $lock = fopen($this->state->path('installation.mutex'), 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Başka bir kurulum işlemi devam ediyor.');
        try {
            $this->state->assertOpen();
            $root = $this->state->root;
            $pdo = MysqlDatabase::connect($data);
            $database = hash('sha256', json_encode([$data['db_host'], $data['db_port'], $data['db_database']]));
            $databaseLock = 'ehg_install_'.substr(hash('sha256', $data['db_database']), 0, 48);
            $query = $pdo->prepare('SELECT GET_LOCK(?, 0)');
            $query->execute([$databaseLock]);
            if ((int)$query->fetchColumn() !== 1) throw new RuntimeException('Bu veritabanında başka bir kurulum devam ediyor.');
            $tables = $pdo->query('SHOW TABLES')->fetchAll();
            $progressPath = $this->state->path('installation-progress.json');
            $progress = is_file($progressPath) ? json_decode(file_get_contents($progressPath), true, flags: JSON_THROW_ON_ERROR) : null;
            if (!$progress && $tables) throw new RuntimeException('Veritabanı boş değil. Mevcut verilerin üzerine kurulum yapılamaz.');
            if ($progress && ($progress['database'] ?? '') !== $database) throw new RuntimeException('Kurulum veritabanı değişmiş.');
            if (!$progress) {
                $progress = ['database'=>$database, 'key'=>'base64:'.base64_encode(random_bytes(32))];
                $this->state->write($progressPath, json_encode($progress, JSON_THROW_ON_ERROR));
            }


            // Bootstrap only after ownership and empty-database checks. No .env or application key is required for the wizard itself.
            $app = require $root.'/bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            config(['app.key'=>$progress['key'], 'app.url'=>$data['site_url'], 'app.env'=>'production', 'app.debug'=>false,
                'database.default'=>'mysql', 'database.connections.mysql'=>MysqlDatabase::configuration($data),
                'session.driver'=>'file', 'cache.default'=>'file', 'queue.default'=>'database']);
            DB::purge('mysql');
            // Historical content/URL conversions are not starter content for a new site.
            $previousFreshInstall = config('installation.fresh', false);
            config(['installation.fresh' => true]);
            try {
                if (Artisan::call('migrate', ['--force'=>true]) !== 0) throw new RuntimeException('Veritabanı hazırlığı tamamlanamadı. Aynı bilgilerle yeniden deneyin.');
            } finally {
                config(['installation.fresh' => $previousFreshInstall]);
            }

            $link = $root.'/public/storage';
            $target = $root.'/storage/app/public';
            if (!file_exists($link)) (new \Illuminate\Filesystem\Filesystem)->link($target, $link);
            if (realpath($link) !== realpath($target)) throw new RuntimeException('Medya bağlantısı oluşturulamadı. Hosting sağlayıcınızdan public/storage → storage/app/public bağlantısını oluşturmasını isteyin.');

            DB::transaction(function () use ($data) {
                $existing = User::first();
                if ($existing) {
                    // Recovery after account commit but before .env publication; never overwrite a password.
                    if (User::count() !== 1 || !$existing->isAdministrator() || $existing->email !== $data['admin_email'] || !Hash::check($data['password'], $existing->password)) {
                        throw new RuntimeException('Kurulum hesabı mevcut. İşlemi aynı yönetici bilgileriyle tamamlayın.');
                    }
                } else {
                    User::withoutEvents(function () use ($data) {
                        $user = new User(['name'=>$data['admin_name'], 'email'=>$data['admin_email'], 'password'=>$data['password'], 'is_admin'=>true, 'role_name'=>'Yönetici']);
                        $user->email_verified_at = now();
                        $user->save();
                        $user->profile_slug = \Illuminate\Support\Str::slug($user->name).'-'.$user->id;
                        $user->save();
                    });
                }
                SiteSetting::withoutEvents(fn()=>SiteSetting::updateOrCreate(['id'=>1], ['site_name'=>$data['site_name'], 'contact_email'=>$data['admin_email']]));
                SeoSetting::firstOrCreate(['id'=>1], ['sitemap_enabled'=>true]);
                AdminRole::where('key', 'editor')->update(['permissions'=>[]]);
                $publicPaths = array_merge(['/', '/deneme', '/konu-testleri', '/konu', '/trafik-isaretleri', '/animasyonlu-sorular', '/blog', '/iletisim'],
                    \App\Models\Page::where('status', 'published')->pluck('slug')->map(fn($slug)=>'/'.$slug)->all());
                NavigationItem::where('menu', 'footer')->where('url', 'like', '/%')->whereNotIn('url', $publicPaths)->update(['is_active'=>false]);
                foreach (['desktop','mobile'] as $menu) {
                    foreach (['Deneme Sınavları'=>'/deneme','Konu Testleri'=>'/konu-testleri','Konu Anlatımı'=>'/konu','Trafik İşaretleri'=>'/trafik-isaretleri','Animasyonlu Sorular'=>'/animasyonlu-sorular','Blog'=>'/blog'] as $name=>$url) {
                        NavigationItem::withoutEvents(fn()=>NavigationItem::firstOrCreate(['menu'=>$menu,'url'=>$url], ['name'=>$name,'audience'=>'Herkes','is_active'=>true,'order'=>NavigationItem::where('menu',$menu)->count()+1]));
                    }
                }
            });
            $this->state->write($root.'/.env', InstallState::environment($data, $progress['key']));
            // .env itself also closes the wizard, even if a host fails to write the secondary lock.
            $this->state->write($this->state->path('installed.json'), json_encode(['installed_at'=>date(DATE_ATOM), 'driver'=>'mysql'], JSON_THROW_ON_ERROR));
            @unlink($progressPath);
        } finally {
            if (isset($pdo, $databaseLock)) {
                try { $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$databaseLock]); } catch (\Throwable) {}
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
