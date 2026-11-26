<?php
namespace Tests\Feature;

use App\Support\Installation\InstallState;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    private ?\PDO $mysqlAdmin = null;
    private ?string $mysqlDatabase = null;
    private ?string $mysqlUser = null;

    private function mysqlInput(): array
    {
        if (!getenv('INSTALL_TEST_MYSQL_HOST')) $this->markTestSkipped('MySQL installer integration: set INSTALL_TEST_MYSQL_HOST, PORT, USERNAME, PASSWORD for an isolated test server.');
        $host = getenv('INSTALL_TEST_MYSQL_HOST');
        $port = getenv('INSTALL_TEST_MYSQL_PORT') ?: '3306';
        $this->mysqlAdmin = new \PDO("mysql:host=$host;port=$port;charset=utf8mb4", getenv('INSTALL_TEST_MYSQL_USERNAME'), getenv('INSTALL_TEST_MYSQL_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->mysqlDatabase = 'ehg_install_test_'.bin2hex(random_bytes(5));
        $this->mysqlUser = 'ehg_test_'.bin2hex(random_bytes(5));
        $password = 'Mysql-Test-9182!$'."'".'"'.'\\suffix';
        $this->mysqlAdmin->exec('CREATE DATABASE `'.$this->mysqlDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $account = $this->mysqlAdmin->quote($this->mysqlUser)."@'127.0.0.1'";
        $this->mysqlAdmin->exec('CREATE USER '.$account.' IDENTIFIED BY '.$this->mysqlAdmin->quote($password));
        $this->mysqlAdmin->exec('GRANT ALL PRIVILEGES ON `'.$this->mysqlDatabase.'`.* TO '.$account);
        return ['db_host'=>$host, 'db_port'=>$port, 'db_database'=>$this->mysqlDatabase, 'db_username'=>$this->mysqlUser, 'db_password'=>$password];
    }

    protected function tearDown(): void
    {
        if ($this->mysqlAdmin) {
            if ($this->mysqlDatabase && preg_match('/^ehg_install_test_[a-f0-9]{10}$/', $this->mysqlDatabase)) $this->mysqlAdmin->exec('DROP DATABASE `'.$this->mysqlDatabase.'`');
            if ($this->mysqlUser && preg_match('/^ehg_test_[a-f0-9]{10}$/', $this->mysqlUser)) $this->mysqlAdmin->exec('DROP USER '.$this->mysqlAdmin->quote($this->mysqlUser)."@'127.0.0.1'");
        }
        parent::tearDown();
    }

    private function sandbox(): string
    {
        $root = storage_path('framework/testing/installer-'.bin2hex(random_bytes(6)));
        $files = new Filesystem;
        foreach (['app','config','routes','resources'] as $dir) $files->copyDirectory(base_path($dir), $root.'/'.$dir);
        $files->copyDirectory(database_path('migrations'), $root.'/database/migrations');
        $files->copyDirectory(public_path('build'), $root.'/public/build');
        foreach (['storage/app/public','storage/framework/sessions','storage/framework/views','storage/framework/cache/data','storage/logs','bootstrap/cache','public'] as $dir) $files->ensureDirectoryExists($root.'/'.$dir);
        foreach (['bootstrap/app.php','bootstrap/providers.php','public/index.php','public/install.php','artisan','composer.json'] as $file) copy(base_path($file),$root.'/'.$file);
        $files->link(base_path('vendor'),$root.'/vendor');
        return $root;
    }

    private function removeSandbox(string $root): void
    {
        $prefix = realpath(storage_path('framework/testing')).DIRECTORY_SEPARATOR;
        $resolved = realpath($root);
        $this->assertStringStartsWith($prefix, $resolved);
        // Remove junctions themselves before recursive cleanup; never traverse the shared vendor tree.
        foreach (['vendor','public/storage'] as $link) {
            if (is_link($root.'/'.$link) || (is_dir($root.'/'.$link) && realpath($root.'/'.$link) !== $root.'/'.$link)) {
                if (PHP_OS_FAMILY === 'Windows') rmdir($root.'/'.$link); else unlink($root.'/'.$link);
            }
        }
        (new Filesystem)->deleteDirectory($root);
    }

    private function environment(): array
    {
        return ['APP_KEY'=>false,'APP_URL'=>false,'SESSION_SECURE_COOKIE'=>false,'APP_ENV'=>'production','APP_DEBUG'=>'false','DB_CONNECTION'=>false,'DB_DATABASE'=>false,'DB_HOST'=>false,'DB_PORT'=>false,'DB_USERNAME'=>false,'DB_PASSWORD'=>false,'DB_URL'=>false,'APP_BASE_PATH'=>false,'CACHE_STORE'=>'file','SESSION_DRIVER'=>'file','APP_CONFIG_CACHE'=>false,'APP_ROUTES_CACHE'=>false];
    }

    public function test_empty_package_installs_into_mysql_over_http_logs_in_and_locks_without_update_module(): void
    {
        $dbInput = $this->mysqlInput();
        $root = $this->sandbox();
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $port = explode(':',stream_socket_get_name($socket,false))[1]; fclose($socket);
        $url = 'http://127.0.0.1:'.$port;
        $server = new Process([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$root.'/public',$root.'/public/index.php'], $root, $this->environment());
        $server->start();
        $http = new \GuzzleHttp\Client(['base_uri'=>$url,'cookies'=>true,'http_errors'=>false,'timeout'=>120,'allow_redirects'=>false]);
        try {
            $response = null;
            for ($i=0;$i<40;$i++) { try { $response=$http->get('/'); break; } catch(\GuzzleHttp\Exception\ConnectException $e) { usleep(100000); } }
            $this->assertNotNull($response, $server->getErrorOutput());
            $html = (string)$response->getBody();
            $this->assertSame(200,$response->getStatusCode(),$html);
            $this->assertStringContainsString('Site bilgileri',$html);
            $this->assertFileDoesNotExist($root.'/.env');
            $this->assertFileDoesNotExist($root.'/storage/app/setup-key.txt');
            $this->assertStringNotContainsString('name="setup_key"',$html);
            $this->assertStringNotContainsString('Kurulum anahtarı',$html);
            $this->assertSame(422,$http->post('/install.php',['form_params'=>[]])->getStatusCode());
            preg_match('/name="csrf" value="([^"]+)"/', $html, $match);
            $data = ['csrf'=>$match[1],'site_name'=>'Kurulum Testi','site_url'=>$url,'admin_name'=>'Site Sahibi','admin_email'=>'owner@example.test','password'=>'Install-Test-9182!','password_confirmation'=>'Install-Test-9182!'] + $dbInput;
            $response=$http->post('/install.php',['form_params'=>$data]);
            $this->assertSame(200,$response->getStatusCode(),(string)$response->getBody().' '.$server->getErrorOutput());
            $this->assertFileExists($root.'/.env');
            $this->assertFileExists($root.'/storage/app/installed.json');
            $this->assertFileDoesNotExist($root.'/storage/app/setup-key.txt');
            $pdo = \App\Support\Installation\MysqlDatabase::connect($dbInput);
            $this->assertFileDoesNotExist($root.'/database/database.sqlite');
            $this->assertSame('mysql', \Dotenv\Dotenv::parse(file_get_contents($root.'/.env'))['DB_CONNECTION']);
            $this->assertSame($dbInput['db_password'], \Dotenv\Dotenv::parse(file_get_contents($root.'/.env'))['DB_PASSWORD']);
            $user = $pdo->query('SELECT * FROM users')->fetchAll(\PDO::FETCH_ASSOC);
            $this->assertCount(1,$user);
            $this->assertSame('owner@example.test',$user[0]['email']);
            $this->assertTrue(password_verify($data['password'],$user[0]['password']));
            $this->assertSame(0,(int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn());
            $this->assertSame(0,(int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn());
            $this->assertSame(0,(int)$pdo->query('SELECT COUNT(*) FROM seo_redirects')->fetchColumn());
            $this->assertSame(0,(int)$pdo->query("SELECT COUNT(*) FROM navigation_items WHERE name='Sıkça Sorulan Sorular' AND is_active=1")->fetchColumn());
            $this->assertSame('[]',$pdo->query("SELECT permissions FROM admin_roles WHERE `key`='editor'")->fetchColumn());
            $this->assertSame(404,$http->get('/install.php')->getStatusCode());
            foreach (['/', '/blog', '/deneme', '/konu-testleri', '/konu', '/trafik-isaretleri', '/animasyonlu-sorular'] as $path) {
                $front = $http->get($path); $this->assertSame(200, $front->getStatusCode(), $path.' '.(string)$front->getBody());
            }
            $response=$http->get('/admin/login');
            $this->assertSame(200,$response->getStatusCode(),(string)$response->getBody());
            preg_match('/name="_token" value="([^"]+)"/', (string)$response->getBody(), $match);
            $response=$http->post('/admin/login',['form_params'=>['_token'=>$match[1],'email'=>$data['admin_email'],'password'=>$data['password']]]);
            $this->assertSame(302,$response->getStatusCode());
            $this->assertStringEndsWith('/admin',$response->getHeaderLine('Location'));
            $settingsResponse = $http->get('/admin/settings');
            $this->assertSame(200, $settingsResponse->getStatusCode());
            $settingsHtml = (string) $settingsResponse->getBody();
            $this->assertStringContainsString('Site Adresi', $settingsHtml);
            preg_match('/wire:snapshot="([^"]+)"/', $settingsHtml, $snapshotMatch);
            $snapshot = json_decode(html_entity_decode($snapshotMatch[1], ENT_QUOTES, 'UTF-8'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($url, $snapshot['data']['site_url']);
            $this->assertSame(404,$http->get('/admin/system-update')->getStatusCode());
            unset($pdo);
        } finally { $server->stop(); $this->removeSandbox($root); }
    }

    public function test_https_is_required_outside_an_actual_localhost_request(): void
    {
        $this->assertTrue(InstallState::secureRequest(['HTTPS'=>'on','HTTP_HOST'=>'site.test','REMOTE_ADDR'=>'203.0.113.10']));
        $this->assertTrue(InstallState::secureRequest(['HTTP_HOST'=>'127.0.0.1:8000','REMOTE_ADDR'=>'127.0.0.1']));
        $this->assertFalse(InstallState::secureRequest(['HTTP_HOST'=>'site.test','REMOTE_ADDR'=>'127.0.0.1','HTTP_X_FORWARDED_PROTO'=>'https']));
        $this->assertFalse(InstallState::secureRequest(['HTTP_HOST'=>'localhost','REMOTE_ADDR'=>'203.0.113.10']));
    }
    public function test_environment_values_are_literal_and_existing_configuration_or_lock_closes_setup(): void
    {
        $root=storage_path('framework/testing/installer-state-'.bin2hex(random_bytes(5)));
        mkdir($root.'/storage/app',0777,true); mkdir($root.'/bootstrap/cache',0777,true);
        try {
            $state=new InstallState($root); $state->assertOpen();
            $this->assertFalse($state->closed());
            $this->assertFileDoesNotExist($state->path('setup-key.txt'));
            $env=InstallState::environment(['site_name'=>'Ad "alıntı" ${HOME} \\ test','site_url'=>'https://example.test', 'db_host'=>'localhost','db_port'=>'3306','db_database'=>'site','db_username'=>'owner','db_password'=>'p$word#\\test'],'base64:test');
            $parsed=\Dotenv\Dotenv::parse($env);
            $this->assertSame('Ad "alıntı" ${HOME} \\ test',$parsed['APP_NAME']);
            $this->assertSame('false',$parsed['APP_DEBUG']);
            foreach ([$root.'/.env', $root.'/storage/app/installed.json', $root.'/bootstrap/cache/config.php'] as $lock) {
                file_put_contents($lock,'existing');
                $this->assertTrue($state->closed());
                try { $state->assertOpen(); $this->fail('Closed installation accepted'); }
                catch (\RuntimeException $e) { $this->assertSame('Kurulum kapalı.', $e->getMessage()); }
                unlink($lock);
            }
        } finally { $this->removeSandbox($root); }
    }

    public function test_existing_mysql_contents_are_never_adopted_by_a_new_installation(): void
    {
        $dbInput = $this->mysqlInput();
        $root=$this->sandbox();
        try {
            $state=new InstallState($root); $state->assertOpen();
            $pdo=\App\Support\Installation\MysqlDatabase::connect($dbInput);
            $pdo->exec('CREATE TABLE keep_me (id INTEGER)');
            $pdo->exec('INSERT INTO keep_me VALUES (42)');
            $input=['site_name'=>'Test','site_url'=>'https://example.test','admin_name'=>'Owner','admin_email'=>'owner@example.test','password'=>'Test-Password-123','password_confirmation'=>'Test-Password-123'] + $dbInput;
            try { (new \App\Support\Installation\Installer($state))->install($input); $this->fail('Existing database accepted'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('boş değil',$e->getMessage()); }
            $this->assertSame(42,(int)$pdo->query('SELECT id FROM keep_me')->fetchColumn());
            $this->assertFileDoesNotExist($root.'/.env'); unset($pdo);
        } finally { $this->removeSandbox($root); }
    }

    public function test_mysql_input_rejects_dsn_injection_and_system_databases_and_preserves_password(): void
    {
        $valid = ['site_name'=>'Test','site_url'=>'https://example.test','admin_name'=>'Owner','admin_email'=>'owner@example.test',
            'password'=>'Test-Password-123','password_confirmation'=>'Test-Password-123',
            'db_host'=>'localhost','db_port'=>'3306','db_database'=>'account_site','db_username'=>'account_user','db_password'=>' spaced $secret# '];
        $this->assertSame($valid['db_password'], InstallState::validate($valid)['db_password']);
        foreach (['db_host'=>'localhost;dbname=other','db_port'=>'0','db_database'=>'mysql','db_username'=>''] as $field=>$value) {
            try { InstallState::validate(array_replace($valid, [$field=>$value])); $this->fail('Invalid database input accepted: '.$field); }
            catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
    }

    public function test_wrong_mysql_credentials_are_reported_without_disclosing_secrets_or_creating_tables(): void
    {
        $dbInput = $this->mysqlInput();
        $dbInput['db_password'] = 'wrong-secret-9284';
        try { \App\Support\Installation\MysqlDatabase::connect($dbInput); $this->fail('Invalid password accepted'); }
        catch (\RuntimeException $e) {
            $this->assertStringContainsString('MySQL bağlantısı kurulamadı', $e->getMessage());
            $this->assertStringNotContainsString($dbInput['db_password'], $e->getMessage());
        }
        $this->assertSame([], $this->mysqlAdmin->query('SHOW TABLES FROM `'.$this->mysqlDatabase.'`')->fetchAll());
    }
}
