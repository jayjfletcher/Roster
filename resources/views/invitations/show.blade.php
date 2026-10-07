@php($heading = __('roster::roster.invitation_title', ['organization' => $organization->name]))

<x-atrium::guest :title="$heading" :heading="$heading">
    <p>{{ __('roster::roster.invitation_prompt', ['organization' => $organization->name]) }}</p>

    @if ($teams->isNotEmpty())
        <p>{{ __('roster::roster.invitation_teams', ['teams' => $teams->join(', ')]) }}</p>
    @endif

    @if ($errors->any())
        <x-atrium::alert variant="danger">{{ $errors->first() }}</x-atrium::alert>
    @endif

    <div class="flex flex-wrap gap-3">
        <form method="POST" action="{{ route('roster.invitations.page.accept', $token) }}">
            @csrf
            <x-atrium::button type="submit" data-testid="accept-invitation">{{ __('roster::roster.accept') }}</x-atrium::button>
        </form>
        <form method="POST" action="{{ route('roster.invitations.page.decline', $token) }}">
            @csrf
            <x-atrium::button type="submit" variant="outline" data-testid="decline-invitation">{{ __('roster::roster.decline') }}</x-atrium::button>
        </form>
    </div>
</x-atrium::guest>
