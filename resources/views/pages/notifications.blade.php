@extends('layouts.app')
@section('title', 'Bildirimlerim')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-8 space-y-4">
    <h1 class="text-2xl font-extrabold text-brand-900">Bildirimlerim</h1>
    @forelse($notifications as $notification)
        <article class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
            <h2 class="font-bold text-ink">{{ $notification->subject }} @unless($notification->read_at)<span class="text-xs text-blue-700">Yeni</span>@endunless</h2>
            <p class="whitespace-pre-wrap break-words text-sm text-slate-600">{{ $notification->content }}</p>
            <time class="block text-xs text-slate-400">{{ $notification->sent_at->format('d.m.Y H:i') }}</time>
            @unless($notification->read_at)
                <form method="POST" action="{{ route('student.notifications.read', $notification->id) }}">@csrf<button class="text-sm font-semibold text-brand-700">Okundu işaretle</button></form>
            @endunless
        </article>
    @empty
        <p class="rounded-2xl border bg-white p-6 text-slate-500">Henüz bildirimin yok.</p>
    @endforelse
    {{ $notifications->links() }}
</section>
@endsection
