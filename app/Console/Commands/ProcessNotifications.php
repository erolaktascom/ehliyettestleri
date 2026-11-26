<?php

namespace App\Console\Commands;

use App\Services\NotificationProcessor;
use Illuminate\Console\Command;

class ProcessNotifications extends Command
{
    protected $signature = 'notifications:process {--limit=100 : Bir çalışmada işlenecek en fazla kanal kaydı}';
    protected $description = 'Zamanı gelen kampanyaları ve otomasyon kurallarını işler.';

    public function handle(NotificationProcessor $processor): int
    {
        $processor->run((int) $this->option('limit'));
        $this->info('Bildirim kontrolü tamamlandı.');
        return self::SUCCESS;
    }
}
