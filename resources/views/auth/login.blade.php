@extends('layouts.auth')

@section('title', 'Sign in · MomenTum')

@section('content')
    {{-- Login page columns --}}
    <main class="grid min-h-screen grid-cols-1 bg-slate-50 lg:grid-cols-2">
        <x-auth.brand-panel />

        {{-- Sign-in form column --}}
        <section class="flex items-center justify-center p-6 lg:p-10">
            <div class="w-full max-w-[448px]">
                {{-- Mobile brand --}}
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <x-icon name="square-check" class="size-[22px]" />
                    </span>
                    <span class="text-xl font-bold text-slate-900">MomenTum</span>
                </a>

                {{-- Login card --}}
                <div class="w-full rounded-2xl border border-slate-200 bg-white p-9 shadow-[0_20px_30px_rgba(15,23,42,0.18)]">
                    {{-- Form heading --}}
                    <div>
                        <p class="text-sm font-semibold text-indigo-600">Your workspace awaits</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Welcome back</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">Sign in to manage your tasks and stay productive.</p>
                    </div>

                    {{-- Credentials form --}}
                    <form method="POST" action="{{ route('login.submit') }}" class="mt-8" novalidate>
                        @csrf

                        {{-- Email field --}}
                        <div>
                            <label for="email" class="text-sm font-semibold text-slate-700">Email address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="{{ old('email') }}"
                                placeholder="you@example.com"
                                class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('email') ? 'border-red-300' : 'border-slate-200' }} bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                            @error('email')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password field with visibility toggle --}}
                        <div class="mt-5">
                            <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative mt-2">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    class="block h-12 w-full rounded-xl border {{ $errors->has('password') ? 'border-red-300' : 'border-slate-200' }} bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                <button
                                    type="button"
                                    id="toggle-password"
                                    aria-label="Show password"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                >
                                    <x-icon name="eye" class="size-[18px]" />
                                    <x-icon name="eye-off" class="hidden size-[18px]" />
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember and recovery options --}}
                        <div class="mt-5 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remember" class="size-4 rounded accent-indigo-600">
                                <span>Remember me</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-700">Forgot password?</a>
                        </div>

                        {{-- Submit button --}}
                        <button type="submit" class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span>Sign in</span>
                            <x-icon name="chevron-right" class="size-[18px]" />
                        </button>
                    </form>

                    {{-- Registration link --}}
                    <div class="mt-7 border-t border-slate-100 pt-6 text-center text-sm text-slate-500">
                        New to MomenTum?
                        <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Create an account</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('toggle-password');
        const passwordIcons = passwordToggle.querySelectorAll('svg');

        passwordToggle.addEventListener('click', () => {
            const isPasswordVisible = passwordInput.type === 'text';

            passwordInput.type = isPasswordVisible ? 'password' : 'text';
            passwordIcons[0].classList.toggle('hidden', !isPasswordVisible);
            passwordIcons[1].classList.toggle('hidden', isPasswordVisible);
            passwordToggle.setAttribute('aria-label', isPasswordVisible ? 'Show password' : 'Hide password');
        });
    </script>
@endpush
