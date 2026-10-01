@use(JayI\Roster\Atrium\Badges)

<x-atrium::layout :title="__('roster::roster.users')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.users')">
        <x-slot:actions>
            <x-atrium::button variant="ghost" :href="route('atrium.roster.transfers.index')" data-testid="user-transfers">{{ __('roster::roster.import_export') }}</x-atrium::button>
            <x-atrium::button :href="route('atrium.roster.users.create')" data-testid="new-user">{{ __('roster::roster.new_user') }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            <form method="GET" action="{{ route('atrium.roster.users.index') }}" class="flex flex-wrap items-start gap-3">
                <x-atrium::form.input name="search" :label="__('roster::roster.search')" :value="$filters['search'] ?? null" wrapper="w-64" />

                <x-atrium::form.select
                    name="status"
                    :label="__('roster::roster.status')"
                    :placeholder="__('roster::roster.all_statuses')"
                    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])"
                    :selected="$filters['status'] ?? null"
                    wrapper="w-48" />

                <div class="roster-actions">
                    <x-atrium::button type="submit" data-testid="filter-users">{{ __('roster::roster.filter') }}</x-atrium::button>
                    <x-atrium::button variant="ghost" :href="route('atrium.roster.users.index')">{{ __('roster::roster.clear') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        @if ($users->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_users')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.email') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.created') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($users as $user)
                    @php($status = $directory->status($user))
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="flex items-center gap-2 font-medium underline-offset-2 hover:underline"
                               href="{{ route('atrium.roster.users.show', $user->getRouteKey()) }}">
                                <x-atrium::avatar size="sm" :src="$user->rosterProfile?->avatar_url" :initials="str($user->rosterProfile?->display_name ?? $directory->name($user) ?? $directory->email($user))->substr(0, 2)->upper()" />
                                {{ $user->rosterProfile?->display_name ?? $directory->name($user) ?? __('roster::roster.none') }}
                            </a>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $directory->email($user) ?? __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <x-atrium::badge :variant="Badges::forStatus($status)">{{ $status->label() }}</x-atrium::badge>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $user->created_at?->diffForHumans() ?? __('roster::roster.none') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <x-atrium::pagination :paginator="$users" />
        @endif
    </div>
</x-atrium::layout>
