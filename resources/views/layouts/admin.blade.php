<!DOCTYPE html>
<html lang="tr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Yönetim Paneli') - {{ $siteName }}</title>
    <link rel="icon" href="{{ $siteSetting?->mediaUrl('favicon') ?? asset('favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    

    <!-- Quill Rich Text Editor CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.4); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(100, 116, 139, 0.6); }

        /* Quill Editor Styling */
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.75rem !important;
            border-top-right-radius: 0.75rem !important;
            border-color: #e2e8f0 !important;
            background: #f8fafc;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 0.75rem !important;
            border-bottom-right-radius: 0.75rem !important;
            border-color: #e2e8f0 !important;
            font-family: inherit;
            font-size: 0.875rem;
            background: #ffffff;
        }
        .ql-editor {
            min-height: 140px;
            line-height: 1.6;
        }
        .ql-editor.ql-blank::before {
            color: #94a3b8;
            font-style: normal;
        }
        .ql-snow .ql-editor h1, .ql-snow .ql-editor h2, .ql-snow .ql-editor h3 {
            font-weight: 700;
            margin-top: 0.75rem;
            margin-bottom: 0.35rem;
        }
        .ql-snow .ql-editor ul {
            list-style-type: disc !important;
            padding-left: 1.5rem !important;
        }
        .ql-snow .ql-editor ol {
            list-style-type: decimal !important;
            padding-left: 1.5rem !important;
        }
    </style>

    @livewireStyles
    @stack('styles')
    @vite('resources/css/admin.css')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden bg-slate-50">
        
        <!-- ================= Mobile Sidebar Backdrop ================= -->
        <div x-cloak x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"></div>

        <!-- ================= Sidebar ================= -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-gradient-to-b from-navy-900 to-navy-950 text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 shrink-0 shadow-2xl lg:shadow-none border-r border-white/10">
            
            <!-- Brand Logo -->
            <div class="h-16 flex items-center justify-between px-5 border-b border-white/10 shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    @if($siteSetting?->mediaUrl('logo'))
                        <img src="{{ $siteSetting->mediaUrl('logo') }}" alt="{{ $siteSetting->site_name }}" style="color-scheme: dark" class="h-10 w-auto max-w-[180px] object-contain">
                    @else

                    <span class="size-9 rounded-xl bg-white text-navy-900 flex items-center justify-center font-black shadow-md group-hover:scale-105 transition">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M6.75 4.75h10.5a2 2 0 0 1 2 2v10.5a2 2 0 0 1-2 2H6.75a2 2 0 0 1-2-2V6.75a2 2 0 0 1 2-2Z"/>
                            <path d="m8.25 12.25 2.25 2.25 5.25-5.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="font-extrabold text-base tracking-tight text-white">{{ $siteName }}</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-wider">Yönetim Paneli</span>
                    </div>
                
                    @endif
</a>
                <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-3.5 py-4 space-y-5">
                @php($allowedModules = array_flip(auth()->user()?->adminModules() ?? []))
                @foreach(\App\Support\AdminAccess::groups() as $group => $modules)
                    @php($visibleModules = array_intersect_key($modules, $allowedModules))
                    @if($visibleModules)
                        <div>
                            <div class="px-3 mb-2 text-[10px] font-extrabold uppercase tracking-wider text-white/40">{{ $group }}</div>
                            <div class="space-y-1">
                                @foreach($visibleModules as $module => $label)
                                    <a href="{{ route('admin.'.$module) }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.'.$module, 'admin.'.$module.'.*') ? 'bg-white/15 text-white shadow-sm ring-1 ring-white/20' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                                        <x-admin-nav-icon :module="$module" />
                                        <span>{{ $label }}</span>
                                        @if($module === 'contact-messages' && ($unreadCount = \App\Models\ContactMessage::where('is_read', false)->count()))
                                            <span class="ms-auto rounded-full bg-blue-500 px-2 py-0.5 text-xs">{{ $unreadCount }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <!-- Sidebar Footer: Active Admin Profile -->
            <div class="p-3 border-t border-white/10 bg-navy-950/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="size-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                        {{ auth()->user()?->initials ?? 'Y' }}
                    </span>
                    <div class="truncate leading-tight">
                        <div class="text-xs font-bold text-white truncate">{{ auth()->user()?->name ?? 'Yönetici' }}</div>
                        <div class="text-[10px] text-white/50 truncate">{{ auth()->user()?->email }}</div>
                    </div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Çıkış Yap" class="p-1.5 rounded-lg text-white/60 hover:text-red-400 hover:bg-white/10 transition">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ================= Main Content Area ================= -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-sm shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="hidden sm:block">
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">@yield('page_title', 'Yönetim Paneli')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <a href="{{ route('home') }}" target="_blank"
                       aria-label="Siteyi Görüntüle" title="Siteyi Görüntüle"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        <span class="hidden sm:inline">Siteyi Görüntüle</span>
                        <svg class="size-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    @auth
                        <div class="flex items-center gap-2.5 min-w-0 border-l border-slate-200 pl-3" aria-label="Oturum açan kullanıcı">
                            <span class="size-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">
                                {{ auth()->user()->initials }}
                            </span>
                            <div class="min-w-0 max-w-28 sm:max-w-40 leading-tight">
                                <div class="truncate text-sm font-bold text-slate-900" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ auth()->user()->role_name }}</div>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                {{-- Livewire screens render flashes inside their reactive root. --}}
                @unless(isset($slot))
                    <x-admin.flash-messages />
                @endunless

                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

    <!-- Notification Toast Listener for Livewire -->
    <div x-data="{ show: false, message: '', type: 'success' }"
         x-on:notify.window="message = $event.detail.message || $event.detail; type = $event.detail.type || 'success'; show = true; setTimeout(() => show = false, 4000)"
         x-cloak
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl border text-sm font-bold"
         :class="type === 'error' ? 'bg-red-900 text-white border-red-800' : 'bg-slate-900 text-white border-slate-800'">
        <span x-text="message"></span>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
