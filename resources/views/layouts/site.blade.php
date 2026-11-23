<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name') }} · os ônibus que passam onde você vai</title>
    <meta name="description" content="{{ __('site.pitch') }}">
    <meta name="theme-color" content="#ffc21a">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="{{ __('site.pitch') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:locale" content="pt_BR">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-paper font-sans text-fg antialiased">
    @yield('main')
</body>
</html>
