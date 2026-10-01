@php($resource = (new \JayI\Roster\Http\Resources\SsoConnectionResource($connection))->resolve())

<x-atrium::layout :title="$connection->name">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$connection->name" :description="__('roster::roster.protocol_'.$connection->protocol).' · '.$connection->organization?->name">
        <x-slot:actions>
            <x-atrium::button variant="ghost" :href="route('atrium.roster.organizations.show', [$connection->organization, 'tab' => 'sso'])">{{ $connection->organization?->name }}</x-atrium::button>
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
                <x-atrium::button class="mt-3" variant="ghost" :href="$resource['sign_in_url']">{{ __('roster::roster.test_sign_in') }}</x-atrium::button>
            @endif
        </x-atrium::card>

        <x-atrium::card :title="__('roster::roster.settings')">
            <form method="POST" action="{{ route('atrium.roster.sso.update', $connection->slug) }}" class="flex max-w-3xl flex-col gap-4">
                @csrf
                @method('PATCH')
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $connection->name)" required />
                @include('roster::ui.sso.partials.fields', ['settings' => (array) $resource['settings'], 'editing' => true, 'jit' => $connection->jit, 'enforced' => $connection->enforced, 'enabled' => $connection->enabled])
                <div>
                    <x-atrium::button type="submit" data-testid="save-sso">{{ __('roster::roster.save') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        <form method="POST" action="{{ route('atrium.roster.sso.destroy', $connection->slug) }}">
            @csrf
            @method('DELETE')
            <x-atrium::button type="submit" variant="danger" data-testid="delete-sso">{{ __('roster::roster.delete') }}</x-atrium::button>
        </form>
    </div>
</x-atrium::layout>
