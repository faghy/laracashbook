<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LaraCashbook') }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="icon" href="{{ asset('images/laracashbook-icon-192.png') }}" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">

        <!-- Scripts -->
        @vite('resources/css/app.css')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 bg-linear-to-br from-indigo-700 via-indigo-600 to-violet-600">
            <a href="/" class="flex items-center gap-3 text-3xl font-bold text-white tracking-tight">
                <x-application-logo class="h-10 w-auto" />
                <span>{{ config('app.name') }}</span>
            </a>

            <div class="card w-full max-w-md mt-6 px-6 py-6 sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
