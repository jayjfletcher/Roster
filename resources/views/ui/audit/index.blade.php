@inject('directory', 'JayI\Roster\Support\Users')

<x-atrium::layout :title="__('roster::roster.audit_log')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.audit_log')" />

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            <form method="GET" action="{{ route('atrium.roster.audit.index') }}" class="flex flex-wrap items-start gap-3">
                <x-atrium::form.input name="action" :label="__('roster::roster.action')" :value="$filters['action'] ?? null" :hint="__('roster::roster.audit_action_hint')" wrapper="w-56" />
                <x-atrium::form.input name="organization" :label="__('roster::roster.organization')" :value="$filters['organization'] ?? null" wrapper="w-48" />
                <x-atrium::form.input name="user" :label="__('roster::roster.user')" :value="$filters['user'] ?? null" :hint="__('roster::roster.user_key_hint')" wrapper="w-40" />
                <x-atrium::form.select
                    name="source"
                    :label="__('roster::roster.source_label')"
                    :placeholder="__('roster::roster.all_sources')"
                    :options="['roster' => __('roster::roster.source_roster'), 'app' => __('roster::roster.source_app')]"
                    :selected="$filters['source'] ?? null"
                    wrapper="w-40" />
                <div class="roster-actions">
                    <x-roster::icon-button icon="funnel" :label="__('roster::roster.filter')" variant="primary" type="submit" data-testid="filter-audit" />
                    <x-roster::icon-button icon="x-mark" :label="__('roster::roster.clear')" variant="ghost" :href="route('atrium.roster.audit.index')" />
                </div>
            </form>
        </x-atrium::card>

        @include('roster::ui.audit.partials.entries', ['entries' => $entries])

        <x-atrium::pagination :paginator="$entries" />

        @rosterCan('roster.audit.record', \JayI\Roster\Support\Scopes::fromInput($filters['organization'] ?? null))
        <x-atrium::card :title="__('roster::roster.record_note')" data-testid="record-note-card">
            <form method="POST" action="{{ route('atrium.roster.audit.store') }}" class="flex flex-wrap items-start gap-3">
                @csrf
                <x-atrium::form.input name="action" :label="__('roster::roster.action')" :hint="__('roster::roster.audit_record_hint')" wrapper="w-56" required />
                <x-atrium::form.input name="subject_label" :label="__('roster::roster.subject')" wrapper="w-56" />
                <x-atrium::form.input name="organization" :label="__('roster::roster.organization')" wrapper="w-48" />
                <x-atrium::form.input name="context[note]" id="audit-note" :label="__('roster::roster.note')" wrapper="w-80" />
                <div class="roster-actions">
                    <x-roster::icon-button icon="pencil-square" :label="__('roster::roster.record')" variant="primary" type="submit" data-testid="record-audit" />
                </div>
            </form>
        </x-atrium::card>
        @endrosterCan
    </div>
</x-atrium::layout>
