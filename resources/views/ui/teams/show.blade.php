<x-atrium::layout :title="$team->name">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$team->name" :description="$organization->name">
        <x-slot:actions>
            <x-atrium::button variant="ghost" :href="route('atrium.roster.organizations.show', [$organization, 'tab' => 'teams'])">{{ $organization->name }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="lg:col-span-2">
            @include('roster::ui.partials.status')
        </div>

        <x-atrium::card :title="__('roster::roster.members')">
            @if ($team->memberships->isEmpty())
                <p class="text-sm">{{ __('roster::roster.no_members') }}</p>
            @else
                <ul class="flex flex-col gap-2">
                    @foreach ($team->memberships as $membership)
                        @if ($membership->user)
                            <li class="flex items-center justify-between gap-2">
                                <span>{{ $directory->name($membership->user) ?? $directory->email($membership->user) }}</span>
                                <form method="POST" action="{{ route('atrium.roster.teams.members.destroy', [$organization, $team->slug, $membership->user->getRouteKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-atrium::button type="submit" size="sm" variant="ghost">{{ __('roster::roster.remove') }}</x-atrium::button>
                                </form>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('atrium.roster.teams.members.store', [$organization, $team->slug]) }}" class="mt-4 flex items-start gap-2">
                @csrf
                <x-atrium::form.select
                    name="user"
                    :label="__('roster::roster.add_member')"
                    :options="collect($candidates->items())->filter(fn ($m) => $m->user)->mapWithKeys(fn ($m) => [$m->user->getRouteKey() => $directory->name($m->user) ?? $directory->email($m->user)])"
                    wrapper="w-64" />
                <div class="roster-actions">
                    <x-atrium::button type="submit" data-testid="add-team-member">{{ __('roster::roster.add_member') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.settings')">
            <form method="POST" action="{{ route('atrium.roster.teams.update', [$organization, $team->slug]) }}" class="flex flex-col gap-4">
                @csrf
                @method('PATCH')
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $team->name)" required />
                <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :value="old('slug', $team->slug)" required />
                <div>
                    <x-atrium::button type="submit" data-testid="save-team">{{ __('roster::roster.save') }}</x-atrium::button>
                </div>
            </form>

            <form method="POST" action="{{ route('atrium.roster.teams.destroy', [$organization, $team->slug]) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <x-atrium::button type="submit" variant="danger" data-testid="delete-team">{{ __('roster::roster.delete') }}</x-atrium::button>
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
