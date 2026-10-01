@use(JayI\Roster\Atrium\Badges)
@use(JayI\Roster\Enums\UserStatus)

<x-atrium::card :title="__('roster::roster.widget_user_status')">
    <div class="flex flex-wrap gap-2">
        @foreach ($counts as $status => $count)
            <a class="flex items-center gap-2 rounded-radius border border-outline px-3 py-2 transition hover:bg-surface-alt dark:border-outline-dark dark:hover:bg-surface-dark-alt"
               href="{{ route('atrium.roster.users.index', ['status' => $status]) }}">
                <x-atrium::badge :variant="Badges::forStatus(UserStatus::from($status))">{{ UserStatus::from($status)->label() }}</x-atrium::badge>
                <span class="text-sm font-semibold tabular-nums">{{ $count }}</span>
            </a>
        @endforeach
    </div>
</x-atrium::card>
