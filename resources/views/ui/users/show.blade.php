@use(JayI\Roster\Atrium\Badges)
@use(JayI\Roster\Enums\UserStatus)

@php($title = $profile?->display_name ?? $directory->name($user) ?? $directory->email($user) ?? __('roster::roster.user'))

<x-atrium::layout :title="$title">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$title" :description="$directory->email($user)">
        <x-slot:actions>
            <x-atrium::badge :variant="Badges::forStatus($status)" data-testid="user-status">{{ $status->label() }}</x-atrium::badge>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="lg:col-span-2">
            @include('roster::ui.partials.status')
        </div>

        <x-atrium::card :title="__('roster::roster.account')">
            <form method="POST" action="{{ route('atrium.roster.users.update', $user->getRouteKey()) }}" class="flex flex-col gap-4">
                @csrf
                @method('PATCH')

                @if ($directory->column('name') !== null)
                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $directory->name($user))" required />
                @endif
                <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" :value="old('email', $directory->email($user))" required />
                <x-atrium::form.input name="password" type="password" :label="__('roster::roster.password')" :hint="__('roster::roster.password_hint')" />

                <div>
                    <x-atrium::button type="submit" data-testid="save-account">{{ __('roster::roster.save') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.profile')">
            <form method="POST" action="{{ route('atrium.roster.users.profile', $user->getRouteKey()) }}" class="flex flex-col gap-4">
                @csrf
                @method('PATCH')

                @include('roster::ui.users.partials.profile-fields', ['profile' => $profile])

                <div>
                    <x-atrium::button type="submit" data-testid="save-profile">{{ __('roster::roster.save') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.status')" class="lg:col-span-2">
            @if ($profile?->status_reason)
                <p class="mb-4 text-sm">{{ __('roster::roster.reason') }}: {{ $profile->status_reason }}</p>
            @endif

            <div class="flex flex-wrap items-end gap-3">
                @if ($status !== UserStatus::Active)
                    <form method="POST" action="{{ route('atrium.roster.users.reactivate', $user->getRouteKey()) }}">
                        @csrf
                        <x-atrium::button type="submit" data-testid="reactivate-user">{{ __('roster::roster.reactivate') }}</x-atrium::button>
                    </form>
                @endif

                @if ($status !== UserStatus::Suspended)
                    <form method="POST" action="{{ route('atrium.roster.users.suspend', $user->getRouteKey()) }}" class="flex items-start gap-2">
                        @csrf
                        <x-atrium::form.input name="reason" id="suspend-reason" :label="__('roster::roster.reason')" wrapper="w-64" />
                        <div class="roster-actions">
                            <x-atrium::button type="submit" variant="secondary" data-testid="suspend-user">{{ __('roster::roster.suspend') }}</x-atrium::button>
                        </div>
                    </form>
                @endif

                @if ($status !== UserStatus::Deactivated)
                    <form method="POST" action="{{ route('atrium.roster.users.deactivate', $user->getRouteKey()) }}" class="flex items-start gap-2">
                        @csrf
                        <x-atrium::form.input name="reason" id="deactivate-reason" :label="__('roster::roster.reason')" wrapper="w-64" />
                        <div class="roster-actions">
                            <x-atrium::button type="submit" variant="secondary" data-testid="deactivate-user">{{ __('roster::roster.deactivate') }}</x-atrium::button>
                        </div>
                    </form>
                @endif

                <form method="POST" action="{{ route('atrium.roster.users.destroy', $user->getRouteKey()) }}" class="ms-auto">
                    @csrf
                    @method('DELETE')
                    <x-atrium::button type="submit" variant="danger" data-testid="delete-user">{{ __('roster::roster.delete') }}</x-atrium::button>
                </form>
            </div>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.memberships')" class="lg:col-span-2">
            @if ($memberships->isEmpty())
                <p class="text-sm">{{ __('roster::roster.no_organizations') }}</p>
            @else
                <ul class="mb-4 flex flex-col gap-1 text-sm">
                    @foreach ($memberships as $membership)
                        <li>
                            <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.organizations.show', $membership->organization) }}">{{ $membership->organization?->name }}</a>
                            @if ($currentOrganization?->is($membership->organization))
                                <x-atrium::badge variant="primary">{{ __('roster::roster.current') }}</x-atrium::badge>
                            @endif
                            @if ($membership->teams->isNotEmpty())
                                <span class="opacity-70">— {{ $membership->teams->pluck('name')->join(', ') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('atrium.roster.users.context', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
                    @csrf
                    @method('PUT')
                    <x-atrium::form.select
                        name="organization"
                        :label="__('roster::roster.organization')"
                        :options="$memberships->mapWithKeys(fn ($m) => [$m->organization?->slug => $m->organization?->name])"
                        :selected="$currentOrganization?->slug"
                        wrapper="w-56" />
                    <x-atrium::form.select
                        name="team"
                        :label="__('roster::roster.team')"
                        :placeholder="__('roster::roster.no_team')"
                        :options="$memberships->flatMap(fn ($m) => $m->teams->mapWithKeys(fn ($t) => [$t->slug => $m->organization?->name.' / '.$t->name]))"
                        :selected="$currentTeam?->slug"
                        wrapper="w-56" />
                    <div class="roster-actions">
                        <x-atrium::button type="submit" data-testid="switch-context">{{ __('roster::roster.switch') }}</x-atrium::button>
                    </div>
                </form>
            @endif

            <form method="POST" action="{{ route('atrium.roster.users.domain-join', $user->getRouteKey()) }}" class="mt-4">
                @csrf
                <x-atrium::button type="submit" variant="ghost" data-testid="domain-join">{{ __('roster::roster.run_domain_join') }}</x-atrium::button>
            </form>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.roles')" class="lg:col-span-2">
            @if ($effective['super_admin'])
                <p class="mb-3"><x-atrium::badge variant="danger">{{ __('roster::roster.super_admin') }}</x-atrium::badge></p>
            @endif

            @if ($assignments->isEmpty())
                <p class="text-sm">{{ __('roster::roster.no_roles') }}</p>
            @else
                <ul class="mb-4 flex flex-col gap-1 text-sm">
                    @foreach ($assignments as $assignment)
                        <li class="flex items-center justify-between gap-2">
                            <span>
                                <span class="font-medium">{{ $assignment->role?->name }}</span>
                                <span class="opacity-70">— {{ $assignment->team ? $assignment->organization?->name.' / '.$assignment->team->name : ($assignment->organization?->name ?? __('roster::roster.scope_global')) }}</span>
                            </span>
                            <form method="POST" action="{{ route('atrium.roster.users.roles.destroy', [$user->getRouteKey(), $assignment->id]) }}">
                                @csrf
                                @method('DELETE')
                                <x-atrium::button type="submit" size="sm" variant="ghost">{{ __('roster::roster.revoke') }}</x-atrium::button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('atrium.roster.users.roles.store', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
                @csrf
                <x-atrium::form.select
                    name="role"
                    :label="__('roster::roster.role')"
                    :options="$assignableRoles->mapWithKeys(fn ($role) => [$role->id => $role->name.' ('.$role->scope->label().($role->organization ? ', '.$role->organization->name : '').')'])"
                    wrapper="w-72" />
                <x-atrium::form.select
                    name="organization"
                    :label="__('roster::roster.organization')"
                    :placeholder="__('roster::roster.scope_global')"
                    :options="$memberships->mapWithKeys(fn ($m) => [$m->organization?->slug => $m->organization?->name])"
                    wrapper="w-56" />
                <x-atrium::form.select
                    name="team"
                    :label="__('roster::roster.team')"
                    :placeholder="__('roster::roster.no_team')"
                    :options="$memberships->flatMap(fn ($m) => $m->teams->mapWithKeys(fn ($t) => [$t->slug => $m->organization?->name.' / '.$t->name]))"
                    wrapper="w-56" />
                <div class="roster-actions">
                    <x-atrium::button type="submit" data-testid="assign-role">{{ __('roster::roster.assign') }}</x-atrium::button>
                </div>
            </form>

            <details class="mt-4 text-sm">
                <summary class="cursor-pointer">{{ __('roster::roster.effective_permissions') }} ({{ count($effective['permissions']) }})</summary>
                <p class="mt-2 font-mono text-xs leading-relaxed">{{ implode(', ', $effective['permissions']) ?: __('roster::roster.none') }}</p>
            </details>
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.activity')" class="lg:col-span-2">
            @include('roster::ui.audit.partials.entries', ['entries' => $activity])
            <x-atrium::button class="mt-3" variant="ghost" :href="route('atrium.roster.audit.index', ['user' => $user->getRouteKey()])">{{ __('roster::roster.view_all') }}</x-atrium::button>
        </x-atrium::card>

        @can('roster.users.impersonate')
            <x-atrium::card :title="__('roster::roster.impersonate')" class="lg:col-span-2">
                <form method="POST" action="{{ route('atrium.roster.users.impersonate', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
                    @csrf
                    <x-atrium::form.input name="reason" id="impersonation-reason" :label="__('roster::roster.reason')" :hint="__('roster::roster.impersonation_reason_hint')" wrapper="w-80" required />
                    <div class="roster-actions">
                        <x-atrium::button type="submit" variant="warning" data-testid="impersonate-user">{{ __('roster::roster.impersonate') }}</x-atrium::button>
                    </div>
                </form>
            </x-atrium::card>
        @endcan

        @if ($ssoIdentities->isNotEmpty())
            <x-atrium::card :title="__('roster::roster.sso_identities')" class="lg:col-span-2">
                <ul class="flex flex-col gap-1 text-sm">
                    @foreach ($ssoIdentities as $identity)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $identity->connection?->name }} — {{ $identity->email ?? $identity->subject }} <span class="opacity-60">{{ __('roster::roster.last_login') }}: {{ $identity->last_login_at?->diffForHumans() ?? __('roster::roster.none') }}</span></span>
                            <form method="POST" action="{{ route('atrium.roster.sso-identities.destroy', $identity->id) }}">
                                @csrf
                                @method('DELETE')
                                <x-atrium::button type="submit" size="sm" variant="ghost">{{ __('roster::roster.unlink') }}</x-atrium::button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </x-atrium::card>
        @endif
    </div>
</x-atrium::layout>
