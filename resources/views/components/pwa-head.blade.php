{{--
    Cabeçalho PWA de um app do portal: manifest, cor do tema, ícones e modo tela cheia.
    Obs.: a prop não pode se chamar `app`, pois `$app` nas views é o container do Laravel.
    Uso: <x-pwa-head :portal-app="$portalApp" /> ou <x-pwa-head start-link="/bingo" />
--}}
@props(['portalApp' => null, 'startLink' => null])
@php
    $pwaApp = $portalApp ?? ($startLink ? \App\Models\PortalApp::where('start_link', $startLink)->first() : null);
@endphp
@if ($pwaApp)
    <link rel="manifest" href="{{ route('pwa.manifest', ['id' => $pwaApp->id]) }}">
    <meta name="theme-color" content="{{ $pwaApp->pwa_theme_color ?: '#ccf381' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $pwaApp->pwa_short_name ?: \Illuminate\Support\Str::limit($pwaApp->title, 12, '') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $pwaApp->icon_url }}">
    <link rel="icon" type="image/png" href="{{ $pwaApp->icon_url }}">
@endif
