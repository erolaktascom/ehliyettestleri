<div class="max-w-4xl mx-auto space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-extrabold text-slate-900">Mesaj Detayı</h1>
        <a href="{{ route('admin.contact-messages') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700">Mesajlara Dön</a>
    </div>
    <article class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-5">
            <h2 class="text-lg font-bold text-slate-900">{{ $message->subject }}</h2>
            <p class="mt-2 text-sm text-slate-700">{{ $message->name }} · {{ $message->email }}</p>
            @if($message->phone)
                <p class="mt-1 text-sm text-slate-600">{{ $message->phone }}</p>
            @endif
            <p class="mt-2 text-xs text-slate-500">{{ $message->created_at->format('d.m.Y H:i') }}</p>
        </div>
        <div class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-800">{{ $message->message }}</div>
        <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="inline-flex px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold">E-posta ile Yanıtla</a>
    </article>
</div>
