<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Advertisement extends Model
{
    public const DEVICES = ['desktop' => 'Masaüstü Reklamları', 'mobile' => 'Mobil Reklamları'];
    public const AUDIENCES = ['everyone' => 'Herkes', 'members' => 'Üyeler', 'guests' => 'Ziyaretçiler', 'premium' => 'Premium Üyeler'];
    public const SELECTABLE_AUDIENCES = ['everyone' => 'Herkes', 'members' => 'Üyeler', 'guests' => 'Ziyaretçiler'];
    protected $fillable = ['title', 'location', 'device', 'audience', 'is_active', 'code'];
    protected $casts = ['is_active' => 'boolean', 'audience' => 'array'];
    protected $attributes = ['audience' => '["everyone"]'];
    public function audienceLabel(): string {
        return implode(', ', array_map(fn ($key) => self::AUDIENCES[$key] ?? $key, $this->audience ?? []));
    }
    public static function locations(string $device): array {
        return $device === 'mobile' ? ['Header' => 'Üst Kısım (Header)'] : [
            'Header' => 'Üst Kısım (Header)', 'LeftBanner' => 'Sol Banner', 'RightBanner' => 'Sağ Banner'];
    }
    public static function slots(): \Illuminate\Support\Collection {
        if (auth()->user()?->isPremium()) return collect();
        $audience = auth()->user() ? 'members' : 'guests';
        return static::where('is_active', true)
            ->where(fn ($query) => $query->whereJsonContains('audience', 'everyone')->orWhereJsonContains('audience', $audience))
            ->orderByDesc('updated_at')->orderByDesc('id')->get()
            ->filter(fn ($ad) => isset(self::DEVICES[$ad->device]) && isset(self::locations($ad->device)[$ad->location])
                && trim($ad->code ?? '') !== '')
            ->sortBy(fn ($ad) => in_array('everyone', $ad->audience, true) ? 1 : 0)
            ->unique(fn ($ad) => $ad->device.'.'.$ad->location)->keyBy(fn ($ad) => $ad->device.'.'.$ad->location);
    }
}
