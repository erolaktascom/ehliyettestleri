@extends('layouts.app')
@section('hide-ads', true)
@section('title', 'Şifremi Unuttum - ' . $siteName)
@section('content')
<section class="site-container max-w-lg py-10 sm:py-16">
    <div class="site-card p-6 sm:p-9">
        <h1 class="text-2xl font-extrabold text-ink">Şifremi Unuttum</h1>
        <p class="text-sm text-muted mt-3">Hesabının e-posta adresini yaz. Şifreni yenilemen için bir bağlantı gönderelim.</p>
        @if(session('status'))<p role="status" class="mt-5 rounded-xl bg-emerald-50 text-emerald-800 p-4 text-sm">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div><label for="email" class="block text-sm font-bold mb-2">E-posta Adresi</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                @error('email')<p role="alert" class="text-sm text-red-700 mt-2">{{ $message }}</p>@enderror
            </div>
            <button class="site-button-primary w-full" type="submit"><x-action-label icon="send">Sıfırlama Bağlantısı Gönder</x-action-label></button>
        </form>
        <a href="{{ route('login') }}" class="inline-block text-sm font-semibold text-brand-700 mt-6"><x-action-label icon="arrow-left">Giriş ekranına dön</x-action-label></a>
    </div>
</section>
@endsection
