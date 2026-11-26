@extends('layouts.app')
@section('title', 'Google Hesabını Bağla - ' . $siteName)
@section('content')
<section class="site-container max-w-lg py-10">
    <div class="site-card p-6 sm:p-10">
        <h1 class="text-2xl font-extrabold text-ink">Google hesabınızı bağlayın</h1>
        <p class="mt-3 text-sm text-muted">{{ $email }} adresiyle bir hesabınız var. Google hesabını bağlamak için mevcut {{ $siteName }} şifrenizi girin.</p>
        <form method="POST" action="{{ route('auth.google.link.submit') }}" class="mt-6 space-y-4">
            @csrf
            <label for="link-password" class="block text-sm font-bold">Mevcut Şifreniz</label>
            <input id="link-password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
            @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="site-button-primary w-full"><x-action-label icon="login">Hesabımı Bağla ve Giriş Yap</x-action-label></button>
        </form>
        <a href="{{ route('login') }}" class="mt-5 block text-sm text-brand-700">Normal girişe dön</a>
    </div>
</section>
@endsection
