{{-- The Activate button for a user awaiting approval, to those who may approve; nothing otherwise. --}}
@if ($status === \JayI\Roster\Enums\UserStatus::Pending && \JayI\Roster\Http\Ui\ScreenAccess::allows('roster.users.approve'))
    <form method="POST" action="{{ route('atrium.roster.users.approve', $user->getRouteKey()) }}">
        @csrf
        <x-atrium::icon-button icon="check" :label="__('roster::roster.approve')" variant="primary" type="submit" size="sm" data-testid="activate-user" />
    </form>
@endif
