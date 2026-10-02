{{-- A user's status badge. --}}
<x-atrium::badge :variant="\JayI\Roster\Atrium\Badges::forStatus($status)" data-testid="member-status">{{ $status->label() }}</x-atrium::badge>
