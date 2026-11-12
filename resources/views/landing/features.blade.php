@php
    $features = [
        ['title' => 'Effortless task planning', 'description' => 'Capture, prioritize, and organize work with flexible lists that make the next step obvious.', 'icon' => 'list-checks'],
        ['title' => 'Team Collaboration', 'description' => 'Set up weekly or monthly routines once and let them show up on their own.', 'icon' => 'calendar-clock'],
        ['title' => 'Progress at a glance', 'description' => 'Spot momentum and blockers with simple dashboards built for quick, confident decisions.', 'icon' => 'chart-column'],
        ['title' => 'Deadlines that work', 'description' => 'Plan realistic due dates and see every commitment in one shared calendar.', 'icon' => 'calendar'],
        ['title' => 'Roles & permissions', 'description' => 'Give admins, managers, and members exactly the access they need as your workspace grows.', 'icon' => 'shield-check'],
        ['title' => 'Fast by default', 'description' => 'A focused interface and smart shortcuts keep you in flow, not fighting the tool.', 'icon' => 'zap'],
    ];
@endphp

<section class="py-32">
    <div class="mx-auto max-w-[1280px] px-8">
        <div class="mx-auto max-w-2xl text-center">
            <div class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50 px-3.5 py-2 text-xs font-bold uppercase tracking-[0.12em] text-indigo-700">
                Everything in one place
            </div>
            <h2 class="mt-6 text-4xl font-extrabold tracking-[-0.04em] text-slate-950 md:text-5xl">Less managing. More doing.</h2>
            <p class="mt-5 text-base leading-7 text-slate-500">
                Powerful enough for everything on your plate, simple enough to feel effortless from day one.
            </p>
        </div>

        <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $feature)
                <article class="rounded-3xl border border-slate-200 bg-white p-7 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <x-icon :name="$feature['icon']" class="size-[23px]" />
                    </div>
                    <h3 class="mt-6 text-lg font-bold text-slate-950">{{ $feature['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
