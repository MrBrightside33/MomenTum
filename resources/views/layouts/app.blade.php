<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings['appearance'] ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MomenTum')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">

    @include('layouts.partials.sidebar')

    {{-- pl-64 leaves room for the 256px fixed sidebar --}}
    <div class="min-h-screen lg:pl-64">
        @include('layouts.partials.header')

        <main class="mx-auto max-w-[1280px] p-8">
            @yield('content')
        </main>
    </div>

    @include('tasks.create-modal')
    @include('tasks.delete-modal')
    @include('tasks.success-modal')
</body>
</html>
