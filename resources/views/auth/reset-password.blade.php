@extends('layouts.auth')

@section('title', 'Choose a new password · MomenTum')

@section('content')
    <main class="grid min-h-screen grid-cols-1 bg-slate-50 lg:grid-cols-2">
        <x-auth.brand-panel />
        <section class="flex items-center justify-center p-6 lg:p-10">
            <div class="w-full max-w-[448px] rounded-2xl border border-slate-200 bg-white p-9 shadow-[0_20px_30px_rgba(15,23,42,0.18)]">
                <p class="text-sm font-semibold text-indigo-600">Account recovery</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Choose a new password</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">Use a new password with at least eight characters.</p>

                <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label for="email" class="text-sm font-semibold text-slate-700">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('email') ? 'border-red-300' : 'border-slate-200' }} bg-white px-4 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        >
                        @error('email')
                            <p role="alert" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="text-sm font-semibold text-slate-700">New password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('password') ? 'border-red-300' : 'border-slate-200' }} bg-white px-4 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        >
                        @error('password')
                            <p role="alert" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-sm font-semibold text-slate-700">Confirm new password</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        >
                    </div>
                    <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-indigo-600 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Reset password
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Back to sign in</a>
                </p>
            </div>
        </section>
    </main>
@endsection
