@use(JayI\Roster\Enums\TransferStatus)
@inject('directory', 'JayI\Roster\Support\Users')

<x-atrium::layout :title="$transfer->type->label()">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$transfer->type->label()" :description="$transfer->organization?->name">
        <x-slot:actions>
            @if ($downloadable)
                <x-atrium::button :href="route('atrium.roster.transfers.download', $transfer->id)" data-testid="download-transfer">{{ __('roster::roster.download_csv') }}</x-atrium::button>
            @endif
            <x-atrium::button variant="ghost" :href="route('atrium.roster.transfers.index', array_filter(['organization' => $transfer->organization?->slug]))">{{ __('roster::roster.transfers') }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            <dl class="grid grid-cols-3 gap-2 text-sm">
                <dt class="opacity-70">{{ __('roster::roster.status') }}</dt>
                <dd class="col-span-2"><x-atrium::badge data-testid="transfer-status">{{ $transfer->status->label() }}</x-atrium::badge></dd>
                <dt class="opacity-70">{{ __('roster::roster.requested_by') }}</dt>
                <dd class="col-span-2">{{ $transfer->requester ? ($directory->name($transfer->requester) ?? $directory->email($transfer->requester)) : __('roster::roster.none') }}</dd>
                <dt class="opacity-70">{{ __('roster::roster.rows') }}</dt>
                <dd class="col-span-2">{{ $transfer->row_count }}</dd>
                @if ($transfer->expires_at && $transfer->status === TransferStatus::AwaitingConfirmation)
                    <dt class="opacity-70">{{ __('roster::roster.confirm_by') }}</dt>
                    <dd class="col-span-2">{{ $transfer->expires_at->toDayDateTimeString() }}</dd>
                @endif
                @if ($transfer->finished_at)
                    <dt class="opacity-70">{{ __('roster::roster.finished') }}</dt>
                    <dd class="col-span-2">{{ $transfer->finished_at->toDayDateTimeString() }}</dd>
                @endif
            </dl>

            @if ($transfer->report['error'] ?? null)
                <x-atrium::alert variant="danger" class="mt-4">{{ $transfer->report['error'] }}</x-atrium::alert>
            @endif

            @if ($progress && $transfer->status === TransferStatus::Running)
                <x-atrium::progress class="mt-4" :value="$progress['done']" :max="max(1, $progress['total'])" :label="__('roster::roster.rows_applied', $progress)" />
            @endif

            @if ($transfer->summary())
                <div class="mt-4 flex flex-wrap gap-2" data-testid="transfer-summary">
                    @foreach ($transfer->summary() as $action => $count)
                        <x-atrium::badge>{{ __('roster::roster.row_'.$action) }}: {{ $count }}</x-atrium::badge>
                    @endforeach
                </div>
            @endif

            @if (! $transfer->status->isFinished())
                <div class="mt-4 flex gap-2">
                    @if ($transfer->status === TransferStatus::AwaitingConfirmation && $canConfirm)
                        <form method="POST" action="{{ route('atrium.roster.transfers.confirm', $transfer->id) }}">
                            @csrf
                            <x-atrium::button type="submit" data-testid="confirm-import">{{ __('roster::roster.confirm_import') }}</x-atrium::button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('atrium.roster.transfers.cancel', $transfer->id) }}">
                        @csrf
                        @method('DELETE')
                        <x-atrium::button type="submit" variant="danger" data-testid="cancel-transfer">{{ __('roster::roster.cancel') }}</x-atrium::button>
                    </form>
                </div>
            @endif
        </x-atrium::card>

        @if ($transfer->rows())
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.line') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.row') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.planned') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.result') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>
                @foreach ($transfer->rows() as $row)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>{{ $row['line'] ?? '' }}</x-atrium::table.cell>
                        <x-atrium::table.cell class="text-xs">{{ collect((array) ($row['values'] ?? []))->filter()->join(' · ') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <x-atrium::badge>{{ __('roster::roster.row_'.($row['action'] ?? 'skip')) }}</x-atrium::badge>
                            <span class="text-xs opacity-70">{{ implode(' ', (array) ($row['reasons'] ?? [])) }}</span>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @isset($row['result'])
                                <x-atrium::badge>{{ __('roster::roster.row_'.$row['result']) }}</x-atrium::badge>
                                <span class="text-xs opacity-70">{{ implode(' ', (array) ($row['result_reasons'] ?? [])) }}</span>
                            @endisset
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
        @endif
    </div>
</x-atrium::layout>
