<x-atrium::layout :title="__('roster::roster.new_organization')">
    <x-atrium::page-header :title="__('roster::roster.new_organization')" />

    <div class="mt-5 flex max-w-2xl flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <x-atrium::card>
            <form method="POST" action="{{ route('atrium.roster.organizations.store') }}" class="flex flex-col gap-4">
                @csrf

                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name')" required />
                <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :value="old('slug')" :hint="__('roster::roster.slug_hint')" />
                <x-atrium::form.input name="owner" :label="__('roster::roster.owner')" :value="old('owner', auth()->id())" :hint="__('roster::roster.owner_optional_hint')" />
                <x-atrium::form.textarea name="domains" :label="__('roster::roster.domains')" :value="old('domains')" :hint="__('roster::roster.domains_hint')" rows="3" />
                <x-atrium::form.checkbox name="auto_join" value="1" :label="__('roster::roster.auto_join')" />

                <div>
                    <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-organization" />
                </div>
            </form>
        </x-atrium::card>
    </div>
</x-atrium::layout>
