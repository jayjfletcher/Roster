<x-atrium::layout :title="__('roster::roster.organizations')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.organizations')">
        <x-slot:actions>
            <x-atrium::button :href="route('atrium.roster.organizations.create')" data-testid="new-organization">{{ __('roster::roster.new_organization') }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            <form method="GET" action="{{ route('atrium.roster.organizations.index') }}" class="flex flex-wrap items-start gap-3">
                <x-atrium::form.input name="search" :label="__('roster::roster.search')" :value="$filters['search'] ?? null" wrapper="w-64" />
                <div class="roster-actions">
                    <x-atrium::button type="submit">{{ __('roster::roster.filter') }}</x-atrium::button>
                    <x-atrium::button variant="ghost" :href="route('atrium.roster.organizations.index')">{{ __('roster::roster.clear') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        @if ($organizations->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_organizations')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.slug') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.members') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.teams') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($organizations as $organization)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.organizations.show', $organization) }}">{{ $organization->name }}</a>
                            @if ($organization->personal)
                                <x-atrium::badge>{{ __('roster::roster.personal') }}</x-atrium::badge>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->slug }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->memberships_count }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->teams_count }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <x-atrium::pagination :paginator="$organizations" />
        @endif
    </div>
</x-atrium::layout>
