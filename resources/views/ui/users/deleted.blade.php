<x-atrium::layout :title="$directory->name($user) ?? $directory->email($user) ?? ''">
    <x-atrium::page-header :title="$directory->name($user) ?? $directory->email($user) ?? ''" :description="$directory->email($user)">
        <x-slot:actions>
            <x-atrium::badge>{{ __('roster::roster.deleted') }}</x-atrium::badge>
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex max-w-3xl flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        @include('roster::ui.partials.deleted', [
            'deletedAt' => $user->getAttribute('deleted_at'),
            'restoreUrl' => route('atrium.roster.users.restore', $user->getRouteKey()),
            'purgeUrl' => route('atrium.roster.users.purge', $user->getRouteKey()),
            'warning' => __('roster::roster.purge_user_warning'),
            'confirm' => __('roster::roster.delete_user_confirm'),
            'canRestore' => \JayI\Roster\Atrium\ScreenAccess::allows('roster.users.delete'),
            'canPurge' => \JayI\Roster\Atrium\ScreenAccess::allows('roster.users.purge'),
        ])
    </div>
</x-atrium::layout>
