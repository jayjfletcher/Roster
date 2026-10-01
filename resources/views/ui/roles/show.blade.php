<x-atrium::layout :title="$role->name">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="$role->name" :description="$role->scope->label().' · '.($role->organization?->name ?? __('roster::roster.shared'))">
        <x-slot:actions>
            <x-atrium::button variant="ghost" :href="route('atrium.roster.roles.index')">{{ __('roster::roster.roles') }}</x-atrium::button>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            @if ($role->super)
                <p class="text-sm">{{ __('roster::roster.super_role_hint') }}</p>
            @else
                <form method="POST" action="{{ route('atrium.roster.roles.update', $role->id) }}" class="flex max-w-3xl flex-col gap-4">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $role->name)" required />
                    <x-atrium::form.textarea name="description" :label="__('roster::roster.description')" :value="old('description', $role->description)" rows="2" />
                    <fieldset class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <legend class="mb-1 text-sm font-medium">{{ __('roster::roster.permissions') }}</legend>
                        @foreach ($permissions as $permission)
                            <x-atrium::form.checkbox
                                name="permissions[]"
                                :value="$permission->name"
                                :id="'role-'.$permission->name"
                                :label="$permission->name"
                                :checked="$role->permissions->contains('name', $permission->name)" />
                        @endforeach
                    </fieldset>
                    <div>
                        <x-atrium::button type="submit" data-testid="save-role">{{ __('roster::roster.save') }}</x-atrium::button>
                    </div>
                </form>
            @endif
        </x-atrium::card>

        @unless ($role->system)
            <form method="POST" action="{{ route('atrium.roster.roles.destroy', $role->id) }}">
                @csrf
                @method('DELETE')
                <x-atrium::button type="submit" variant="danger" data-testid="delete-role">{{ __('roster::roster.delete') }}</x-atrium::button>
            </form>
        @endunless
    </div>
</x-atrium::layout>
