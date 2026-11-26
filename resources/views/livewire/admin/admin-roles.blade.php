<div class="space-y-6 max-w-5xl">
    <x-admin.flash-messages />
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rol ve İzinler</h1>
        <p class="mt-2 text-sm text-slate-600">Panelde Yönetici ve Editör olmak üzere iki rol bulunur. Editörün çalışabileceği bölümleri buradan belirleyebilirsiniz.</p>
    </div>
    <div class="grid gap-5 md:grid-cols-2">
        @foreach($roles as $role)
            <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $role->key === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $role->key === 'admin' ? 'Tam erişim' : 'Seçilen bölümlere erişim' }}</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $role->name }}</h2>
                <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">
                    @if($role->key === 'admin')
                        Tüm bölümleri, kullanıcı hesaplarını ve editör izinlerini yönetir. Yönetici yetkileri sabittir.
                    @else
                        Yönetici tarafından seçilen bölümlerde işlem yapabilir. Şu anda {{ count($role->permissions ?? []) }} / {{ $moduleCount }} bölüm açık.
                    @endif
                </p>
                <a href="{{ route('admin.admin-roles.edit', $role->id) }}" class="mt-6 inline-flex justify-center rounded-xl px-4 py-3 text-sm font-bold {{ $role->key === 'editor' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $role->key === 'editor' ? 'Editör İzinlerini Düzenle' : 'Yönetici Yetkilerini Gör' }}
                </a>
            </section>
        @endforeach
    </div>
</div>
