            @if(\App\Support\GoogleLogin::enabled())
            <a href="{{ route('auth.google.redirect') }}" class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 transition text-sm font-bold text-slate-700 shadow-sm">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24">
                    <path d="M23.49 12.27c0-.79-.07-1.55-.2-2.27H12v4.51h6.45a5.52 5.52 0 0 1-2.39 3.52v2.93h3.87c2.27-2.09 3.56-5.17 3.56-8.69Z" fill="#4285F4"/>
                    <path d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.87-2.99c-1.07.72-2.44 1.15-4.08 1.15-3.13 0-5.78-2.11-6.73-4.95H1.28v3.04A12 12 0 0 0 12 24Z" fill="#34A853"/>
                    <path d="M5.27 14.3A7.22 7.22 0 0 1 4.9 12c0-.8.14-1.57.37-2.3V6.66H1.28A12 12 0 0 0 0 12c0 1.94.46 3.78 1.28 5.34l3.99-3.04Z" fill="#FBBC05"/>
                    <path d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.45-3.45A11.58 11.58 0 0 0 12 0 12 12 0 0 0 1.28 6.66L5.27 9.7C6.22 6.86 8.87 4.75 12 4.75Z" fill="#EA4335"/>
                </svg>
                Google ile Devam Et
            </a>

            <div class="my-6 flex items-center gap-3 text-xs font-semibold text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span>veya e-posta ile</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            @endif
