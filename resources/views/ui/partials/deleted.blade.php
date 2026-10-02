{{--
    A deleted record's page: when it was deleted, Restore, and a Danger zone
    to delete it for good. Expects $deletedAt, $restoreUrl, $purgeUrl,
    $warning and $confirm; $canRestore and $canPurge hide what the viewer
    may not do.
--}}
<x-atrium::alert variant="warning" :title="__('roster::roster.deleted_banner', ['when' => $deletedAt?->diffForHumans()])" data-testid="deleted-banner">
    {{ __('roster::roster.deleted_banner_hint') }}
</x-atrium::alert>

@if ($canRestore ?? true)
<form method="POST" action="{{ $restoreUrl }}">
    @csrf
    <x-roster::icon-button icon="arrow-uturn-left" :label="__('roster::roster.restore')" variant="primary" type="submit" data-testid="restore" />
</form>
@endif

@if ($canPurge ?? true)
<x-atrium::card :title="__('roster::roster.danger_zone')" data-testid="danger-zone">
    <x-atrium::alert variant="danger" :title="__('roster::roster.delete_user_warning_title')">
        {{ $warning }}
    </x-atrium::alert>

    <form method="POST" action="{{ $purgeUrl }}" class="mt-4 flex flex-wrap items-center gap-4">
        @csrf
        @method('DELETE')
        <x-atrium::form.checkbox name="confirm" value="1" id="confirm-purge" :label="$confirm" required />
        <x-roster::icon-button icon="trash" :label="__('roster::roster.delete_permanently')" type="submit" variant="danger" data-testid="purge" />
    </form>
</x-atrium::card>
@endif
