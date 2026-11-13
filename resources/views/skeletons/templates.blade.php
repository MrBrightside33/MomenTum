@if (request()->routeIs('admin.overview'))
    <div class="hidden" aria-hidden="true">
        <template id="sk-admin-overview-data">
            <div class="space-y-6" aria-busy="true">
                <x-sk.stat-cards />
                <x-sk.table columns="2" rows="5" />
            </div>
        </template>
    </div>
@elseif (request()->routeIs('admin.reports.index'))
    <div class="hidden" aria-hidden="true">
        <template id="sk-admin-reports">
            <div class="space-y-6" aria-busy="true">
                <x-sk.stat-cards />
                <x-sk.report-charts />
            </div>
        </template>
    </div>
@elseif (! request()->routeIs('admin.*'))
    <div class="hidden" aria-hidden="true">
    <template id="sk-line"><x-sk.line /></template>
    <template id="sk-circle"><x-sk.circle /></template>
    <template id="sk-block"><x-sk.block /></template>
    <template id="sk-avatar-line"><x-sk.avatar-line /></template>
    <template id="sk-stat-cards"><x-sk.stat-cards /></template>
    <template id="sk-task-row"><x-sk.task-row /></template>
    <template id="sk-task-list"><x-sk.task-list count="10" /></template>
    <template id="sk-weekly-focus"><x-sk.weekly-focus /></template>
    <template id="sk-streak-card"><x-sk.streak-card /></template>
    <template id="sk-project-card"><x-sk.project-card /></template>
    <template id="sk-projects-grid"><x-sk.projects-grid count="10" /></template>
    <template id="sk-tabs"><x-sk.tabs /></template>
    <template id="sk-table"><x-sk.table columns="5" rows="10" /></template>
    <template id="sk-members-table"><x-sk.members-table rows="10" /></template>
    <template id="sk-tasks-table"><x-sk.tasks-table rows="10" /></template>
    <template id="sk-audit-table"><x-sk.audit-table rows="10" /></template>
    <template id="sk-projects-table"><x-sk.projects-table rows="10" /></template>
    <template id="sk-modal-project"><x-sk.modal-project /></template>
    <template id="sk-modal-task-details"><x-sk.modal-task-details /></template>
    <template id="sk-modal-user-details"><x-sk.modal-user-details /></template>
    <template id="sk-modal-form"><x-sk.modal-form /></template>
    <template id="sk-inbox-list"><x-sk.inbox-list count="10" /></template>
    <template id="sk-calendar-grid"><x-sk.calendar-grid /></template>
    <template id="sk-report-charts"><x-sk.report-charts /></template>
    <template id="sk-settings-form"><x-sk.settings-form /></template>
    <template id="sk-search-results"><x-sk.search-results count="10" /></template>
    <template id="sk-page-header"><x-sk.page-header /></template>
    <template id="sk-generic-page"><x-sk.generic-page /></template>

    <template id="sk-dashboard">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.stat-cards />
            <x-sk.task-list count="10" />
            <x-sk.weekly-focus />
            <x-sk.streak-card />
        </div>
    </template>
    <template id="sk-projects">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.stat-cards />
            <x-sk.tabs />
            <x-sk.projects-grid count="10" />
        </div>
    </template>
    <template id="sk-calendar">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.calendar-grid />
        </div>
    </template>
    <template id="sk-inbox">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.inbox-list count="10" />
        </div>
    </template>
    <template id="sk-settings">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.settings-form />
        </div>
    </template>
    <template id="sk-team">
        <div class="space-y-6" aria-busy="true">
            <x-sk.page-header />
            <x-sk.stat-cards />
            <x-sk.projects-grid count="6" />
        </div>
    </template>
    </div>
@endif
