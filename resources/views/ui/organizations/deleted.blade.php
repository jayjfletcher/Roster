<x-atrium::layout :title="$organization->name">
    <x-atrium::page-header :title="$organization->name" :description="$organization->slug">
        <x-slot:actions>
            <x-atrium::badge>{{ __('roster::roster.deleted') }}</x-atrium::badge>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex max-w-3xl flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        @include('roster::ui.partials.deleted', [
            'deletedAt' => $organization->deleted_at,
            'restoreUrl' => route('atrium.roster.organizations.restore', $organization),
            'purgeUrl' => route('atrium.roster.organizations.purge', $organization),
            'warning' => __('roster::roster.delete_organization_warning'),
            'confirm' => __('roster::roster.delete_organization_confirm'),
            'canRestore' => \JayI\Roster\Atrium\ScreenAccess::allows('roster.organizations.delete', $organization),
            'canPurge' => \JayI\Roster\Atrium\ScreenAccess::allows('roster.organizations.purge', $organization),
        ])
    </div>
</x-atrium::layout>
