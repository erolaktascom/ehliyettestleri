@extends('layouts.app')

@section('title', 'Ücretsiz Üye Ol - ' . $siteName)

@section('content')
<section class="pt-4 sm:pt-5 pb-12 sm:pb-20">
    <div class="site-container max-w-lg">

<div class="site-card p-6 sm:p-10 shadow-xl shadow-slate-900/5">
            <div class="text-center mb-8">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Ücretsiz Hesap Oluşturun</h1>
                <p class="text-xs sm:text-sm text-muted mt-1.5">Sınav sonuçlarınızı ve çözdüğünüz işaretleri kalıcı olarak saklayın.</p>
            </div>

            @include('partials.google-login')

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ad Soyad</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">E-posta Adresi</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Şifre</label>
                    <input type="password" id="password" name="password" required minlength="6"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Şifre Tekrarı</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100">
                </div>

                <button type="submit" class="site-button-primary w-full py-3 text-base shadow-md mt-2"><x-action-label icon="profile">Ücretsiz Üye Ol</x-action-label></button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-muted">
                Zaten hesabınız var mı?
                <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline ml-1">Giriş yapın</a>
            </div>
        </div>
    </div>
</section>
@endsection
