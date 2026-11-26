<div class="max-w-5xl space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $role->name }} Bölüm İzinleri</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $role->key === 'editor' ? 'İşaretlediğiniz bölümlerde listeleme, ekleme, düzenleme ve silme işlemlerine izin verilir.' : 'Yönetici tüm bölümlere erişebilir. Bu yetkiler değiştirilemez.' }}</p>
        </div>
        <a href="{{ route('admin.admin-roles') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Rollere Dön</a>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif
    <form wire:submit="save" class="space-y-5">
        @if($role->key === 'editor')
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                <p class="text-sm font-semibold text-blue-900">{{ count($permissions) }} bölüm seçili · Tüm editörler için geçerli</p>
                <div class="flex gap-3 text-sm font-semibold">
                    <button type="button" wire:click="selectAll" class="text-blue-700 hover:underline">Tümünü Seç</button>
                    <button type="button" wire:click="clearPermissions" class="text-slate-600 hover:underline">Seçimi Temizle</button>
                </div>
            </div>
        @endif
        @foreach($groups as $group => $modules)
            <fieldset class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <legend class="px-2 text-sm font-bold text-slate-900">{{ $group }}</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($modules as $module => $label)
                        @php $locked = $role->key === 'admin' || !array_key_exists($module, $editorModules); @endphp
                        <label wire:key="module-{{ $module }}" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 {{ $locked ? 'bg-slate-50 text-slate-500' : 'cursor-pointer hover:border-blue-300 text-slate-800' }}">
                            @if($locked)
                                <input type="checkbox" disabled @checked($role->key === 'admin') class="size-4 rounded border-slate-300">
                            @else
                                <input type="checkbox" wire:model.live="permissions" value="{{ $module }}" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            @endif
                            <span class="text-sm font-medium">{{ $label }}
                                @if($role->key === 'editor' && $locked)
                                    <span class="block text-xs font-normal text-slate-500">Yalnızca Yönetici</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        @if($role->key === 'editor')
            <div class="sticky bottom-0 flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">İzinleri Kaydet</button>
            </div>
        @endif
    </form>
</div>
