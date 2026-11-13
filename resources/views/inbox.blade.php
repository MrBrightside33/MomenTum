@extends('layouts.app')

@section('title', 'Inbox · MomenTum')
@section('skeleton', 'inbox')

@section('content')
    <div class="space-y-6">
        {{-- Inbox heading and bulk action --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Workspace updates</p>
                <h1 class="mt-1 text-4xl font-bold tracking-tight text-slate-900">Inbox</h1>
                <p class="mt-2 text-slate-500">Stay on top of task assignments, mentions, and team activity.</p>
            </div>

            <button
                type="button"
                data-inbox-mark-all
                data-inbox-mark-all-url="{{ route('inbox.read-all') }}"
                class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 sm:self-auto"
            >
                <x-icon name="check" class="size-4 text-indigo-600" />
                Mark all as read
            </button>
        </section>

        {{-- Inbox notifications --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Inbox notifications">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                {{-- Search inbox updates --}}
                <label class="relative block w-full lg:max-w-sm">
                    <span class="sr-only">Search inbox</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        data-inbox-search
                        placeholder="Search updates"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </label>

                {{-- Inbox filters --}}
                <div class="flex w-full items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 text-sm sm:w-auto" role="group" aria-label="Filter inbox">
                    @foreach (['all' => 'All', 'unread' => 'Unread', 'archived' => 'Archived'] as $filter => $label)
                        <button
                            type="button"
                            data-inbox-filter="{{ $filter }}"
                            aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"
                            class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-sm font-medium transition {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}"
                        >
                            {{ $label }}
                            <span data-inbox-count="{{ $filter }}" class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">
                                {{ $inboxCounts[$filter] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div
                id="inbox-list"
                class="divide-y divide-slate-100"
                data-inbox-all-count="{{ $inboxCounts['all'] }}"
                data-inbox-unread-count="{{ $inboxCounts['unread'] }}"
                data-inbox-archived-count="{{ $inboxCounts['archived'] }}"
            >
                @foreach ($inboxGroups as $group => $groupNotifications)
                    <section data-inbox-group aria-label="{{ $group }} updates">
                        <h2 class="bg-slate-50/70 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ $group }}
                        </h2>

                        @foreach ($groupNotifications as $notification)
                            {{-- Single inbox notification --}}
                            <article
                                data-inbox-item
                                data-notification-id="{{ $notification['id'] }}"
                                data-read="{{ $notification['read'] ? 'true' : 'false' }}"
                                data-archived="{{ $notification['archived'] ? 'true' : 'false' }}"
                                data-search="{{ $notification['headline'] }} {{ $notification['detail'] }}"
                                class="flex gap-3 px-4 py-4 transition-colors sm:gap-4 sm:px-5 {{ $notification['read'] ? 'bg-white' : 'bg-indigo-50/30' }}"
                            >
                                {{-- Notification type --}}
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $notification['icon_color'] }}" aria-hidden="true">
                                    <x-icon :name="$notification['icon']" class="size-[18px]" />
                                </span>

                                {{-- Notification details --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-sm font-semibold text-slate-900">{{ $notification['headline'] }}</h3>
                                                <span data-inbox-unread-indicator class="size-2 shrink-0 rounded-full bg-indigo-600 {{ $notification['read'] ? 'hidden' : '' }}" aria-label="Unread"></span>
                                            </div>
                                            <p class="mt-1 text-sm leading-5 text-slate-600">{{ $notification['detail'] }}</p>
                                        </div>
                                        <time class="shrink-0 text-xs text-slate-400">{{ $notification['time'] }}</time>
                                    </div>

                                    {{-- Notification actions and context --}}
                                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                            <span class="flex size-4 items-center justify-center rounded-full text-[8px] font-bold {{ $notification['avatar'] }}">{{ $notification['initials'] }}</span>
                                            {{ $notification['actor'] }}
                                        </span>
                                        @if ($notification['task_id'])
                                            <a
                                                href="{{ $notification['url'] ?? (route('dashboard').'#task-row-'.$notification['task_id']) }}"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 focus:outline-none focus:underline"
                                            >
                                                View task
                                                <x-icon name="arrow-right" class="size-3.5" />
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                {{-- Mark-read and archive controls --}}
                                <div class="flex shrink-0 items-start gap-1">
                                    <button
                                        type="button"
                                        data-inbox-toggle-read
                                        data-read-url="{{ route('inbox.read', ['notification' => $notification['id']]) }}"
                                        aria-label="{{ $notification['read'] ? 'Mark notification as unread' : 'Mark notification as read' }}"
                                        title="{{ $notification['read'] ? 'Mark as unread' : 'Mark as read' }}"
                                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 {{ $notification['read'] ? 'bg-emerald-50 text-emerald-600' : '' }}"
                                    >
                                        <x-icon name="check" class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        data-inbox-toggle-archive
                                        data-archive-url="{{ route('inbox.archive', ['notification' => $notification['id']]) }}"
                                        data-restore-url="{{ route('inbox.restore', ['notification' => $notification['id']]) }}"
                                        aria-label="Archive notification"
                                        title="Archive"
                                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                    >
                                        <x-icon name="archive" class="size-4" data-inbox-archive-icon />
                                        <x-icon name="inbox" class="hidden size-4" data-inbox-restore-icon />
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endforeach
            </div>

            <div class="px-5 py-4">
                {{ $notifications->links() }}
            </div>

            {{-- Empty search and filter state --}}
            <div data-inbox-empty class="hidden flex-col items-center px-6 py-14 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <x-icon name="inbox" class="size-6" />
                </span>
                <h2 data-inbox-empty-title class="mt-4 text-base font-semibold text-slate-900">No updates found</h2>
                <p data-inbox-empty-description class="mt-1 max-w-sm text-sm text-slate-500">Try another search or filter.</p>
            </div>
        </section>

        <p data-inbox-status class="sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
@endsection
