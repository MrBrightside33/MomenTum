<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings['appearance'] ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-timezone" content="{{ auth()->user()->settings['timezone'] ?? config('app.timezone') }}">
    <meta name="today" content="{{ now(auth()->user()->settings['timezone'] ?? config('app.timezone'))->toDateString() }}">
    <meta name="current-user-id" content="{{ auth()->id() }}">
    <title>@yield('title', 'Administration · MomenTum')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    @include('layouts.partials.admin-sidebar')

    <div
        data-admin-backdrop
        class="fixed inset-0 z-20 hidden bg-slate-950/30 lg:hidden"
        aria-hidden="true"
    ></div>

    <div class="min-h-screen lg:pl-64">
        @include('layouts.partials.admin-header')

        <main class="mx-auto max-w-[1280px] p-4 sm:p-8">
            @yield('content')
        </main>
    </div>

    <x-toast-container />
    @stack('scripts')
</body>
</html>
