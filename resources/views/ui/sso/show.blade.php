@php($resource = (new \JayI\Roster\Http\Resources\SsoConnectionResource($connection))->resolve())

<x-atrium::layout :title="$connection->name">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$connection->name" :description="__('roster::roster.protocol_'.$connection->protocol).' · '.$connection->organization?->name">
        <x-slot:actions>
            <x-roster::icon-button icon="arrow-left" :label="$connection->organization?->name" variant="ghost" :href="route('atrium.roster.organizations.show', [$connection->organization, 'tab' => 'sso'])" />
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card :title="__('roster::roster.sso')">
            <dl class="grid grid-cols-3 gap-2 text-sm">
                <dt class="opacity-70">{{ __('roster::roster.callback_url') }}</dt><dd class="col-span-2 break-all font-mono text-xs" data-testid="callback-url">{{ $resource['callback_url'] ?? __('roster::roster.none') }}</dd>
                @if ($resource['metadata_url'])
                    <dt class="opacity-70">{{ __('roster::roster.metadata_url') }}</dt><dd class="col-span-2 break-all font-mono text-xs">{{ $resource['metadata_url'] }}</dd>
                @endif
                <dt class="opacity-70">{{ __('roster::roster.sso_identities') }}</dt><dd class="col-span-2">{{ $connection->identities_count }}</dd>
            </dl>
            @if ($resource['sign_in_url'])
                <x-roster::icon-button icon="arrow-top-right-on-square" :label="__('roster::roster.test_sign_in')" class="mt-3" variant="ghost" :href="$resource['sign_in_url']" />
            @endif
        </x-atrium::card>

        @rosterCan('roster.sso.manage', $connection->organization)
        <x-atrium::card :title="__('roster::roster.settings')" data-testid="sso-settings">
            <form method="POST" action="{{ route('atrium.roster.sso.update', $connection->slug) }}" class="flex max-w-3xl flex-col gap-4">
                @csrf
                @method('PATCH')
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $connection->name)" required />
                @include('roster::ui.sso.partials.fields', ['settings' => (array) $resource['settings'], 'editing' => true, 'protocol' => $connection->protocol, 'jit' => $connection->jit, 'enforced' => $connection->enforced, 'enabled' => $connection->enabled])
                <div>
                    <x-roster::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-sso" />
                </div>
            </form>
        </x-atrium::card>

        <form method="POST" action="{{ route('atrium.roster.sso.destroy', $connection->slug) }}">
            @csrf
            @method('DELETE')
            <x-roster::icon-button icon="trash" :label="__('roster::roster.delete')" type="submit" variant="danger" data-testid="delete-sso" />
        </form>
        @endrosterCan
    </div>
</x-atrium::layout>
