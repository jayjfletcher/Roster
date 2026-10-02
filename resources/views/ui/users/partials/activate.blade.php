{{-- The Activate button for a user awaiting approval; nothing otherwise. --}}
@if ($status === \JayI\Roster\Enums\UserStatus::Pending)
    <form method="POST" action="{{ route('atrium.roster.users.approve', $user->getRouteKey()) }}">
        @csrf
        <x-atrium::button type="submit" size="sm" data-testid="activate-user">{{ __('roster::roster.approve') }}</x-atrium::button>
    </form>
@endif
