<?php

namespace App\Livewire\Admin;

use App\Models\NavigationItem;
use App\Models\NavigationGroup;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class NavigationItems extends Component
{
    use WithPagination;
    #[Locked]
    public string $menu = 'desktop';
    public string $search = '';
    #[Locked]
    public ?int $pendingDeleteGroupId = null;
    protected $queryString = ['search' => ['except' => '']];

    public function mount(string $menu = 'desktop'): void
    {
        abort_unless(isset(NavigationItem::ADMIN_MENUS[$menu]), 404);
        $this->menu = $menu;
    }

    public function toggleGroup(int $id): void
    {
        $group = NavigationGroup::forAdminMenu($this->menu)->findOrFail($id);
        $group->update(['is_active' => !$group->is_active]);
        session()->flash('success', 'Sütunun görünürlüğü güncellendi.');
    }

    public function moveGroup(int $id, string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down']), 422);
        DB::transaction(function () use ($id, $direction) {
            $group = NavigationGroup::forAdminMenu($this->menu)->findOrFail($id);
            $groups = NavigationGroup::footerGroups();
            $index = $groups->search(fn ($row) => $row->id === $group->id);
            $target = $index + ($direction === 'up' ? -1 : 1);
            if ($target < 0 || $target >= $groups->count()) return;
            $ordered = $groups->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
            foreach ($ordered as $position => $row) $row->update(['order' => $position + 1]);
        });
        session()->flash('success', 'Sütun sırası güncellendi.');
    }

    public function requestGroupDeletion(int $id): void
    {
        $this->pendingDeleteGroupId = NavigationGroup::forAdminMenu($this->menu)->findOrFail($id)->id;
    }

    public function cancelGroupDeletion(): void { $this->pendingDeleteGroupId = null; }

    public function deleteGroup(): void
    {
        abort_unless($this->pendingDeleteGroupId, 422);
        DB::transaction(function () {
            $group = NavigationGroup::forAdminMenu($this->menu)->findOrFail($this->pendingDeleteGroupId);
            foreach ($group->items()->get() as $item) $item->delete();
            $group->delete();
        });
        $this->pendingDeleteGroupId = null;
        $this->resetPage();
        session()->flash('success', 'Sütun ve içindeki bağlantılar silindi.');
    }

    public function updatingSearch() { $this->resetPage(); }

    public function delete(int $id)
    {
        NavigationItem::forAdminMenu($this->menu)->findOrFail($id)->delete();
        session()->flash('success', 'Menü bağlantısı silindi.');
    }

    public function render()
    {
        $query = NavigationItem::forAdminMenu($this->menu);
        if ($this->search !== '') $query->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('url', 'like', '%'.$this->search.'%'));
        return view('livewire.admin.navigation-items', [
            'items' => $query->orderBy('order')->orderBy('id')->paginate(15),
            'footerGroups' => NavigationGroup::forAdminMenu($this->menu)->withCount('items')->orderBy('order')->orderBy('id')->get(),
            'pendingGroup' => $this->pendingDeleteGroupId ? NavigationGroup::forAdminMenu($this->menu)->withCount('items')->find($this->pendingDeleteGroupId) : null,
        ])
            ->layout('layouts.admin', ['title' => NavigationItem::ADMIN_MENUS[$this->menu]]);
    }
}
