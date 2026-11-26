@props(['icon', 'name', 'fallback' => 'book'])
<label for="category-icon" class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori ikonu</label>
<div class="flex items-center gap-3">
    <span class="text-blue-600" aria-hidden="true">
        <x-student-icon :name="\App\Support\StudyCategoryIcon::resolve($icon, $name, $fallback)" class="size-6" />
    </span>
    <select id="category-icon" wire:model.live="icon" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">Otomatik (kategori adına göre)</option>
        @foreach(\App\Support\StudyCategoryIcon::OPTIONS as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
@error('icon') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
