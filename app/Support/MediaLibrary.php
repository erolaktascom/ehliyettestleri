<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaLibrary
{
    // Every persisted content source is checked, including drafts and disabled records.
    // Add new image-bearing modules here when adding upload/content fields.
    private const SOURCES = [
        ['Question', 'Sorular', 'questions', 'question_text'],
        ['BlogPost', 'Blog Yazısı', 'blog-posts', 'title'],
        ['Lecture', 'Konu Anlatımı', 'topics', 'title'],
        ['Page', 'Sayfa', 'pages', 'title'],
        ['TrafficSign', 'Trafik İşareti', 'traffic-signs', 'name'],
        ['SiteSetting', 'Site Ayarları', 'settings', 'site_name'],
        ['User', 'Profil Fotoğrafı', 'users', 'name'],
        ['Advertisement', 'Reklam', 'advertisements', 'title'],
        ['NavigationItem', 'Menü Bağlantısı', 'navigation-items', 'title'],
        ['NavigationGroup', 'Menü Başlığı', 'navigation-items', 'title'],
        ['Exam', 'Deneme Sınavı', 'exams', 'title'],
        ['TopicTest', 'Konu Testi', 'topic-tests', 'title'],
        ['TopicCategory', 'Soru Kategorisi', 'question-topics', 'name'],
        ['LectureCategory', 'Konu Kategorisi', 'content-categories', 'name'],
        ['BlogCategory', 'Blog Kategorisi', 'blog-categories', 'name'],
        ['TrafficSignCategory', 'İşaret Kategorisi', 'traffic-sign-categories', 'name'],
        ['SeoSetting', 'Genel SEO Ayarları', 'seo-settings', 'id'],
        ['SeoRouteSetting', 'Sayfa SEO Ayarları', 'seo-settings', 'id'],
        ['NotificationCampaign', 'Bildirim', 'notification-campaigns', 'title'],
        ['AutomationRule', 'Otomasyon', 'automation-rules', 'name'],
        ['PremiumPlan', 'Premium Paketi', 'premium-plans', 'name'],
    ];

    public function safeFile(string $path): bool
    {
        if (!preg_match('~\A(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(?:png|jpe?g|webp|gif|ico|avif)\z~i', $path)
            || str_contains($path, '..') || str_starts_with($path, 'livewire-tmp/')) return false;
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($path));
        if (!$root || !$file || !is_file($file)) return false;
        if (!str_starts_with(str_replace('\\', '/', $file), rtrim(str_replace('\\', '/', $root), '/').'/')) return false;
        $current = $disk->path('');
        foreach (explode('/', $path) as $part) {
            $current = rtrim($current, '/\\').DIRECTORY_SEPARATOR.$part;
            if (is_link($current)) return false;
        }
        return true;
    }

    public function files(): Collection
    {
        $disk = Storage::disk('public');
        $paths = array_values(array_filter($disk->allFiles(), fn ($path) => $this->safeFile($path)));
        $usage = $this->usageIndex($paths);
        return collect($paths)->map(function ($path) use ($disk, $usage) {
            $modified = $disk->lastModified($path);
            return ['path' => $path, 'url' => $disk->url($path), 'name' => basename($path),
                'bytes' => $disk->size($path), 'modified' => $modified,
                'recent' => $modified > now()->subHour()->timestamp, 'uses' => $usage[$path] ?? []];
        })->sortByDesc('modified')->values();
    }

    public function usageIndex(array $paths): array
    {
        if (!$paths) return [];
        $wanted = array_fill_keys($paths, true);
        $index = [];
        foreach (self::SOURCES as [$class, $label, $module, $titleField]) {
            $model = 'App\\Models\\'.$class;
            foreach ($model::query()->cursor() as $record) {
                // Raw JSON is decoded so escaped slashes and Unicode URLs are recognized.
                $attributes = $record->getAttributes();
                if ($class === 'User') $attributes = array_intersect_key($attributes, array_flip(['avatar_path', 'bio']));
                $found = [];
                foreach ($attributes as $value) {
                    foreach ($this->references($value) as $path) if (isset($wanted[$path])) $found[$path] = true;
                }
                if (!$found) continue;
                $recordModule = $class === 'User' && $record->is_admin ? 'staff-users' : $module;
                $route = 'admin.'.$recordModule.'.edit';
                $parameters = ['id' => $record->getKey()];
                if (in_array($class, ['SiteSetting', 'SeoSetting', 'SeoRouteSetting', 'NavigationGroup'], true)) {
                    $route = 'admin.'.$recordModule;
                    $parameters = [];
                }
                $url = \Illuminate\Support\Facades\Route::has($route) ? route($route, $parameters) : route('admin.'.$recordModule);
                $title = \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $record->getAttribute($titleField)), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 85);
                foreach (array_keys($found) as $path) $index[$path][] = ['label' => $label,
                    'title' => $title ?: '#'.$record->getKey(), 'id' => $record->getKey(), 'route' => $route, 'url' => $url];
            }
        }
        return $index;
    }

    private function references(mixed $value): array
    {
        if (!is_string($value) || $value === '') return [];
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $paths = [];
            array_walk_recursive($decoded, function ($item) use (&$paths) { $paths = array_merge($paths, $this->references($item)); });
            return $paths;
        }
        $value = rawurldecode(html_entity_decode(str_replace('\\/', '/', $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Match full paths, never substrings of similarly named files. Host-independent
        // storage URLs remain protected after a site address change.
        preg_match_all('~(?<![a-zA-Z0-9_.-])(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(?:png|jpe?g|webp|gif|ico|avif)(?![a-zA-Z0-9_.-])~i', $value, $matches);
        return array_map(function ($path) {
            $position = strpos($path, 'storage/');
            return $position === false ? $path : substr($path, $position + 8);
        }, $matches[0]);
    }

    public function deleteUnused(string $path, bool $protectRecent = true): bool
    {
        if (!$this->safeFile($path)) throw ValidationException::withMessages(['media' => 'Dosya bulunamadı veya bu dosya silinemez.']);
        if ($this->usageIndex([$path])) throw ValidationException::withMessages(['media' => 'Bu görsel kullanılıyor. Önce kullanıldığı içerikten kaldırın.']);
        if ($protectRecent && Storage::disk('public')->lastModified($path) > now()->subHour()->timestamp) {
            throw ValidationException::withMessages(['media' => 'Yeni yüklenen görseller, açık düzenleme formlarını korumak için ilk 1 saat silinemez.']);
        }
        if (!Storage::disk('public')->delete($path)) throw ValidationException::withMessages(['media' => 'Dosya silinemedi. Lütfen yeniden deneyin.']);
        return true;
    }

    // Existing replace/remove actions must also respect references in other modules.
    public function cleanup(?string $path): void
    {
        if ($path && $this->safeFile($path) && !$this->usageIndex([$path])) Storage::disk('public')->delete($path);
    }
}
