@extends('layouts.app')

@section('title', 'Giriş Yap - ' . $siteName)

@section('content')
<section class="pt-4 sm:pt-5 pb-12 sm:pb-20">
    <div class="site-container max-w-lg">

<div class="site-card p-6 sm:p-10 shadow-xl shadow-slate-900/5">
            <div class="text-center mb-8">
                <span class="grid size-12 place-items-center rounded-2xl bg-brand-700 text-white mx-auto mb-4 shadow-md shadow-brand-700/20">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6.5 16.5h11M7.5 16.5l-1-5.5 2-4h7l2 4-1 5.5M8 11h8M8.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">{{ $siteName }} hesabınıza giriş yapın</h1>
                <p class="text-xs sm:text-sm text-muted mt-1.5">E-posta ve şifrenizle giriş yapın.</p>
            </div>

            @include('partials.google-login')

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">E-posta Adresi</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Şifre</label>
                    <input type="password" id="password" name="password" required
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100">
                </div>

                <div class="flex items-center justify-between text-xs font-semibold">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        Beni hatırla
                    </label>
                    <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">Şifremi unuttum</a>
                </div>

                <button type="submit" class="site-button-primary w-full py-3 text-base shadow-md"><x-action-label icon="login">Giriş Yap</x-action-label></button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-muted">
                Hesabınız yok mu?
                <a href="{{ route('register') }}" class="font-bold text-brand-700 hover:underline ml-1">Hemen ücretsiz üye olun</a>
            </div>
        </div>
    </div>
</section>
@endsection
