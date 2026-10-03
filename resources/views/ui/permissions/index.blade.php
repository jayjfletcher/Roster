<x-atrium::layout :title="__('roster::roster.permissions')">
    <x-atrium::page-header :title="__('roster::roster.permissions')" />

    @php($manage = \JayI\Roster\Atrium\ScreenAccess::allows('roster.roles.manage'))

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        @if ($manage)
        <x-atrium::card :title="__('roster::roster.new_permission')" data-testid="new-permission-card">
            <form method="POST" action="{{ route('atrium.roster.permissions.store') }}" class="flex flex-wrap items-start gap-3">
                @csrf
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :hint="__('roster::roster.permission_name_hint')" wrapper="w-64" required />
                <x-atrium::form.input name="description" :label="__('roster::roster.description')" wrapper="w-80" />
                <div class="roster-actions">
                    <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-permission" />
                </div>
            </form>
        </x-atrium::card>
        @endif

        <x-atrium::table striped>
            <x-slot:head>
                <x-atrium::table.row>
                    <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                    <x-atrium::table.cell heading>{{ __('roster::roster.description') }}</x-atrium::table.cell>
                    @if ($manage)
                        <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                    @endif
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
                        @if ($manage)
                            {{-- Belongs to the Save form in the Actions column. --}}
                            <x-atrium::form.input name="description" :id="'description-'.$permission->name" :form="'update-'.$permission->id" :value="$permission->description" wrapper="w-80" />
                        @else
                            {{ $permission->description }}
                        @endif
                    </x-atrium::table.cell>
                    @if ($manage)
                    <x-atrium::table.cell>
                        <div class="flex justify-end gap-2">
                            <form method="POST" action="{{ route('atrium.roster.permissions.update', $permission->name) }}" id="update-{{ $permission->id }}">
                                @csrf
                                @method('PATCH')
                                <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" type="submit" size="sm" variant="ghost" />
                            </form>
                            @unless ($permission->system)
                                <form method="POST" action="{{ route('atrium.roster.permissions.destroy', $permission->name) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete')" type="submit" size="sm" variant="danger" />
                                </form>
                            @endunless
                        </div>
                    </x-atrium::table.cell>
                    @endif
                </x-atrium::table.row>
            @endforeach
        </x-atrium::table>

        <x-atrium::pagination :paginator="$permissions" />
    </div>
</x-atrium::layout>
