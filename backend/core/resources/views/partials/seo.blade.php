@php
    $siteName      = gs()->siteName(__($pageTitle ?? ''));
    $metaTitle     = @$seoContents->social_title ?? @$seoContents->title ?? $siteName;
    $metaDesc      = @$seoContents->description ?? @$seo->description ?? '';
    $ogTitle       = @$seoContents->og_title ?: $metaTitle;
    $ogDesc        = @$seoContents->og_description ?: (@$seoContents->social_description ?? $metaDesc);
    $twitterTitle  = @$seoContents->twitter_title ?: $ogTitle;
    $twitterDesc   = @$seoContents->twitter_description ?: $ogDesc;
    $seoImage      = $seoImage ?? (@$seo->image ? getImage(getFilePath('seo') . '/' . $seo->image) : siteLogo());
    $canonicalUrl  = @$canonicalUrl ?? url()->current();
    $ogType        = @$seoContents->og_type ?? @$ogType ?? 'website';
    $robotsContent = @$seoContents->robots ?? @$robotsContent ?? 'index, follow';
    $twitterSite   = @$seoContents->twitter_site ?? '@liztodelivery';
@endphp

    {{-- ── Basic Meta ── --}}
    <meta name="robots" content="{{ $robotsContent }}">
    <meta name="author" content="{{ gs()->siteName() }}">
    <meta name="description" content="{{ Str::limit(strip_tags($metaDesc), 160) }}">
    <meta name="keywords" content="{{ implode(',', @$seoContents->keywords ?? @$seo->keywords ?? []) }}">

    {{-- ── Canonical URL ── --}}
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- ── Alternate (hreflang) ── --}}
    <link rel="alternate" hreflang="es-pe" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

    {{-- ── Favicon ── --}}
    <link rel="shortcut icon" href="{{ siteFavicon() }}" type="image/x-icon">
    <link rel="icon" href="{{ siteFavicon() }}" type="image/x-icon">

    {{-- ── Apple ── --}}
    <link rel="apple-touch-icon" href="{{ siteLogo() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">

    {{-- ── Open Graph / Facebook ── --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ gs()->siteName() }}">
    <meta property="og:title" content="{{ Str::limit($ogTitle, 95) }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($ogDesc), 300) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ $siteName }}">
    @if(@$seo->image)
        @php $ext = pathinfo($seo->image, PATHINFO_EXTENSION); @endphp
        <meta property="og:image:type" content="image/{{ $ext ?: 'png' }}">
    @endif
    <meta property="og:locale" content="es_PE">

    {{-- ── Twitter / X ── --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $twitterSite }}">
    <meta name="twitter:creator" content="{{ $twitterSite }}">
    <meta name="twitter:title" content="{{ Str::limit($twitterTitle, 70) }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($twitterDesc), 200) }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $siteName }}">

    {{-- ── Microsoft/Bing ── --}}
    <meta name="msnbot" content="index, follow">

    {{-- ── Preload SEO image for faster social sharing ── --}}
    @if(@$seoImage)
        <link rel="preload" as="image" href="{{ $seoImage }}">
    @endif
