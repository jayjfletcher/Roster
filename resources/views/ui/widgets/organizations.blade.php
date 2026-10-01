<x-atrium::card :title="__('roster::roster.widget_organizations')">
    <div class="flex gap-2 text-center">
        <a href="{{ route('atrium.roster.organizations.index') }}" class="flex-1 rounded-radius border border-outline px-3 py-2 dark:border-outline-dark">
            <div class="text-lg font-semibold tabular-nums">{{ $organizations }}</div>
            <div class="text-xs">{{ __('roster::roster.organizations') }}</div>
        </a>
        <div class="flex-1 rounded-radius border border-outline px-3 py-2 dark:border-outline-dark">
            <div class="text-lg font-semibold tabular-nums">{{ $teams }}</div>
            <div class="text-xs">{{ __('roster::roster.teams') }}</div>
        </div>
        <div class="flex-1 rounded-radius border border-outline px-3 py-2 dark:border-outline-dark">
            <div class="text-lg font-semibold tabular-nums">{{ $pending }}</div>
            <div class="text-xs">{{ __('roster::roster.pending_invitations') }}</div>
        </div>
    </div>
</x-atrium::card>
