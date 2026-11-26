@foreach([
    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
    'error' => 'bg-red-50 border-red-200 text-red-800',
    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
] as $type => $colors)
    @if(session()->has($type))
        <div data-admin-flash="{{ $type }}" role="{{ $type === 'success' ? 'status' : 'alert' }}"
             class="mb-5 p-4 rounded-xl border text-sm font-semibold shadow-sm {{ $colors }}">
            {{ session($type) }}
        </div>
    @endif
@endforeach
