@php
    $trustItems = ['Free usage', 'No credit card', 'Set up in minutes'];

    $stats = [
        ['value' => '12', 'label' => 'To do', 'tone' => 'bg-indigo-50 text-indigo-600'],
        ['value' => '5', 'label' => 'In progress', 'tone' => 'bg-violet-50 text-violet-600'],
        ['value' => '28', 'label' => 'Done', 'tone' => 'bg-emerald-50 text-emerald-600'],
    ];

    $tasks = [
        ['title' => 'Finalize landing page concepts', 'meta' => 'Product Design', 'tag' => 'High', 'tagClass' => 'bg-rose-50 text-rose-600', 'due' => 'Today', 'checked' => false],
        ['title' => 'Review Q3 campaign brief', 'meta' => 'Marketing', 'tag' => 'Medium', 'tagClass' => 'bg-amber-50 text-amber-600', 'due' => 'Tomorrow', 'checked' => false],
        ['title' => 'Update onboarding workflow', 'meta' => 'Operations', 'tag' => 'Low', 'tagClass' => 'bg-emerald-50 text-emerald-600', 'due' => 'Sep 28', 'checked' => true],
    ];
@endphp

<section class="relative overflow-hidden pb-36 pt-40">
    <div aria-hidden="true" class="pointer-events-none absolute -left-48 top-28 h-96 w-96 rounded-full bg-indigo-200/50 blur-[64px]"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-40 top-48 h-[480px] w-[480px] rounded-full bg-violet-200/50 blur-[64px]"></div>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-x-0 top-0 h-[704px] opacity-50"
        style="background-image: linear-gradient(to right, rgba(79, 70, 229, 0.06) 1px, transparent 1px), linear-gradient(to bottom, rgba(79, 70, 229, 0.06) 1px, transparent 1px); background-size: 48px 48px; -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 0.5) 65%, rgba(0, 0, 0, 0) 100%); mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 0.5) 65%, rgba(0, 0, 0, 0) 100%);"
    ></div>

    <div class="mx-auto grid max-w-[1280px] gap-10 px-8 lg:grid-cols-[5fr_7fr] lg:items-center">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3.5 py-2 text-xs font-bold uppercase tracking-[0.12em] text-indigo-700">
                <x-icon name="sparkles" class="size-[14px]" />
                <span>Built for focused teams</span>
            </div>

            <h1 class="mt-7 text-5xl font-extrabold tracking-[-0.05em] text-slate-950 leading-[1.04] lg:text-[70px]">
                Turn big goals into
                <span class="bg-gradient-to-br from-indigo-600 to-violet-600 bg-clip-text text-transparent">
                    daily progress.
                </span>
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                Plan projects, assign work, and move forward together—all in one beautifully simple workspace.
            </p>

            <a href="{{ route('login') }}" class="mt-9 inline-flex h-[52px] items-center gap-2 rounded-xl bg-indigo-600 px-7 text-base font-semibold text-white shadow-xl shadow-indigo-600/25 transition hover:bg-indigo-700">
                <span>Start now</span>
                <x-icon name="arrow-right" class="size-[18px]" />
            </a>

            <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2">
                @foreach ($trustItems as $item)
                    <div class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <x-icon name="check" class="size-[14px] text-indigo-600" />
                        <span>{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative">
            <div class="rounded-[28px] border border-white/80 bg-white p-2 shadow-[0_32px_80px_-24px_rgba(79,70,229,0.35)]">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="block size-[10px] rounded-full bg-[#ffa1ad]"></span>
                            <span class="block size-[10px] rounded-full bg-[#ffd230]"></span>
                            <span class="block size-[10px] rounded-full bg-[#5ee9b5]"></span>
                        </div>
                        <div class="h-5 w-32 rounded-md bg-slate-100"></div>
                        <div class="flex size-7 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                            <x-icon name="plus" class="size-[14px]" />
                        </div>
                    </div>

                    <div class="grid md:grid-cols-[160px_minmax(0,1fr)]">
                        <aside class="hidden border-r border-slate-200 bg-white p-4 md:block">
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-600 text-white">
                                    <x-icon name="square-check" class="size-[18px]" />
                                </span>
                                <span class="text-sm font-bold text-slate-900">TaskFlow</span>
                            </div>

                            <nav class="mt-8 space-y-2">
                                @php
                                    $navItems = [
                                        ['label' => 'Overview', 'active' => false, 'icon' => 'layout-grid'],
                                        ['label' => 'My tasks', 'active' => true, 'icon' => 'inbox'],
                                        ['label' => 'Calendar', 'active' => false, 'icon' => 'calendar'],
                                        ['label' => 'Team', 'active' => false, 'icon' => 'users'],
                                    ];
                                @endphp

                                @foreach ($navItems as $item)
                                    <div class="flex items-center gap-2 rounded-lg px-2 py-2 text-xs font-semibold {{ $item['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-400' }}">
                                        <x-icon :name="$item['icon']" class="size-4" />
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                @endforeach
                            </nav>
                        </aside>

                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Tuesday, September 24</p>
                                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Good morning, Alex</h2>
                                </div>
                                <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20" aria-hidden="true">
                                    <x-icon name="plus" class="size-[18px]" />
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                @foreach ($stats as $stat)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <span class="inline-flex rounded-lg px-2 py-1 text-sm font-bold {{ $stat['tone'] }}">
                                            {{ $stat['value'] }}
                                        </span>
                                        <p class="mt-2 text-xs font-semibold text-slate-500">{{ $stat['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold text-slate-900">Today’s tasks</p>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-indigo-600">View all</p>
                                </div>

                                <div class="mt-2">
                                    @foreach ($tasks as $task)
                                        <div class="flex items-center gap-3 border-b border-slate-100 py-3 last:border-none">
                                            <div class="flex size-5 items-center justify-center rounded-md {{ $task['checked'] ? 'border border-indigo-600 bg-indigo-600 text-white' : 'border border-slate-300 bg-white' }}">
                                                @if ($task['checked'])
                                                    <x-icon name="check" class="size-3.5" />
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-semibold {{ $task['checked'] ? 'text-slate-400 line-through' : 'text-slate-800' }}">
                                                        {{ $task['title'] }}
                                                    </p>
                                                    <span class="rounded-md px-2 py-1 text-[9px] font-bold {{ $task['tagClass'] }}">
                                                        {{ $task['tag'] }}
                                                    </span>
                                                </div>
                                                <div class="mt-1 flex items-center gap-2 text-[10px] text-slate-400">
                                                    <span>{{ $task['meta'] }}</span>
                                                    <span>•</span>
                                                    <span>{{ $task['due'] }}</span>
                                                </div>
                                            </div>

                                            <x-icon name="ellipsis" class="size-4 text-slate-400" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pointer-events-none absolute -left-8 top-12 hidden lg:block">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-50">
                        <x-icon name="check" class="size-5 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Weekly progress</p>
                        <p class="text-sm font-bold text-slate-900">24 tasks completed</p>
                    </div>
                </div>
            </div>

            <div class="pointer-events-none absolute -bottom-5 -right-5 hidden lg:block">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/10">
                    <div class="flex -space-x-2">
                        <span class="flex size-9 items-center justify-center rounded-full border-2 border-white bg-indigo-100 text-[10px] font-bold text-indigo-700">AM</span>
                        <span class="flex size-9 items-center justify-center rounded-full border-2 border-white bg-violet-100 text-[10px] font-bold text-violet-700">JD</span>
                        <span class="flex size-9 items-center justify-center rounded-full border-2 border-white bg-amber-100 text-[10px] font-bold text-amber-700">SK</span>
                    </div>
                    <p class="text-xs font-bold text-slate-700">Your team is in sync</p>
                </div>
            </div>
        </div>
    </div>
</section>
