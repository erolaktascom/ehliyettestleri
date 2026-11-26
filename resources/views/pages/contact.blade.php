@extends('layouts.app')

@section('title', 'İletişim - ' . $siteName)

@push('styles')
<style>
    .contact-page { padding: 20px 0 56px; }
    .contact-page-heading { padding: 32px; }
    .contact-page-heading h1 { margin: 0; font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.2; font-weight: 800; letter-spacing: -.035em; color: #0f172a; }
    .contact-page-heading p { max-width: 70ch; margin: 14px 0 0; font-size: 1rem; line-height: 1.75; color: #64748b; }
    .contact-page-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: 24px; align-items: start; margin-top: 24px; }
    .contact-page-details { padding: 28px; min-width: 0; }
    .contact-page h2 { color: #0f172a; font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; }
    .contact-page-details > p { margin-top: 8px; color: #64748b; font-size: .875rem; line-height: 1.75; }
    .contact-page-items { margin-top: 24px; display: grid; gap: 20px; }
    .contact-page-item { border-top: 1px solid #e8edf5; padding-top: 20px; }
    .contact-page-item dt { color: #64748b; font-size: .75rem; font-weight: 600; margin-bottom: 6px; }
    .contact-page-item dd { color: #0f172a; font-size: .9375rem; line-height: 1.7; overflow-wrap: anywhere; }
    .contact-page-form { padding: 28px; min-width: 0; }
    .contact-page-form-heading { margin-bottom: 24px; }
    .contact-page-form-heading p { margin-top: 8px; color: #64748b; font-size: .875rem; line-height: 1.75; }
    @media (max-width: 767px) {
        .contact-page-grid { grid-template-columns: minmax(0, 1fr); gap: 20px; margin-top: 20px; }
    }
    @media (max-width: 639px) {
        .contact-page { padding: 16px 0 32px; }
        .contact-page-heading, .contact-page-details, .contact-page-form { padding: 24px 20px; }
        .contact-page-heading p { font-size: .9375rem; }
    }
</style>
@endpush

@section('content')
<section class="contact-page" aria-labelledby="contact-title">
    <div class="site-container">
        <header class="site-card contact-page-heading">
            <h1 id="contact-title">İletişim</h1>
            <p>Sınavlar, üyeliğiniz veya içeriklerimiz hakkında bize ulaşın. Sorularınızı ve önerilerinizi dinlemek için buradayız.</p>
        </header>
        <div class="contact-page-grid">
            <aside class="site-card contact-page-details" aria-labelledby="contact-details-title">
                <h2 id="contact-details-title">Size nasıl yardımcı olabiliriz?</h2>
                <p>Aşağıdaki iletişim bilgilerinden veya mesaj formundan bize ulaşabilirsiniz.</p>
                <dl class="contact-page-items">
                    @if(filled($siteSetting?->phone))
                        <div class="contact-page-item"><dt>Telefon</dt><dd>{{ $siteSetting->phone }}</dd></div>
                    @endif
                    @if($siteSetting?->address)
                        <div class="contact-page-item"><dt>Adres</dt><dd class="whitespace-pre-line">{{ $siteSetting->address }}</dd></div>
                    @endif
                    <div class="contact-page-item"><dt>E-posta</dt><dd>{{ $siteSetting?->contact_email ?? \App\Models\SiteSetting::DEFAULT_CONTACT_EMAIL }}</dd></div>
                </dl>
            </aside>
            <livewire:contact-form />
        </div>
    </div>
</section>
@endsection
