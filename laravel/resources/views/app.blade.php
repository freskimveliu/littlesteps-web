<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Little Steps') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" href="/icon-192.png" sizes="192x192">
        <link rel="icon" type="image/png" href="/icon-512.png" sizes="512x512">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="h-full bg-zinc-50 font-sans antialiased dark:bg-zinc-950">
        @inertia
    </body>
</html>
