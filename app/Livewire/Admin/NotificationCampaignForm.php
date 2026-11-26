<?php

namespace App\Livewire\Admin;

use App\Models\NotificationCampaign;
use App\Support\NotificationAudience;
use App\Services\CampaignMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Bildirim Kampanyası Formu'])]
class NotificationCampaignForm extends Component
{
    #[Locked]
    public ?int $campaignId = null;
    public string $title = '';
    public string $type = 'Genel Duyuru';
    public array $target_groups = ['everyone'];
    #[Locked]
    public ?string $legacyTarget = null;
    public bool $is_email = false;
    public bool $is_push = false;
    public bool $is_in_app = true;
    public string $status = 'Taslak';
    public ?string $scheduled_at = null;
    #[Locked]
    public ?string $sent_at = null;
    public ?string $content = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $c = NotificationCampaign::findOrFail($id);
            $this->campaignId = $c->id;
            $this->title = $c->title;
            $this->type = $c->type;
            $this->target_groups = $c->target_groups ?? NotificationAudience::legacyGroups($c->target);
            $this->legacyTarget = empty($this->target_groups) ? $c->target : null;
            $this->is_email = (bool) $c->is_email;
            $this->is_push = false;
            $this->is_in_app = $c->is_in_app;
            $this->status = $c->execution_enabled ? $c->status : 'Taslak';
            $this->scheduled_at = $c->scheduled_at ? $c->scheduled_at->format('Y-m-d\TH:i') : null;
            $this->sent_at = $c->sent_at ? $c->sent_at->format('Y-m-d\TH:i') : null;
            $this->content = $c->content;
        }
    }

    public function toggleAudience(string $group): void
    {
        if (!array_key_exists($group, NotificationAudience::GROUPS)) return;
        $this->target_groups = $group === 'everyone' ? ['everyone'] : array_values(
            in_array($group, $this->target_groups, true)
                ? array_diff($this->target_groups, [$group, 'everyone'])
                : [...array_diff($this->target_groups, ['everyone']), $group]
        );
        $this->resetValidation('target_groups');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'target_groups' => 'required|array|min:1|max:3',
            'target_groups.*' => 'required|string|distinct|in:everyone,members,premium',
            'is_email' => 'boolean',
            'is_push' => 'boolean',
            'is_in_app' => 'boolean',
            'status' => 'required|in:Sırada,Planlandı,Taslak',
            'scheduled_at' => $this->status === 'Planlandı' ? 'required|date|after:now' : 'nullable|date',
            'content' => ($this->status === 'Taslak' ? 'nullable' : 'required').'|string|max:20000',
        ];
    }

    public function save()
    {
        $validated = $this->validate();
        if ($this->status !== 'Taslak') {
            if ($this->is_push) throw ValidationException::withMessages(['is_push' => 'Web Push henüz bağlı değil.']);
            if (!$this->is_in_app && !$this->is_email) throw ValidationException::withMessages(['is_in_app' => 'En az bir gönderim kanalı seçin.']);
            if ($this->is_email && !app(CampaignMailer::class)->settings()) throw ValidationException::withMessages(['is_email' => 'Önce Entegrasyonlar bölümünden SMTP bağlantısını etkinleştirin.']);
        }
        if (in_array('everyone', $validated['target_groups'], true)) {
            $validated['target_groups'] = $this->target_groups = ['everyone'];
        }
        $validated['recipient_count'] = NotificationAudience::query($validated['target_groups'])->count();
        $validated['scheduled_at'] = match ($this->status) { 'Sırada' => now(), 'Taslak' => null, default => $validated['scheduled_at'] };
        $validated['execution_enabled'] = $this->status !== 'Taslak';
        $validated['sent_at'] = null;

        if ($this->campaignId) {
            DB::transaction(function () use ($validated) {
                $campaign = NotificationCampaign::lockForUpdate()->findOrFail($this->campaignId);
                if ($campaign->prepared_at) throw ValidationException::withMessages(['status' => 'Gönderimi başlamış kampanya değiştirilemez. Yeni bir kampanya oluşturun.']);
                $campaign->update($validated);
            });
            session()->flash('success', 'Bildirim kampanyası güncellendi.');
            $this->legacyTarget = null;
        } else {
            NotificationCampaign::create($validated);
            session()->flash('success', 'Yeni bildirim oluşturuldu.');
        }

        if (!$this->campaignId) {
            return redirect()->route('admin.notification-campaigns');
        }
    }

    public function cancelPending(): void
    {
        if (!$this->campaignId) return;
        DB::transaction(function () {
            $campaign = NotificationCampaign::lockForUpdate()->findOrFail($this->campaignId);
            if (!in_array($campaign->status, ['Sırada', 'Planlandı', 'Gönderiliyor', 'Hata'])) return;
            $campaign->update(['status' => 'İptal', 'execution_enabled' => false]);
            $campaign->deliveries()->where('status', 'pending')->update(['status' => 'cancelled']);
            $this->status = 'İptal';
        });
        session()->flash('success', 'Bekleyen gönderimler iptal edildi. Gönderimi başlamış iletiler geri alınamaz.');
    }

    public function retryFailed(): void
    {
        if (!$this->campaignId) return;
        if (!app(CampaignMailer::class)->settings()) throw ValidationException::withMessages(['is_email' => 'Önce SMTP bağlantısını etkinleştirin.']);
        DB::transaction(function () {
            $campaign = NotificationCampaign::lockForUpdate()->findOrFail($this->campaignId);
            if (!$campaign->execution_enabled || $campaign->status !== 'Hata') return;
            // Only failures known to have happened BEFORE an SMTP attempt are retryable.
            if ($campaign->deliveries()->where('status', 'failed')->update(['status' => 'pending', 'error' => null, 'attempted_at' => null])) {
                $campaign->update(['status' => 'Gönderiliyor']);
                $this->status = 'Gönderiliyor';
            }
        });
        session()->flash('success', 'Gönderimi hiç başlamamış başarısız kayıtlar yeniden sıraya alındı.');
    }

    public function render()
    {
        $campaign = $this->campaignId ? NotificationCampaign::findOrFail($this->campaignId) : null;
        return view('livewire.admin.notification-campaign-form', [
            'campaign' => $campaign,
            'deliveries' => $campaign?->deliveries()->with('user')->latest()->limit(50)->get() ?? collect(),
            'audiences' => NotificationAudience::GROUPS,
            'recipientCount' => NotificationAudience::query($this->target_groups)->count(),
        ]);
    }
}
