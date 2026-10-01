<x-atrium::layout :title="__('roster::roster.permissions')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.permissions')" />

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card :title="__('roster::roster.new_permission')">
            <form method="POST" action="{{ route('atrium.roster.permissions.store') }}" class="flex flex-wrap items-start gap-3">
                @csrf
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :hint="__('roster::roster.permission_name_hint')" wrapper="w-64" required />
                <x-atrium::form.input name="description" :label="__('roster::roster.description')" wrapper="w-80" />
                <div class="roster-actions">
                    <x-atrium::button type="submit" data-testid="create-permission">{{ __('roster::roster.create') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>

        <x-atrium::table striped>
            <x-slot:head>
                <x-atrium::table.row>
                    <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('roster::roster.description') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading></x-atrium::table.cell>
                </x-atrium::table.row>
            </x-slot:head>

            @foreach ($permissions as $permission)
                <x-atrium::table.row>
                    <x-atrium::table.cell>
                        <code>{{ $permission->name }}</code>
                        @if ($permission->system)
                            <x-atrium::badge>{{ __('roster::roster.built_in') }}</x-atrium::badge>
                        @endif
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>
                        <form method="POST" action="{{ route('atrium.roster.permissions.update', $permission->name) }}" class="flex items-end gap-2">
                            @csrf
                            @method('PATCH')
                            <x-atrium::form.input name="description" :id="'description-'.$permission->name" :value="$permission->description" wrapper="w-80" />
                            <x-atrium::button type="submit" size="sm" variant="ghost">{{ __('roster::roster.save') }}</x-atrium::button>
                        </form>
                    </x-atrium::table.cell>
                    <x-atrium::table.cell>
                        @unless ($permission->system)
                            <form method="POST" action="{{ route('atrium.roster.permissions.destroy', $permission->name) }}" class="flex justify-end">
                                @csrf
                                @method('DELETE')
                                <x-atrium::button type="submit" size="sm" variant="danger">{{ __('roster::roster.delete') }}</x-atrium::button>
                            </form>
                        @endunless
                    </x-atrium::table.cell>
                </x-atrium::table.row>
            @endforeach
        </x-atrium::table>

        <x-atrium::pagination :paginator="$permissions" />
    </div>
</x-atrium::layout>
