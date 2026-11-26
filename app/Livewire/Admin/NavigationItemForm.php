<?php

namespace App\Livewire\Admin;

use App\Models\NavigationItem;
use App\Models\NavigationGroup;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Layout, Locked};
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Menü Öğesi Formu'])]
class NavigationItemForm extends Component
{
    #[Locked]
    public ?int $itemId = null;
    #[Locked]
    public string $menu = 'desktop';
    public string $name = '';
    public string $url = '';
    public string $audience = 'Herkes';
    public bool $is_active = true;
    public int $order = 0;
    public ?string $footer_group = null;

    public function mount(?int $id = null, ?string $menu = null): void
    {
        $n = $id ? NavigationItem::findOrFail($id) : null;
        $this->menu = $menu ?? ($n?->menu === 'footer' ? match ($n->footer_group) { null => 'footer-bottom', 'apps' => 'apps', default => 'footer' } : $n?->menu) ?? 'desktop';
        abort_unless(isset(NavigationItem::ADMIN_MENUS[$this->menu]), 404);
        abort_if($n && !NavigationItem::forAdminMenu($this->menu)->whereKey($n->id)->exists(), 404);
        $this->footer_group = NavigationGroup::forAdminMenu($this->menu)->orderBy('order')->orderBy('id')->value('key');
        if ($n) {
            $this->itemId = $n->id;
            $this->name = $n->name;
            $this->url = $n->url;
            $this->audience = ['everyone' => 'Herkes', 'members' => 'Üyeler', 'guests' => 'Misafirler'][$n->audience] ?? $n->audience ?? 'Herkes';
            $this->is_active = $n->is_active;
            $this->order = $n->order;
            $this->footer_group = $n->footer_group;
        } else $this->order = (NavigationItem::forAdminMenu($this->menu)->max('order') ?? 0) + 1;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'url' => [$this->menu === 'apps' ? 'nullable' : 'required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (!NavigationItem::safeUrl($value)) $fail('Site içi /adres veya geçerli bir http/https adresi girin.');
            }],
            'audience' => 'required|in:Herkes,Üyeler,Misafirler',
            'is_active' => 'boolean',
            'order' => 'required|integer|min:0',
            'footer_group' => in_array($this->menu, ['footer', 'apps'])
                ? ['required', Rule::in(NavigationGroup::forAdminMenu($this->menu)->pluck('key')->all())] : ['prohibited'],
        ];
    }

    public function save()
    {
        $validated = $this->validate() + ['menu' => in_array($this->menu, ['footer-bottom', 'apps']) ? 'footer' : $this->menu];
        $validated['footer_group'] = in_array($this->menu, ['footer', 'apps']) ? $this->footer_group : null;
        $validated['url'] = $validated['url'] ?? '';
        if ($this->itemId) {
            NavigationItem::forAdminMenu($this->menu)->findOrFail($this->itemId)->update($validated);
            session()->flash('success', 'Menü bağlantısı güncellendi.');
        } else {
            NavigationItem::create($validated);
            session()->flash('success', 'Yeni menü bağlantısı eklendi.');
        }
        if (!$this->itemId) {
            return redirect()->route('admin.navigation-items.'.$this->menu);
        }
    }

    public function render() { return view('livewire.admin.navigation-item-form', [
        'footerGroups' => NavigationGroup::forAdminMenu($this->menu)->orderBy('order')->orderBy('id')->get(),
    ]); }
}
