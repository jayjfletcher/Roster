<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('roster::roster.invitation_title', ['organization' => $organization->name]) }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f6f7; color: #1f2328; margin: 0; }
        main { max-width: 28rem; margin: 10vh auto; background: #fff; border: 1px solid #e3e3e6; border-radius: .75rem; padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        p { line-height: 1.5; }
        .error { color: #b42318; }
        .actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
        button { font: inherit; padding: .5rem 1rem; border-radius: .5rem; border: 1px solid #d0d0d5; background: #fff; cursor: pointer; }
        button.primary { background: #1f2328; color: #fff; border-color: #1f2328; }
        @media (prefers-color-scheme: dark) {
            body { background: #111214; color: #e6e6e8; }
            main { background: #1a1b1e; border-color: #2c2d31; }
            button { background: #1a1b1e; color: inherit; border-color: #3a3b40; }
            button.primary { background: #e6e6e8; color: #111214; }
        }
    </style>
</head>
<body>
<main>
    <h1>{{ __('roster::roster.invitation_title', ['organization' => $organization->name]) }}</h1>
    <p>{{ __('roster::roster.invitation_prompt', ['organization' => $organization->name]) }}</p>

    @if ($teams->isNotEmpty())
        <p>{{ __('roster::roster.invitation_teams', ['teams' => $teams->join(', ')]) }}</p>
    @endif

    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif

    <div class="actions">
        <form method="POST" action="{{ route('roster.invitations.page.accept', $token) }}">
            @csrf
            <button type="submit" class="primary" data-testid="accept-invitation">{{ __('roster::roster.accept') }}</button>
        </form>
        <form method="POST" action="{{ route('roster.invitations.page.decline', $token) }}">
            @csrf
            <button type="submit" data-testid="decline-invitation">{{ __('roster::roster.decline') }}</button>
        </form>
    </div>
</main>
</body>
</html>
