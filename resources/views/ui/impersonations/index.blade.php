@inject('directory', 'JayI\Roster\Support\Users')

<x-atrium::layout :title="__('roster::roster.impersonations')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.impersonations')" />

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        @if ($impersonations->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_impersonations')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.impersonator') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.user') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.reason') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>
                @foreach ($impersonations as $impersonation)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>{{ $impersonation->impersonator ? $directory->name($impersonation->impersonator) : __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $impersonation->user ? $directory->name($impersonation->user) : __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $impersonation->reason }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($impersonation->isActive())
                                <x-roster::status variant="warning" :label="__('roster::roster.impersonation_active')" />
                            @elseif ($impersonation->ended_at)
                                {{ $impersonation->end_reason }} · {{ $impersonation->ended_at->diffForHumans() }}
                            @else
                                {{ __('roster::roster.impersonation_link_issued') }}
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            {{-- Your own impersonation, or anyone's with the permission in its organization. --}}
                            @if (! $impersonation->ended_at && \JayI\Roster\Http\Ui\ScreenAccess::allows('roster.users.impersonate', $impersonation->organization, (string) $impersonation->impersonator_id === (string) auth()->id() ? auth()->user() : null))
                                <form method="POST" action="{{ route('atrium.roster.impersonations.stop', $impersonation->id) }}" class="flex justify-end">
                                    @csrf
                                    @method('DELETE')
                                    <x-roster::icon-button icon="stop" :label="__('roster::roster.end')" type="submit" size="sm" variant="danger" data-testid="end-impersonation" />
                                </form>
                            @endif
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>
            <x-atrium::pagination :paginator="$impersonations" />
        @endif
    </div>
</x-atrium::layout>
