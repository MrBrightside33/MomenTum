<x-modal id="project-modal" title="Project details" subtitle="Everything about this project in one place." maxWidth="max-w-[1100px]" gap="gap-6">
    <x-slot:headerActions>
        <div class="flex shrink-0 items-center gap-1">
            <button type="button" data-pm-copy-link aria-label="Copy project link" title="Copy project link" class="flex size-11 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                <x-icon name="link" class="size-5" />
            </button>
            <button type="button" data-modal-close aria-label="Close project details" class="flex size-11 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
    </x-slot:headerActions>

    <div data-project-modal data-progress-stages='@json(config("progress.stages"))' class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-8">
        <div class="min-w-0 space-y-6">
            <section class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <x-icon name="sparkles" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 data-pm="title" class="break-words text-2xl font-bold tracking-tight text-slate-900"></h3>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span data-pm="priority" class="rounded-full px-2.5 py-1 text-[11px] font-semibold"></span>
                            <span data-pm="status" class="rounded-full px-2.5 py-1 text-[11px] font-semibold"></span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">From project <span data-pm="project-name" class="font-semibold text-slate-700"></span></p>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-800">Description</h4>
                    <p data-pm="description" class="mt-1 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600"></p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="project-progress-heading">
                <div class="flex items-start justify-between gap-4">
                    <h4 id="project-progress-heading" class="text-base font-bold text-slate-900">Project progress</h4>
                    <div class="text-right">
                        <p data-pm="percent" class="text-2xl font-bold tabular-nums text-slate-900">0%</p>
                        <p data-pm="stage-label" class="text-xs font-medium text-slate-500">Not started</p>
                    </div>
                </div>

                <div data-pm-radiogroup role="radiogroup" aria-label="Project progress stage" class="mt-5">
                    <div data-pm-track class="relative">
                        <div class="absolute inset-x-5 top-[22px] h-2 rounded-full bg-slate-200">
                            <div data-pm-progress-fill class="h-full rounded-full bg-indigo-500 transition-[width] duration-300" style="width: 0%"></div>
                        </div>
                        <div class="relative flex justify-between">
                            @foreach (config('progress.stages') as $stage)
                                <button
                                    type="button"
                                    role="radio"
                                    data-pm-stage="{{ $stage['value'] }}"
                                    aria-label="{{ $stage['label'] }}, {{ $stage['value'] }} percent"
                                    aria-checked="false"
                                    aria-disabled="true"
                                    tabindex="{{ $stage['value'] === 0 ? '0' : '-1' }}"
                                    class="group flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-lg text-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                >
                                    <span data-pm-stage-node class="flex size-11 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-slate-500 transition">
                                        <x-icon name="check" data-pm-node-check class="hidden size-4" />
                                    </span>
                                    <span class="text-[11px] font-semibold text-slate-600">{{ $stage['value'] }}%</span>
                                    <span data-pm-stage-label class="hidden text-[11px] font-medium text-slate-500 min-[480px]:inline">{{ $stage['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <button type="button" data-pm-back-stage class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                            <x-icon name="chevron-left" class="size-4" />
                            Back a stage
                        </button>
                        <button type="button" data-pm-next-stage class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                            Next stage
                            <x-icon name="chevron-right" class="size-4" />
                        </button>
                    </div>
                    <p data-pm-updated class="text-xs text-slate-500"></p>
                </div>
                <p data-pm-helper class="mt-3 text-xs leading-5 text-slate-500">Click a stage to update your progress. Completing the task on the dashboard sets it to 100% automatically.</p>
                <p data-pm-locked class="mt-3 hidden items-center gap-2 text-xs font-medium text-slate-500">
                    <x-icon name="lock" class="size-4" />
                    Only collaborators can update progress.
                </p>
                <p data-pm-live class="sr-only" aria-live="polite" role="status"></p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="project-activity-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h4 id="project-activity-heading" class="text-base font-bold text-slate-900">Activity</h4>
                    <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-xs" role="group" aria-label="Filter activity">
                        <button type="button" data-pm-activity-filter="all" aria-pressed="true" class="rounded-md bg-white px-3 py-1.5 font-semibold text-slate-900 shadow-sm">All</button>
                        <button type="button" data-pm-activity-filter="progress" aria-pressed="false" class="rounded-md px-3 py-1.5 font-semibold text-slate-500">Progress</button>
                        <button type="button" data-pm-activity-filter="comment" aria-pressed="false" class="rounded-md px-3 py-1.5 font-semibold text-slate-500">Comments</button>
                    </div>
                </div>
                <div data-pm-activity-list class="mt-4 space-y-4"></div>
                <p data-pm-no-activity class="mt-4 hidden text-sm text-slate-500">No activity yet.</p>
                <template data-pm-activity-template>
                    <article data-pm-activity-row class="flex gap-3">
                        <span data-pm-activity-avatar class="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-1.5">
                                <x-icon name="chart-column" data-pm-activity-icon class="mt-0.5 hidden size-4 shrink-0 text-indigo-500" />
                                <p class="break-words text-sm leading-5 text-slate-700"><span data-pm-activity-name class="font-semibold text-slate-900"></span> <span data-pm-activity-text></span></p>
                            </div>
                            <time data-pm-activity-time class="mt-1 block text-xs text-slate-400"></time>
                        </div>
                    </article>
                </template>
                <form data-pm-comment-form class="mt-5 flex items-end gap-2 border-t border-slate-100 pt-4">
                    <label class="min-w-0 flex-1">
                        <span class="sr-only">Add a comment</span>
                        <textarea data-pm-comment-input maxlength="300" rows="2" placeholder="Write a comment…" class="min-h-11 w-full resize-y rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </label>
                    <button type="submit" data-pm-comment-send disabled class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Send comment">
                        <x-icon name="send" class="size-4" />
                    </button>
                </form>
            </section>
        </div>

        <aside class="min-w-0 space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h4 class="text-sm font-bold text-slate-900">Details</h4>
                <dl class="mt-4 space-y-4">
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Status</dt><dd data-pm-detail="status" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Priority</dt><dd data-pm-detail="priority" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Due date</dt><dd data-pm-detail="due" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">From project</dt><dd data-pm-detail="project" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Created by</dt><dd data-pm-detail="creator" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Created on</dt><dd data-pm-detail="created" class="text-right font-semibold text-slate-800"></dd></div>
                    <div class="flex items-start justify-between gap-3 text-sm"><dt class="text-slate-500">Last updated</dt><dd data-pm-detail="updated" class="text-right font-semibold text-slate-800"></dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-sm font-bold text-slate-900">People working on this</h4>
                    <span data-pm-people-count class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600"></span>
                </div>
                <div data-pm-people class="mt-4 space-y-3"></div>
                <p data-pm-one-person class="mt-3 hidden text-xs text-slate-500">One-person project</p>
                <template data-pm-person-template>
                    <div data-pm-person class="flex items-center gap-3">
                        <span data-pm-person-avatar class="flex size-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800"><span data-pm-person-name></span> <span data-pm-person-me class="hidden rounded-full bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">You</span></p>
                            <p data-pm-person-role class="text-xs text-slate-500"></p>
                        </div>
                    </div>
                </template>
            </section>

            <div class="flex flex-wrap gap-2">
                <button type="button" data-pm-edit class="hidden min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <x-icon name="pencil" class="size-4" />
                    Edit
                </button>
                <button type="button" data-pm-status class="hidden min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"></button>
            </div>
        </aside>
    </div>
</x-modal>
