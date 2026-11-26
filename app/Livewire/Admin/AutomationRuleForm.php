<?php

namespace App\Livewire\Admin;

use App\Models\{AutomationRule, NotificationDelivery};
use App\Services\CampaignMailer;
use App\Support\NotificationAudience;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\{Layout, Locked};
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Otomasyon Kuralı Formu'])]
class AutomationRuleForm extends Component
{
    #[Locked]
    public ?int $ruleId = null;
    public string $name = '';
    public bool $is_active = false;
    public int $wait_hours = 24;
    public string $trigger = 'registered';
    public array $target_groups = ['everyone'];
    public array $delivery_channels = ['in_app'];
    public string $subject = '';
    public string $content = '';

    public function mount(?int $id = null): void
    {
        if (!$id) return;
        $rule = AutomationRule::findOrFail($id);
        $this->ruleId = $rule->id;
        $this->name = $rule->name;
        $this->is_active = $rule->activated_at && $rule->is_active;
        $this->wait_hours = $rule->wait_hours;
        $this->trigger = $rule->trigger ?? 'registered';
        $this->target_groups = $rule->target_groups ?? ['everyone'];
        $this->delivery_channels = $rule->delivery_channels ?? ['in_app'];
        $this->subject = $rule->subject ?? '';
        $this->content = $rule->content ?? '';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', 'is_active' => 'boolean',
            'wait_hours' => 'required|integer|min:'.($this->trigger === 'inactive' ? 1 : 0).'|max:8760',
            'trigger' => 'required|in:'.implode(',', array_keys(AutomationRule::TRIGGERS)),
            'target_groups' => 'required|array|min:1|max:3', 'target_groups.*' => 'required|distinct|in:everyone,members,premium',
            'delivery_channels' => 'required|array|min:1|max:2', 'delivery_channels.*' => 'required|distinct|in:in_app,email',
            'subject' => ($this->is_active ? 'required' : 'nullable').'|string|max:255',
            'content' => ($this->is_active ? 'required' : 'nullable').'|string|max:20000',
        ];
    }

    public function toggleAudience(string $group): void
    {
        if (!array_key_exists($group, NotificationAudience::GROUPS)) return;
        $this->target_groups = $group === 'everyone' ? ['everyone'] : array_values(
            in_array($group, $this->target_groups, true) ? array_diff($this->target_groups, [$group, 'everyone']) : [...array_diff($this->target_groups, ['everyone']), $group]
        );
    }

    public function save()
    {
        $data = $this->validate();
        if ($this->is_active && in_array('email', $this->delivery_channels) && !app(CampaignMailer::class)->settings()) {
            throw ValidationException::withMessages(['delivery_channels' => 'Önce Entegrasyonlar bölümünden SMTP bağlantısını etkinleştirin.']);
        }
        if (in_array('everyone', $data['target_groups'])) $data['target_groups'] = $this->target_groups = ['everyone'];
        DB::transaction(function () use ($data) {
            $rule = $this->ruleId ? AutomationRule::lockForUpdate()->findOrFail($this->ruleId) : new AutomationRule;
            $wasActive = $rule->is_active && $rule->activated_at;
            $rule->fill($data);
            if ($rule->is_active && (!$wasActive || $rule->isDirty('trigger'))) $rule->activated_at = now();
            $rule->save();
            if (!$rule->is_active) $rule->deliveries()->where('status', 'pending')->update(['status' => 'cancelled']);
        });
        session()->flash('success', 'Otomasyon kuralı kaydedildi.');
        if (!$this->ruleId) return redirect()->route('admin.automation-rules');
    }

    public function retryFailed(): void
    {
        if (!$this->ruleId) return;
        if (!app(CampaignMailer::class)->settings()) throw ValidationException::withMessages(['delivery_channels' => 'Önce SMTP bağlantısını etkinleştirin.']);
        DB::transaction(function () {
            $rule = AutomationRule::lockForUpdate()->findOrFail($this->ruleId);
            if (!$rule->is_active || !$rule->activated_at) return;
            $rule->deliveries()->where('status', 'failed')->update(['status' => 'pending', 'error' => null, 'attempted_at' => null]);
        });
        session()->flash('success', 'Gönderimi hiç başlamamış başarısız kayıtlar yeniden sıraya alındı.');
    }

    public function render()
    {
        $rule = $this->ruleId ? AutomationRule::findOrFail($this->ruleId) : null;
        return view('livewire.admin.automation-rule-form', [
            'rule' => $rule, 'audiences' => NotificationAudience::GROUPS,
            'deliveries' => $rule?->deliveries()->with('user')->latest()->limit(50)->get() ?? collect(),
        ]);
    }
}
