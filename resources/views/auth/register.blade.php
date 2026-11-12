@extends('layouts.auth')

@section('title', 'Sign up · MomenTum')

@section('content')
    {{-- Registration page columns --}}
    <main class="grid min-h-screen grid-cols-1 bg-slate-50 lg:grid-cols-2">
        <x-auth.brand-panel />

        {{-- Sign-up form column --}}
        <section class="flex items-center justify-center p-6 lg:p-10">
            <div class="w-full max-w-[448px]">
                {{-- Mobile brand --}}
                <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <x-icon name="square-check" class="size-[22px]" />
                    </span>
                    <span class="text-xl font-bold text-slate-900">MomenTum</span>
                </a>

                {{-- Sign-up card --}}
                <div class="w-full rounded-2xl border border-slate-200 bg-white px-9 pb-9 pt-16 shadow-[0_20px_30px_rgba(15,23,42,0.18)]">
                    {{-- Form heading --}}
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Sign Up</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Sign in to manage your tasks and stay productive.</p>
                    </div>

                    {{-- Account details --}}
                    <form method="POST" action="{{ route('register.submit') }}" class="mt-8 space-y-3">
                        @csrf
                        <div>
                            <label for="name" class="text-sm font-semibold text-slate-700">Full name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                autocomplete="name"
                                value="{{ old('name') }}"
                                placeholder="Your name"
                                class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('name') ? 'border-red-300' : 'border-slate-200' }} bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                            @error('name')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="register-email" class="text-sm font-semibold text-slate-700">Email address</label>
                            <input
                                id="register-email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="{{ old('email') }}"
                                placeholder="you@gmail.com"
                                class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('email') ? 'border-red-300' : 'border-slate-200' }} bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                            @error('email')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="register-password" class="text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative mt-2">
                                <input
                                    id="register-password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Enter your password"
                                    class="block h-12 w-full rounded-xl border {{ $errors->has('password') ? 'border-red-300' : 'border-slate-200' }} bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="register-password"
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

                        <div>
                            <label for="confirm-password" class="text-sm font-semibold text-slate-700">Confirm Password</label>
                            <div class="relative mt-2">
                                <input
                                    id="confirm-password"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Enter your password"
                                    class="block h-12 w-full rounded-xl border border-slate-200 bg-white pl-4 pr-11 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="confirm-password"
                                    aria-label="Show password"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                >
                                    <x-icon name="eye" class="size-[18px]" />
                                    <x-icon name="eye-off" class="hidden size-[18px]" />
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Use at least 8 characters.</p>

                        {{-- Sign-up action --}}
                        <button type="submit" class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span>Sign up</span>
                            <x-icon name="chevron-right" class="size-[18px]" />
                        </button>
                    </form>

                    {{-- Return to login --}}
                    <div class="mt-7 border-t border-slate-100 pt-6 text-center text-sm text-slate-500">
                        Go back to login?
                        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Login</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    {{-- Show or hide either password field --}}
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const passwordInput = document.getElementById(toggle.dataset.passwordToggle);
            const passwordIcons = toggle.querySelectorAll('svg');

            toggle.addEventListener('click', () => {
                const isPasswordVisible = passwordInput.type === 'text';

                passwordInput.type = isPasswordVisible ? 'password' : 'text';
                passwordIcons[0].classList.toggle('hidden', !isPasswordVisible);
                passwordIcons[1].classList.toggle('hidden', isPasswordVisible);
                toggle.setAttribute('aria-label', isPasswordVisible ? 'Show password' : 'Hide password');
            });
        });
    </script>
@endpush
