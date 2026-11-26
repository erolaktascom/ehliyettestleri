@extends('layouts.app')
@section('hide-ads', true)
@section('title', 'Şifre Yenile - ' . $siteName)
@section('content')
<section class="site-container max-w-lg py-10 sm:py-16">
    <div class="site-card p-6 sm:p-9">
        <h1 class="text-2xl font-extrabold text-ink">Yeni Şifre Belirle</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div><label for="email" class="block text-sm font-bold mb-2">E-posta Adresi</label>
                <input id="email" name="email" type="email" value="{{ $email }}" autocomplete="email" required readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm">
                @error('email')<p role="alert" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
                @error('token')<p role="alert" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
            </div>
            <div><label for="password" class="block text-sm font-bold mb-2">Yeni Şifre</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <p class="text-xs text-muted mt-1">En az 8 karakter kullan.</p>
                @error('password')<p role="alert" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
            </div>
            <div><label for="password_confirmation" class="block text-sm font-bold mb-2">Yeni Şifre Tekrar</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <button class="site-button-primary w-full" type="submit"><x-action-label icon="lock">Şifremi Yenile</x-action-label></button>
        </form>
        <a href="{{ route('password.request') }}" class="inline-block text-sm font-semibold text-brand-700 mt-6">Yeni sıfırlama bağlantısı iste</a>
    </div>
</section>
@endsection
