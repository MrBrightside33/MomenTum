<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings['appearance'] ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="layout" content="app">
    <meta name="app-env" content="{{ app()->environment() }}">
    <title>@yield('title', 'MomenTum')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <div id="nav-progress" class="fixed inset-x-0 top-0 z-[70] hidden h-0.5 bg-indigo-600" aria-hidden="true">
        <span class="block h-full w-full origin-left bg-indigo-300"></span>
    </div>
    <p id="live-region" class="sr-only" aria-live="polite" aria-atomic="true"></p>

    @include('layouts.partials.sidebar')

    {{-- pl-64 leaves room for the 256px fixed sidebar --}}
    <div class="min-h-screen lg:pl-64">
        @include('layouts.partials.header')

        <main id="page-main" data-skeleton="@yield('skeleton', 'generic-page')" class="mx-auto max-w-[1280px] p-8">
            @yield('content')
        </main>
    </div>

    @include('tasks.create-modal')
    @include('tasks.delete-modal')
    @include('tasks.success-modal')
    @include('skeletons.templates')
</body>
</html>
