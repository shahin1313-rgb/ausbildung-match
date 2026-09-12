<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="جست‌وجو و تطبیق فرصت‌های آوسبیلدونگ آلمان برای فارسی‌زبانان">
    <title>{{ config('app.name', 'Ausbildung Match') }}</title>
    @if (app()->environment('local') && config('frontend.dev_server_url'))
        <script type="module" src="{{ config('frontend.dev_server_url') }}/@vite/client"></script>
        <script type="module" src="{{ config('frontend.dev_server_url') }}/resources/js/main.tsx"></script>
    @else
        <link rel="stylesheet" href="/build/assets/app.css">
        <script type="module" src="/build/assets/app.js"></script>
    @endif
</head>
<body>
    <div id="app"></div>
    <noscript>برای استفاده از این سامانه باید JavaScript مرورگر فعال باشد.</noscript>
</body>
</html>
