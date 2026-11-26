<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageSeo = \App\Support\PageSeo::metadata(
            get_defined_vars(),
            $__env->yieldContent('title', $siteName),
            $__env->yieldContent('meta_description') ?: ($siteSetting?->site_description ?? ''),
            $__env->yieldContent('robots'),
            $__env->hasSection('seo-error')
        );
    @endphp
    <title>{{ $pageSeo['title'] }}</title>
    <link rel="icon" href="{{ $siteSetting?->mediaUrl('favicon') ?? asset('favicon.svg') }}">
    @if($pageSeo['description'] !== '')<meta name="description" content="{{ $pageSeo['description'] }}">@endif
    @if($pageSeo['canonical'])<link rel="canonical" href="{{ $pageSeo['canonical'] }}">@endif
    <meta name="robots" content="{{ $pageSeo['robots'] }}">
    @foreach(($seoSetting?->verification_tags ?? []) as $verificationName => $verificationContent)
        @if(is_string($verificationContent) && trim($verificationContent) !== '')
            <meta name="{{ $verificationName }}" content="{{ $verificationContent }}">
        @endif
    @endforeach

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Locally compiled site styles -->
    

    <!-- Alpine.js -->
    

    <style>
        .store-badge { display: inline-flex; align-items: center; gap: 8px; width: 184px; max-width: 100%; min-height: 52px; padding: 7px 10px; border: 1px solid #999; border-radius: 9px; background: #000; color: #fff; font-family: Arial, sans-serif; text-align: left; vertical-align: top; }
        .store-badge-icon { width: 29px; height: 33px; flex-shrink: 0; }
        .store-badge-copy { display: flex; flex-direction: column; gap: 2px; white-space: nowrap; }
        .store-badge-title { font-size: 19px; font-weight: 400; line-height: 1.1; letter-spacing: -.5px; }
        .store-badge-caption { font-size: 11px; font-weight: 400; line-height: 1.1; }
        .store-badge-caption-google { font-size: 10px; }
        a.store-badge:hover { background: #171717; }
        a.store-badge:focus-visible { outline: 3px solid #4871f4; outline-offset: 3px; }
        .site-ad-header { margin: 0 auto; padding-block: 16px; flex-shrink: 0; width: calc(100% - 32px); max-width: 728px; }
        .site-ad-mobile.site-ad-header { max-width: 320px; }
        .site-ad-desktop, .site-ad-rail { display: none; }
        .site-ad-shell { width: 100%; flex: 1; min-width: 0; }
        .site-ad-shell > main { min-width: 0; }
        @media (min-width: 1024px) {
            .site-ad-mobile { display: none; }
            .site-ad-desktop { display: block; }
            .site-ad-shell.has-side-ads { display: grid; grid-template-columns: var(--left-ad, 0px) minmax(0, 1fr) var(--right-ad, 0px); gap: 16px; max-width: 1568px; margin: 0 auto; padding: 0 16px; }
            .site-ad-shell.has-left-ad { --left-ad: 160px; }
            .site-ad-shell.has-right-ad { --right-ad: 160px; }
            .site-ad-rail { display: block; padding-top: 24px; }
            .site-ad-rail .site-ad { position: sticky; top: 88px; }
            .site-ad-shell.has-side-ads > main { grid-column: 2; grid-row: 1; }
            .site-ad-rail-left { grid-column: 1; grid-row: 1; }
            .site-ad-rail-right { grid-column: 3; grid-row: 1; }
        }
        [x-cloak] { display: none !important; }
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }
        .site-header-logo {
            width: auto;
            height: 2.5rem;
            max-width: min(216px, 100%);
            object-fit: contain;
        }
        .site-container {
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
            width: 100%;
        }
        @media (min-width: 640px) {
            .site-container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }
        .site-card {
            background-color: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        .stitch-row {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }
        .stitch-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px;
            background-color: #eef2ff;
            color: #2339d2;
        }
        .stitch-icon-box {
            display: grid;
            place-items: center;
            border-radius: 0.875rem;
            background-color: #eef2ff;
            color: #2339d2;
        }
        .site-button-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: #2339d2;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(35, 57, 210, 0.2);
        }
        .site-button-primary:hover {
            background-color: #1e30aa;
            transform: translateY(-1px);
        }
        .site-button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: #ffffff;
            color: #2339d2;
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .site-button-secondary:hover {
            background-color: #f8faff;
            border-color: #cbd5e1;
        }
        .site-action-label { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; line-height: 1.35; }
        .site-action-label > .portal-icon { width: 1.125rem; height: 1.125rem; flex: 0 0 1.125rem; }
        main .site-button-primary, main .site-button-secondary, main .site-action { min-height: 44px; }
        main .site-action { min-width: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: .75rem; }
        main .site-action:focus-visible, main .site-button-primary:focus-visible, main .site-button-secondary:focus-visible { outline: 3px solid #92a6fb; outline-offset: 3px; }
        main button:disabled.site-button-primary:hover, main button:disabled.site-button-secondary:hover { transform: none; }
        @media (max-width: 639px) {
            .study-navigation > button { padding-left: .75rem; padding-right: .75rem; font-size: .8125rem; flex: 1; min-width: 0; }
        }
    </style>
    @livewireStyles
    @stack('styles')
    @vite('resources/css/site.css')
</head>
<body class="flex flex-col min-h-screen bg-canvas font-sans text-ink antialiased w-full max-w-full overflow-x-hidden">

    <!-- Top Sticky Header -->
    @php
        $siteMenus = \App\Models\NavigationItem::visibleMenus();
    @endphp
    @unless($__env->hasSection('test-session'))
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
        <div class="site-container flex items-center justify-between gap-2 h-[72px]">
            <!-- Left: Mobile Menu Toggle + Brand Logo -->
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <!-- Mobile Menu Toggle Button -->
                <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="min-[1200px]:hidden shrink-0 p-1.5 -ml-1 text-slate-700 hover:text-brand-700 rounded-lg focus:outline-none" :aria-expanded="mobileMenuOpen" aria-controls="mobile-site-menu" :aria-label="mobileMenuOpen ? 'Menüyü kapat' : 'Menüyü aç'">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Brand Logo -->
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex min-w-0 items-center gap-2.5" aria-label="{{ $siteName }} ana sayfa">
                    @if($siteSetting?->mediaUrl('logo'))
                        <img src="{{ $siteSetting->mediaUrl('logo') }}" alt="{{ $siteSetting->site_name }}" class="site-header-logo">
                    @else

                    <span class="grid size-9 place-items-center rounded-xl bg-brand-700 text-white shadow-md shadow-brand-700/20">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6.5 16.5h11M7.5 16.5l-1-5.5 2-4h7l2 4-1 5.5M8 11h8M8.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="text-xl font-extrabold tracking-tight text-brand-900">{{ $siteName }}</span>
                
                    @endif
</a>
            </div>

            <!-- Desktop Nav Pill -->
            <nav class="hidden min-[1200px]:flex min-w-0 items-center gap-1 overflow-x-auto bg-[#f2f4ff] p-1.5 rounded-xl border border-slate-200/60" aria-label="Ana menü">
                @foreach($siteMenus->get('desktop', collect()) as $link)
                    <a href="{{ $link->url }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap transition {{ request()->getPathInfo() === parse_url($link->url, PHP_URL_PATH) ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-600 hover:text-brand-700' }}">{{ $link->name }}</a>
                @endforeach
            </nav>

            <!-- Auth Actions -->
            <div class="flex items-center gap-1 sm:gap-2">
                @auth
                    <!-- Notifications Dropdown -->
                    @php
                        $memberNotifications = \App\Models\NotificationDelivery::where('user_id', auth()->id())->where('channel', 'in_app')->where('status', 'sent')->latest('sent_at')->limit(5)->get();
                        $unreadNotificationCount = \App\Models\NotificationDelivery::where('user_id', auth()->id())->where('channel', 'in_app')->where('status', 'sent')->whereNull('read_at')->count();
                    @endphp
                    <details class="group relative" name="header-account-menu" @click.outside="$el.open = false" @keydown.escape.window="if ($el.open) { $el.open = false; $el.querySelector('summary').focus() }">
                        <summary class="relative grid size-10 cursor-pointer list-none place-items-center rounded-full text-muted transition hover:bg-brand-50 hover:text-brand-700 [&::-webkit-details-marker]:hidden" aria-label="Bildirimler">
                            @if($unreadNotificationCount)<span class="absolute -top-1 -right-1 rounded-full bg-red-600 px-1.5 text-xs text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                            </svg>
                        </summary>
                        <div class="fixed left-1/2 top-[4.5rem] z-50 hidden max-h-[calc(100dvh-5.5rem)] w-[calc(100vw-2rem)] max-w-[22rem] -translate-x-1/2 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 group-open:block sm:absolute sm:left-auto sm:right-0 sm:top-full sm:max-h-none sm:translate-x-0">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <strong class="text-sm text-ink">Son bildirimler</strong>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse($memberNotifications as $notification)
                                    <a href="{{ route('student.notifications') }}" class="block px-4 py-3 hover:bg-brand-50/50 transition">
                                        <strong class="line-clamp-1 text-sm text-ink">{{ $notification->subject }}</strong>
                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-muted">{{ $notification->content }}</p>
                                        <time class="mt-1 block text-[10px] text-slate-400">{{ $notification->sent_at->format('d.m.Y H:i') }}</time>
                                    </a>
                                @empty
                                    <p class="px-4 py-3 text-sm text-muted">Henüz bildirimin yok.</p>
                                @endforelse
                                <a href="{{ route('student.notifications') }}" class="block px-4 py-3 text-sm font-bold text-brand-700">Tüm bildirimler</a>
                            </div>
                        </div>
                    </details>

                    <!-- User Account Popover -->
                    <details class="group relative" name="header-account-menu" @click.outside="$el.open = false" @keydown.escape.window="if ($el.open) { $el.open = false; $el.querySelector('summary').focus() }">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full p-1 pr-2 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-900 [&::-webkit-details-marker]:hidden" aria-label="Kullanıcı hesabım">
                            <span class="grid size-10 place-items-center rounded-full bg-brand-900 text-sm font-extrabold text-white">
                                {{ auth()->user()->initials }}
                            </span>
                            <svg class="hidden size-4 text-brand-900 transition group-open:rotate-180 sm:block" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 0 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </summary>

                        <div class="absolute right-0 top-full z-50 hidden w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 group-open:block">
                            <div class="border-b border-slate-100 bg-brand-50/70 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-900 text-sm font-extrabold text-white">
                                        {{ auth()->user()->initials }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-extrabold text-ink">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                                    </div>
                                    <span class="ml-auto rounded-full bg-amber-100 px-2 py-1 text-[10px] font-extrabold text-amber-900">
                                        {{ auth()->user()->is_admin ? 'YÖNETİCİ' : (auth()->user()->isPremium() ? 'PREMIUM' : 'ÖĞRENCİ') }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid p-2">
                                @if(auth()->user()->is_admin)
                                    <a href="/admin" class="rounded-xl px-3 py-2.5 text-sm font-bold text-amber-900 transition hover:bg-amber-50">Yönetim Paneli</a>
                                @endif
                                <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2.5 text-sm font-bold text-brand-900 transition hover:bg-brand-50">Genel Bakış</a>
                                <a href="{{ route('student.settings.profile') }}" class="rounded-xl px-3 py-2.5 text-sm font-bold text-muted transition hover:bg-brand-50 hover:text-brand-900">Hesap Ayarları</a>
                                <a href="{{ route('student.premium') }}" class="rounded-xl px-3 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-50">Premium</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-600 transition hover:bg-red-50">Çıkış Yap</button>
                                </form>
                            </div>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="site-button-primary py-1.5 px-3 sm:py-2 sm:px-4 text-xs sm:text-sm whitespace-nowrap shrink-0">Üye Girişi</a>
                @endauth
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div id="mobile-site-menu" x-cloak x-show="mobileMenuOpen" x-transition class="min-[1200px]:hidden border-t border-slate-200 bg-white px-4 py-4 space-y-2">
            <nav aria-label="Mobil menü" class="space-y-2">
                @foreach($siteMenus->get('mobile', collect()) as $link)
                    <a href="{{ $link->url }}" class="block px-3 py-2.5 rounded-lg text-sm font-bold text-slate-800 hover:bg-brand-50 hover:text-brand-700">{{ $link->name }}</a>
                @endforeach
            </nav>
            @guest
                <div class="pt-3 border-t border-slate-100 grid gap-2">
                    <a href="{{ route('login') }}" class="site-button-primary text-center text-xs py-2">Üye Girişi</a>
                </div>
            @endguest
        </div>
    </header>
    @endunless

    @php
        $adSlots = $__env->hasSection('hide-ads') ? collect() : \App\Models\Advertisement::slots();
        $leftAd = $adSlots->get('desktop.LeftBanner');
        $rightAd = $adSlots->get('desktop.RightBanner');
    @endphp
    @foreach(['desktop.Header', 'mobile.Header'] as $slot)
        @if($adSlots->has($slot))<x-site-ad :ad="$adSlots->get($slot)" />@endif
    @endforeach
    <div class="site-ad-shell {{ $leftAd || $rightAd ? 'has-side-ads' : '' }} {{ $leftAd ? 'has-left-ad' : '' }} {{ $rightAd ? 'has-right-ad' : '' }}">
        @if($leftAd)<aside aria-label="Sol reklam alanı" class="site-ad-rail site-ad-rail-left"><x-site-ad :ad="$leftAd" /></aside>@endif
        @if($rightAd)<aside aria-label="Sağ reklam alanı" class="site-ad-rail site-ad-rail-right"><x-site-ad :ad="$rightAd" /></aside>@endif
    <!-- Main Content Area -->
    <main class="flex-1">
        @if(session('success'))
            <div class="site-container mt-4">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between text-sm font-semibold">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="site-container mt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center justify-between text-sm font-semibold">
                    <span>{{ session('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-900">&times;</button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>
    </div>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-slate-200">
        <div class="site-container py-6 sm:py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-8">
                <!-- Sol Kolon: Site Markası, Açıklama ve Mağaza Butonları -->
                <div class="lg:col-span-5 max-w-md">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 group">
                    @if($siteSetting?->mediaUrl('logo'))
                        <img src="{{ $siteSetting->mediaUrl('logo') }}" alt="{{ $siteSetting->site_name }}" class="h-10 w-auto max-w-[180px] object-contain">
                    @else

                        <span class="grid size-10 place-items-center rounded-xl bg-brand-700 text-white shadow-md shadow-brand-700/20 transition group-hover:scale-105">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6.5 16.5h11M7.5 16.5l-1-5.5 2-4h7l2 4-1 5.5M8 11h8M8.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="text-xl font-extrabold tracking-tight text-brand-950">{{ $siteName }}</span>
                    
                    @endif
</a>

                    <p class="text-sm text-muted leading-relaxed mt-2.5 max-w-sm">
                        {{ $siteSetting?->site_description }}
                    </p>

                    <!-- Sosyal Medya İkonları -->
                    <div class="flex items-center gap-2 mt-4">
                        @if($siteSetting?->socialUrl('instagram'))
                        <a href="{{ $siteSetting->socialUrl('instagram') }}" target="_blank" rel="noopener noreferrer"
                           class="grid size-9 place-items-center rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-500 transition-all hover:scale-105 shadow-sm"
                           title="Instagram" aria-label="Instagram">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        @endif
                        @if($siteSetting?->socialUrl('tiktok'))
                        <a href="{{ $siteSetting->socialUrl('tiktok') }}" target="_blank" rel="noopener noreferrer"
                           class="grid size-9 place-items-center rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-500 transition-all hover:scale-105 shadow-sm"
                           title="TikTok" aria-label="TikTok">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg>
                        </a>
                        @endif
                        @if($siteSetting?->socialUrl('youtube'))
                        <a href="{{ $siteSetting->socialUrl('youtube') }}" target="_blank" rel="noopener noreferrer"
                           class="grid size-9 place-items-center rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-500 transition-all hover:scale-105 shadow-sm"
                           title="YouTube" aria-label="YouTube">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        @endif
                        @if($siteSetting?->socialUrl('facebook'))
                        <a href="{{ $siteSetting->socialUrl('facebook') }}" target="_blank" rel="noopener noreferrer"
                           class="grid size-9 place-items-center rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-500 transition-all hover:scale-105 shadow-sm"
                           title="Facebook" aria-label="Facebook">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        @endif
                        @if($siteSetting?->socialUrl('x'))
                        <a href="{{ $siteSetting->socialUrl('x') }}" target="_blank" rel="noopener noreferrer"
                           class="grid size-9 place-items-center rounded-xl bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-500 transition-all hover:scale-105 shadow-sm"
                           title="X (Twitter)" aria-label="X">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>

                @php
                    $footerLinks = $siteMenus->get('footer', collect());
                    $footerGroups = \App\Models\NavigationGroup::footerGroups()->filter(fn ($group) => $group->is_active && $footerLinks->where('footer_group', $group->key)->isNotEmpty());
                @endphp
                <nav aria-label="Alt bilgi sütunları" class="lg:col-span-7 grid gap-8" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,184px),1fr))">
                    @foreach($footerGroups as $group)
                        @php($groupLinks = $footerLinks->where('footer_group', $group->key))
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-brand-950 mb-3">{{ $group->name }}</h3>
                            <ul class="space-y-2.5 text-sm font-semibold text-muted">
                                @foreach($groupLinks as $link)
                                    <li>
                                        @if($group->key === 'apps')
                                            <x-store-badge :item="$link" />
                                        @else
                                            <a href="{{ $link->url }}" class="hover:text-brand-700 transition">{{ $link->name }}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </div>

            <!-- Alt Çizgi ve Telif -->
            <div class="mt-8 pt-4 sm:pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-muted font-medium">
                    {{ $siteSetting?->copyrightText() }}
                </p>
                @if($footerLinks->whereNull('footer_group')->isNotEmpty())
                    <nav aria-label="Alt bilgi menüsü" class="flex flex-wrap items-center justify-center sm:justify-end gap-x-4 gap-y-1 text-xs font-medium text-slate-400">
                        @foreach($footerLinks->whereNull('footer_group') as $link)
                            @unless($loop->first)<span aria-hidden="true">•</span>@endunless
                            <a href="{{ $link->url }}" class="hover:text-brand-700 transition">{{ $link->name }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
