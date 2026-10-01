@inject('directory', 'JayI\Roster\Support\Users')

<x-atrium::layout :title="__('roster::roster.transfers')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.transfers')" :description="$organization?->name" />

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        @unless ($available)
            <x-atrium::alert variant="warning" :title="__('roster::roster.transfers_unavailable')">
                <code>composer require jayi/impex</code> · {{ __('roster::roster.transfers_unavailable_hint') }}
            </x-atrium::alert>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                <x-atrium::card :title="__('roster::roster.new_import')">
                    <form method="POST" action="{{ route('atrium.roster.transfers.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                        @csrf
                        <x-atrium::form.select
                            name="type"
                            id="import-type"
                            :label="__('roster::roster.type')"
                            :options="collect($imports)->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                            :selected="old('type')"
                            required />
                        <x-atrium::form.input name="organization" id="import-organization" :label="__('roster::roster.organization')" :value="old('organization', $organization?->slug)" :hint="__('roster::roster.import_organization_hint')" />
                        <x-atrium::form.file name="file" :label="__('roster::roster.csv_file')" :hint="__('roster::roster.import_columns_hint')" accept=".csv,text/csv" />
                        <div>
                            <x-atrium::button type="submit" data-testid="start-import">{{ __('roster::roster.preview_import') }}</x-atrium::button>
                        </div>
                    </form>
                </x-atrium::card>

                <x-atrium::card :title="__('roster::roster.new_export')">
                    <form method="POST" action="{{ route('atrium.roster.transfers.export') }}" class="flex flex-col gap-3">
                        @csrf
                        <x-atrium::form.select
                            name="type"
                            id="export-type"
                            :label="__('roster::roster.type')"
                            :options="collect($exports)->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                            required />
                        <x-atrium::form.input name="organization" id="export-organization" :label="__('roster::roster.organization')" :value="$organization?->slug" :hint="__('roster::roster.export_organization_hint')" />
                        <x-atrium::form.input name="filters[action]" id="export-action" :label="__('roster::roster.action')" :hint="__('roster::roster.audit_action_hint')" />
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-atrium::form.input name="filters[since]" id="export-since" type="date" :label="__('roster::roster.since')" />
                            <x-atrium::form.input name="filters[until]" id="export-until" type="date" :label="__('roster::roster.until')" />
                        </div>
                        <div>
                            <x-atrium::button type="submit" data-testid="start-export">{{ __('roster::roster.start_export') }}</x-atrium::button>
                        </div>
                    </form>
                </x-atrium::card>
            </div>
        @endunless

        @if ($transfers->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_transfers')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.type') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.organization') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.requested_by') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.rows') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.created') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>
                @foreach ($transfers as $transfer)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a href="{{ route('atrium.roster.transfers.show', $transfer->id) }}" class="font-medium underline-offset-2 hover:underline">{{ $transfer->type->label() }}</a>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $transfer->organization?->name ?? __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $transfer->requester ? ($directory->name($transfer->requester) ?? $directory->email($transfer->requester)) : __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell><x-atrium::badge>{{ $transfer->status->label() }}</x-atrium::badge></x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $transfer->row_count }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $transfer->created_at?->diffForHumans() }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
            <x-atrium::pagination :paginator="$transfers" />
        @endif
    </div>
</x-atrium::layout>
