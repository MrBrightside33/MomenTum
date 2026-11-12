@extends('layouts.app')

@section('title', 'Settings · MomenTum')

@section('content')
    <div data-settings-root class="space-y-6">
        <section>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Account</p>
            <h1 class="mt-1 text-4xl font-bold tracking-tight text-slate-900">Settings</h1>
            <p class="mt-2 text-slate-500">Manage your account, workspace preferences, and application settings.</p>
        </section>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Please review the highlighted fields and try again.
            </div>
        @endif

        <nav class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Settings sections">
            @foreach (['profile' => 'Profile', 'security' => 'Account & security', 'workspace' => 'Workspace', 'appearance' => 'Appearance', 'notifications' => 'Notifications', 'danger-zone' => 'Danger zone'] as $sectionId => $sectionLabel)
                <a href="#{{ $sectionId }}" class="shrink-0 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    {{ $sectionLabel }}
                </a>
            @endforeach
        </nav>

        <section id="profile" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="profile-heading">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 id="profile-heading" class="text-lg font-bold text-slate-900">Profile settings</h2>
                <p class="mt-1 text-sm text-slate-500">Update how your name and profile appear in the workspace.</p>
            </div>

            <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="space-y-6 p-5 sm:p-6" data-settings-form>
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-indigo-50 text-xl font-bold text-indigo-700">
                        <img
                            @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
                            alt=""
                            data-avatar-preview
                            data-initial-avatar="{{ $avatarUrl ?? '' }}"
                            class="{{ $avatarUrl ? 'block' : 'hidden' }} size-full object-cover"
                        >
                        <span data-avatar-fallback class="{{ $avatarUrl ? 'hidden' : '' }}">{{ $initials }}</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp" class="sr-only" data-avatar-input>
                            <button type="button" data-avatar-select class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                <x-icon name="pencil" class="size-4 text-indigo-600" />
                                Change photo
                            </button>
                            <span data-avatar-name class="text-xs text-slate-500">JPG, PNG, or WebP up to 2 MB</span>
                        </div>
                        @error('avatar')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="profile-name" class="text-sm font-semibold text-slate-700">Full name</label>
                        <input id="profile-name" name="name" type="text" required maxlength="120" autocomplete="name" value="{{ old('name', $user->name) }}" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('name') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="profile-email" class="text-sm font-semibold text-slate-700">Email address</label>
                        <input id="profile-email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ old('email', $user->email) }}" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('email') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="job-title" class="text-sm font-semibold text-slate-700">Job title or role</label>
                        <input id="job-title" name="job_title" type="text" maxlength="120" autocomplete="organization-title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Product designer" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('job_title') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @error('job_title')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="bio" class="text-sm font-semibold text-slate-700">Profile description <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="bio" name="bio" rows="3" maxlength="500" class="mt-2 block w-full rounded-xl border {{ $errors->has('bio') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="A short introduction about you">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <button type="reset" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">Cancel</button>
                    <button type="submit" data-saving-button data-loading-label="Saving profile…" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:cursor-wait disabled:opacity-70">Save profile changes</button>
                </div>
            </form>
        </section>

        <section id="security" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="security-heading">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 id="security-heading" class="text-lg font-bold text-slate-900">Account &amp; security</h2>
                <p class="mt-1 text-sm text-slate-500">Change your password or sign out of this account.</p>
            </div>

            <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-5 p-5 sm:p-6" data-settings-form>
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ([
                        'current_password' => ['Current password', 'current-password'],
                        'password' => ['New password', 'new-password'],
                        'password_confirmation' => ['Confirm new password', 'new-password'],
                    ] as $field => [$label, $autocomplete])
                        <div class="{{ $field === 'current_password' ? 'md:col-span-2 md:max-w-md' : '' }}">
                            <label for="{{ $field }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                            <div class="relative mt-2">
                                <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}" {{ $field !== 'current_password' ? 'minlength=8' : '' }} class="block h-11 w-full rounded-xl border {{ $errors->has($field) ? 'border-red-300' : 'border-slate-200' }} bg-white pl-3.5 pr-12 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" data-password-field>
                                <button type="button" data-password-toggle="{{ $field }}" aria-label="Show {{ strtolower($label) }}" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                    <x-icon name="eye" class="size-[18px]" />
                                    <x-icon name="eye-off" class="hidden size-[18px]" />
                                </button>
                            </div>
                            @error($field)
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
                <p data-password-feedback class="text-sm text-slate-500" aria-live="polite">Use at least 8 characters. Your other active sessions will be signed out when you change your password.</p>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <button type="reset" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">Cancel</button>
                    <button type="submit" data-saving-button data-loading-label="Updating password…" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:cursor-wait disabled:opacity-70">Change password</button>
                </div>
            </form>

            <div class="flex flex-col gap-4 border-t border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Sign out</h3>
                    <p class="mt-1 text-sm text-slate-500">End your current session on this device.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                        <x-icon name="log-out" class="size-4" />
                        Sign out
                    </button>
                </form>
            </div>
        </section>

        <form method="POST" action="{{ route('settings.preferences.update') }}" class="space-y-6" data-settings-form data-preferences-form>
            @csrf
            @method('PUT')

            <section id="workspace" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="workspace-heading">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 id="workspace-heading" class="text-lg font-bold text-slate-900">Workspace preferences</h2>
                    <p class="mt-1 text-sm text-slate-500">These preferences are saved to your account.</p>
                </div>

                <div class="grid gap-5 p-5 md:grid-cols-2 sm:p-6">
                    <div>
                        <label for="workspace-name" class="text-sm font-semibold text-slate-700">Workspace name</label>
                        <input id="workspace-name" name="workspace_name" type="text" required maxlength="80" value="{{ old('workspace_name', $settings['workspace_name']) }}" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('workspace_name') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @error('workspace_name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="default-page" class="text-sm font-semibold text-slate-700">Default landing page</label>
                        <select id="default-page" name="default_page" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('default_page') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($defaultPages as $value => $label)
                                <option value="{{ $value }}" @selected(old('default_page', $settings['default_page']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('default_page')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="task-view" class="text-sm font-semibold text-slate-700">Default task view</label>
                        <select id="task-view" name="default_task_view" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('default_task_view') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($taskViews as $value => $label)
                                <option value="{{ $value }}" @selected(old('default_task_view', $settings['default_task_view']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('default_task_view')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="timezone" class="text-sm font-semibold text-slate-700">Time zone</label>
                        <select id="timezone" name="timezone" class="mt-2 block h-11 w-full rounded-xl border {{ $errors->has('timezone') ? 'border-red-300' : 'border-slate-200' }} bg-white px-3.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($timezones as $value => $label)
                                <option value="{{ $value }}" @selected(old('timezone', $settings['timezone']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-sm text-slate-500 md:col-span-2">English is the only language currently configured for this application.</p>
                </div>
            </section>

            <section id="appearance" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="appearance-heading">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 id="appearance-heading" class="text-lg font-bold text-slate-900">Appearance</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose how MomenTum looks across your workspace.</p>
                </div>

                <fieldset class="grid gap-3 p-5 sm:grid-cols-3 sm:p-6">
                    <legend class="sr-only">Color theme</legend>
                    @foreach ($appearanceModes as $value => $label)
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="appearance" value="{{ $value }}" @checked(old('appearance', $settings['appearance']) === $value) class="size-4 accent-indigo-600" data-appearance-option>
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>
                @error('appearance')
                    <p class="px-6 pb-5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section id="notifications" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="notifications-heading">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 id="notifications-heading" class="text-lg font-bold text-slate-900">Notification preferences</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose which workspace updates you want to receive.</p>
                </div>

                <div class="divide-y divide-slate-100 px-5 sm:px-6">
                    @foreach ($notificationOptions as $key => $label)
                        <label class="flex min-h-14 items-center justify-between gap-4 py-3">
                            <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="hidden" name="notifications[{{ $key }}]" value="0">
                                <input
                                    type="checkbox"
                                    name="notifications[{{ $key }}]"
                                    value="1"
                                    @checked((bool) old("notifications.{$key}", $settings['notifications'][$key] ?? true))
                                    class="peer sr-only"
                                >
                                <span aria-hidden="true" class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-indigo-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-600"></span>
                                <span aria-hidden="true" class="pointer-events-none absolute left-1 size-4 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5"></span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @foreach ($notificationOptions as $key => $label)
                    @error("notifications.{$key}")
                        <p class="px-6 pb-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endforeach
                @error('notifications')
                    <p class="px-6 pb-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 p-5 sm:flex-row sm:justify-end sm:px-6">
                    <button type="reset" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">Cancel</button>
                    <button type="submit" data-saving-button data-loading-label="Saving preferences…" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 disabled:cursor-wait disabled:opacity-70">Save preferences</button>
                </div>
            </section>
        </form>

        <section id="danger-zone" class="scroll-mt-24 overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm" aria-labelledby="danger-heading">
            <div class="border-b border-red-100 bg-red-50/70 px-5 py-4 sm:px-6">
                <h2 id="danger-heading" class="text-lg font-bold text-red-800">Danger zone</h2>
                <p class="mt-1 text-sm text-red-700">Destructive account actions are not enabled for this application.</p>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Account deletion</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">There is no account deletion workflow configured, so your account cannot be deleted here.</p>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    <h3 class="text-sm font-semibold text-slate-900">Workspace deletion</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Workspace preferences are personal to your account; shared workspaces and workspace deletion are not supported.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
