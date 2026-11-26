@props(['active' => 'seo-settings'])

<nav aria-label="SEO ayarları bölümleri" class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
    @foreach(['seo-settings' => 'Genel Ayarlar', 'seo-settings.pages' => 'Sayfa SEO Ayarları', 'seo-redirects' => 'Yönlendirmeler', 'seo-not-found-logs' => '404 Kayıtları', 'seo-verification' => 'Doğrulama Ayarları', 'sitemap-settings' => 'Sitemap Ayarları'] as $key => $label)
        @if(auth()->user()?->canAccessAdminRoute('admin.'.$key))
            <a href="{{ route('admin.'.$key) }}"
               @if($active === $key) aria-current="page" @endif
               @class(['inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                   'bg-blue-600 text-white shadow-sm' => $active === $key,
                   'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-900' => $active !== $key])>
                {{ $label }}
            </a>
        @endif
    @endforeach
</nav>