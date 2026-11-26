@extends('layouts.app')

@section('title', 'Hesap Ayarları - ' . $siteName)

@section('content')
<div class="site-page-content">
    <div class="site-container pt-4 sm:pt-5 pb-8 sm:pb-12">
        <!-- Breadcrumbs -->
        <nav aria-label="Sayfa yolu" class="flex flex-wrap items-center gap-2 text-xs text-muted mb-4 sm:mb-5">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-700">Öğrenci Portalı</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="font-bold text-ink">Hesap Ayarları</span>
        </nav>

        <header class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Öğrenci Portalı</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Hesap Ayarları</h1>
            <p class="mt-2 text-sm text-muted">Profil tercihlerinizi, sınav tarihinizi ve hesap güvenliğinizi yönetin.</p>
        </header>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-8">
            <!-- Profil Bilgileri Formu -->
            <section class="site-card p-6 sm:p-8">
                <h2 class="text-xl font-extrabold text-ink mb-1">Profil Bilgileri</h2>
                <p class="text-sm text-muted mb-6">Sınav takip sisteminin size özel çalışması için bilgilerinizi güncel tutun.</p>

                <form method="POST" action="{{ route('student.settings.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <fieldset class="rounded-xl border border-brand-100 bg-brand-50/40 p-5 space-y-4">
                        <legend class="text-sm font-bold text-ink px-2">Herkese Açık Profil</legend>
                        <p class="text-xs text-muted">Adınız, profil fotoğrafınız, biyografiniz ve üyelik tarihiniz profil sayfanızda görünür.</p>
                        @if($user->profile_slug)
                            <a href="{{ route($user->is_admin ? 'blog.author' : 'members.show', $user->profile_slug) }}" class="inline-flex text-sm font-bold text-brand-700"><x-action-label icon="profile">Profilimi Görüntüle</x-action-label></a>
                        @endif
                        <div>
                            <label for="profile_photo" class="block text-sm font-semibold text-ink mb-2">Profil Fotoğrafı</label>
                            @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="Mevcut profil fotoğrafı" class="size-16 rounded-xl object-cover mb-3">
                                <label class="flex items-center gap-2 text-xs mb-3"><input type="checkbox" name="remove_photo" value="1"> Mevcut fotoğrafı kaldır</label>
                            @endif
                            <input id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                            <p class="text-xs text-muted mt-2">JPG, PNG veya WebP · En fazla 2 MB</p>
                        </div>
                        <div>
                            <label for="biography" class="block text-sm font-semibold text-ink mb-2">Biyografi</label>
                            <textarea id="biography" name="biography" rows="4" maxlength="1500" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('biography', $user->biography) }}</textarea>
                        </div>
                    </fieldset>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Ad Soyad</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">E-Posta Adresi</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Hedef Ehliyet Sınıfı</label>
                            @php
                                $licenseOptions = [
                                    'B Sınıfı Ehliyet' => 'B Sınıfı (Otomobil)',
                                    'A Sınıfı Ehliyet' => 'A Sınıfı (Motosiklet)',
                                    'A2 Sınıfı Ehliyet' => 'A2 Sınıfı (Motosiklet)',
                                    'C Sınıfı Ehliyet' => 'C Sınıfı (Kamyon)',
                                    'D Sınıfı Ehliyet' => 'D Sınıfı (Otobüs)',
                                ];
                                $selectedLicense = old('target_license_class', $user->target_license_class) ?? '';
                            @endphp
                            <select name="target_license_class" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                                <option value="" @selected($selectedLicense === '')>Seçiniz</option>
                                @if($selectedLicense !== '' && !array_key_exists($selectedLicense, $licenseOptions))
                                    <option value="{{ $selectedLicense }}" selected>{{ $selectedLicense }}</option>
                                @endif
                                @foreach($licenseOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedLicense === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Hedef Sınav Tarihi</label>
                            <input type="date" name="driving_exam_date" value="{{ old('driving_exam_date', $user->driving_exam_date ? $user->driving_exam_date->format('Y-m-d') : '') }}"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Şehir</label>
                            <input type="text" name="city" value="{{ old('city', $user->city) }}" placeholder="Örn: İstanbul"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Doğum Tarihi</label>
                            <input type="date" name="birth_date" max="{{ now()->toDateString() }}" value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="site-button-primary px-6 py-2.5">
                            <x-action-label icon="save">Bilgileri Kaydet</x-action-label>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Şifre Güncelleme Formu -->
            <section class="site-card p-6 sm:p-8">
                <h2 class="text-xl font-extrabold text-ink mb-1">{{ $user->password ? 'Şifre Değiştir' : 'Şifre Oluştur' }}</h2>
                <p class="text-sm text-muted mb-6">Hesabınızın güvenliği için güçlü bir parola kullanın.</p>

                <form method="POST" action="{{ route('student.settings.password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @if($user->password)
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Mevcut Şifre</label>
                        <input type="password" name="current_password" required
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>

                    @endif
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Yeni Şifre</label>
                            <input type="password" name="password" required minlength="6"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Yeni Şifre (Tekrar)</label>
                            <input type="password" name="password_confirmation" required minlength="6"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-ink focus:border-brand-600 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="site-button-secondary px-6 py-2.5">
                            <x-action-label icon="lock">Şifreyi Güncelle</x-action-label>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
