<?php

namespace App\Livewire\Admin;

use App\Models\NavigationGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NavigationGroupForm extends Component
{
    #[Locked] public ?int $groupId = null;
    #[Locked] public string $menu = 'footer';
    public string $name = '';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?int $id = null, ?string $menu = null): void {
        $menu ??= 'footer';
        abort_unless(in_array($menu, ['footer', 'apps']), 404);
        $this->menu = $menu;
        if ($id) {
            $group = NavigationGroup::forAdminMenu($menu)->findOrFail($id);
            $this->groupId = $group->id;
            $this->fill($group->only('name', 'order', 'is_active'));
        } else {
            abort_if($menu === 'apps' && NavigationGroup::where('key', 'apps')->exists(), 404);
            $this->order = (NavigationGroup::max('order') ?? 0) + 1;
            if ($menu === 'apps') $this->name = 'Uygulamalar';
        }
    }

    public function save() {
        $data = $this->validate(['name' => 'required|string|max:100', 'order' => 'required|integer|min:0|max:100000', 'is_active' => 'boolean']);
        DB::transaction(function () use ($data) {
            if ($this->groupId) {
                NavigationGroup::forAdminMenu($this->menu)->findOrFail($this->groupId)->update($data);
            } else {
                if ($this->menu === 'apps' && NavigationGroup::where('key', 'apps')->exists()) {
                    throw ValidationException::withMessages(['name' => 'Uygulamalar sütunu zaten var. Mevcut sütunu düzenleyin.']);
                }
                $group = new NavigationGroup($data);
                $group->key = $this->menu === 'apps' ? 'apps' : 'column-'.Str::uuid();
                $group->save();
            }
        });
        session()->flash('success', $this->groupId ? 'Sütun güncellendi.' : 'Yeni sütun eklendi.');
        if (!$this->groupId) return redirect()->route('admin.navigation-items.'.$this->menu);
    }

    public function render() {
        return view('livewire.admin.navigation-group-form')->layout('layouts.admin', ['title' => 'Alt Bilgi Sütunu']);
    }
}
