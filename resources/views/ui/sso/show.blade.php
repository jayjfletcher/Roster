@php($resource = (new \JayI\Roster\Domains\Sso\Resources\SsoConnectionResource($connection))->resolve())

<x-atrium::layout :title="$connection->name">
    <x-atrium::page-header :title="$connection->name" :description="__('roster::roster.protocol_'.$connection->protocol).' · '.$connection->organization?->name">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="$connection->organization?->name" variant="ghost" :href="route('atrium.roster.organizations.show', [$connection->organization, 'tab' => 'sso'])" />
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <x-atrium::card :title="__('roster::roster.sso')">
            <x-atrium::description-list>
                <x-atrium::description-list.item :term="__('roster::roster.callback_url')" class="break-all font-mono text-xs" data-testid="callback-url">{{ $resource['callback_url'] ?? __('roster::roster.none') }}</x-atrium::description-list.item>
                @if ($resource['metadata_url'])
                    <x-atrium::description-list.item :term="__('roster::roster.metadata_url')" class="break-all font-mono text-xs">{{ $resource['metadata_url'] }}</x-atrium::description-list.item>
                @endif
                <x-atrium::description-list.item :term="__('roster::roster.sso_identities')">{{ $connection->identities_count }}</x-atrium::description-list.item>
            </x-atrium::description-list>
            @if ($resource['sign_in_url'])
                <x-atrium::icon-button icon="arrow-top-right-on-square" :label="__('roster::roster.test_sign_in')" class="mt-3" variant="ghost" :href="$resource['sign_in_url']" />
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
                    <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-sso" />
                </div>
            </form>
        </x-atrium::card>

        <form method="POST" action="{{ route('atrium.roster.sso.destroy', $connection->slug) }}">
            @csrf
            @method('DELETE')
            <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete')" type="submit" variant="danger" data-testid="delete-sso" />
        </form>
        @endrosterCan
    </div>
</x-atrium::layout>
