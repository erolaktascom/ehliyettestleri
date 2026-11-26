<div>
    <label for="published-at" class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Yayın Tarihi</label>
    <input id="published-at" type="datetime-local" wire:model="published_at" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <p class="text-xs text-slate-500 mt-1">Görünürlüğü Yayın Durumu belirler.</p>
    @error('published_at') <p class="text-xs text-red-600 mt-1">Geçerli bir yayın tarihi girin.</p> @enderror
</div>
