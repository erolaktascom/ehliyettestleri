<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Yönetici Girişi - {{ $siteName }}</title>
    <link rel="icon" href="{{ $siteSetting?->mediaUrl('favicon') ?? asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/admin-login.css')
    
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-100 via-blue-50/40 to-slate-100">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            @if($siteSetting?->mediaUrl('logo'))
                <img src="{{ $siteSetting->mediaUrl('logo') }}" alt="{{ $siteSetting->site_name }}" class="h-14 max-w-full object-contain mx-auto mb-3">
            @else
            <div class="inline-flex items-center justify-center size-14 rounded-2xl bg-gradient-to-br from-navy-900 to-brand-600 text-white shadow-lg shadow-blue-900/20 mb-3">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6.75 4.75h10.5a2 2 0 0 1 2 2v10.5a2 2 0 0 1-2 2H6.75a2 2 0 0 1-2-2V6.75a2 2 0 0 1 2-2Z"/>
                    <path d="m8.25 12.25 2.25 2.25 5.25-5.25" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            @endif
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $siteName }}</h1>
            <p class="text-sm font-semibold text-slate-500 mt-1">Yönetim Paneli Girişi</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl p-7 shadow-xl shadow-slate-200/60 border border-slate-200/80">
            @if (session('success'))
                <div role="status" class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">E-posta Adresi</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition placeholder:text-slate-400"
                        placeholder="admin@example.com">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Şifre</label>
                    </div>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition placeholder:text-slate-400"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-600 select-none">
                        <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Beni hatırla</span>
                    </label>
                    <a href="{{ route('home') }}" class="text-xs font-medium text-slate-500 hover:text-blue-600 transition">Ana Sayfaya Dön</a>
                </div>

                <button type="submit"
                    class="w-full py-2.5 px-4 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-bold text-sm shadow-md shadow-navy-900/20 transition duration-150 active:scale-[0.99] mt-2">
                    Oturum Aç
                </button>
            <a href="{{ route('password.request') }}" class="block text-center text-sm text-blue-700 mt-4">Şifremi unuttum</a>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 font-medium mt-6">
            &copy; {{ date('Y') }} {{ $siteName }}. Güvenli Yönetici Erişimi.
        </p>
    </div>
</body>
</html>
