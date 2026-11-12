@extends('layouts.auth')

@section('title', 'Reset password · MomenTum')

@section('content')
    <main class="grid min-h-screen grid-cols-1 bg-slate-50 lg:grid-cols-2">
        <x-auth.brand-panel />
        <section class="flex items-center justify-center p-6 lg:p-10">
            <div class="w-full max-w-[448px] rounded-2xl border border-slate-200 bg-white p-9 shadow-[0_20px_30px_rgba(15,23,42,0.18)]">
                <p class="text-sm font-semibold text-indigo-600">Account recovery</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Forgot your password?</h1>
                <p class="mt-3 text-sm leading-6 text-slate-500">Enter your email and we’ll send a reset link if an account exists.</p>

                @if (session('status'))
                    <p role="status" class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="text-sm font-semibold text-slate-700">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            class="mt-2 block h-12 w-full rounded-xl border {{ $errors->has('email') ? 'border-red-300' : 'border-slate-200' }} bg-white px-4 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        >
                        @error('email')
                            <p role="alert" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="flex h-12 w-full items-center justify-center rounded-xl bg-indigo-600 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Send reset link
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Remembered it?
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Back to sign in</a>
                </p>
            </div>
        </section>
    </main>
@endsection
