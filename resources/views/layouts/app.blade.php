<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LaraCashbook') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @if (config('app.demo'))
                <div class="bg-indigo-600 text-white text-sm text-center px-4 py-1.5">
                    {{ __('This is a demo: data are reset every night.') }}
                </div>
            @endif

            <!-- Page Heading -->
            @if (isset($header))
                <header class="max-w-7xl mx-auto pt-6 pb-2 px-4 sm:px-6 lg:px-8 flex flex-wrap items-center gap-x-4 gap-y-3">
                    {{ $header }}
                </header>
            @endif

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 pb-12">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
