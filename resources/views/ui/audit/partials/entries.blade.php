@if ($entries->isEmpty())
    <x-atrium::empty-state :title="__('roster::roster.no_audit_entries')" />
@else
    <x-atrium::table striped>
        <x-slot:head>
            <x-atrium::table.row>
                <x-atrium::table.cell heading>{{ __('roster::roster.when') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading>{{ __('roster::roster.action') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading>{{ __('roster::roster.subject') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading>{{ __('roster::roster.actor') }}</x-atrium::table.cell>
                <x-atrium::table.cell heading>{{ __('roster::roster.surface') }}</x-atrium::table.cell>
            </x-atrium::table.row>
        </x-slot:head>

        @foreach ($entries as $entry)
            <x-atrium::table.row>
                <x-atrium::table.cell>
                    <a class="underline-offset-2 hover:underline" href="{{ route('atrium.roster.audit.show', $entry->id) }}" title="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->diffForHumans() }}</a>
                </x-atrium::table.cell>
                <x-atrium::table.cell>
                    <code>{{ $entry->action }}</code>
                    @if ($entry->source === 'app')
                        <x-atrium::badge variant="info">{{ __('roster::roster.source_app') }}</x-atrium::badge>
                    @endif
                </x-atrium::table.cell>
                <x-atrium::table.cell>{{ $entry->subject_label ?? __('roster::roster.none') }}</x-atrium::table.cell>
                <x-atrium::table.cell>{{ $entry->actor ? ($directory->name($entry->actor) ?? $directory->email($entry->actor)) : __('roster::roster.system') }}</x-atrium::table.cell>
                <x-atrium::table.cell>{{ $entry->surface }}</x-atrium::table.cell>
            </x-atrium::table.row>
        @endforeach
    </x-atrium::table>
@endif
