<x-atrium::layout :title="__('roster::roster.new_user')">
    @include('roster::ui.partials.styles')

    <x-atrium::page-header :title="__('roster::roster.new_user')" />

    <div class="mt-5 flex max-w-2xl flex-col gap-4">
        @include('roster::ui.partials.status')

        <x-atrium::card>
            <form method="POST" action="{{ route('atrium.roster.users.store') }}" class="flex flex-col gap-4">
                @csrf

                @if ($hasName)
                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name')" required />
                @endif
                <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" :value="old('email')" required />
                <x-atrium::form.input name="password" type="password" :label="__('roster::roster.password')" :hint="__('roster::roster.password_create_hint')" />

                @include('roster::ui.users.partials.profile-fields', ['profile' => null])

                <div>
                    <x-atrium::button type="submit" data-testid="create-user">{{ __('roster::roster.create') }}</x-atrium::button>
                </div>
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
