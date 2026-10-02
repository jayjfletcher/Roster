<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Roster\Actions\ListAuditEntriesAction;
use JayI\Roster\Actions\RecordAuditEventAction;
use JayI\Roster\Actions\ShowAuditEntryAction;
use JayI\Roster\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Support\AuditAccess;
use JayI\Roster\Support\Scopes;
use JayI\Roster\Support\Users;

/**
 * The Atrium audit log: browse, inspect an entry, and record a note.
 */
final class AuditUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $filters = $request->validate(ListAuditEntriesAction::rules());
        $self = isset($filters['user']) ? $this->users->query()->where($this->users->routeKeyName(), $filters['user'])->first() : null;
        $within = $this->authorizeList('roster.audit.view', Scopes::fromInput($filters['organization'] ?? null), $self);

        /** @var view-string $view */
        $view = 'roster::ui.audit.index';

        return view($view, [
            'entries' => app(ListAuditEntriesAction::class)->execute($filters, $within)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $entry): View
    {
        $model = AuditEntry::query()->whereKey($entry)->firstOrFail();
        $actor = $request->user();
        $this->authorizeScreen('roster.audit.view', $model->organization, AuditAccess::selfFor($model, $actor instanceof Model ? $actor : null));

        /** @var view-string $view */
        $view = 'roster::ui.audit.show';

        return view($view, ['entry' => app(ShowAuditEntryAction::class)->execute($model)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.audit.record', Scopes::fromInput($request->input('organization')));

        $actor = $request->user();

        app(RecordAuditEventAction::class)->execute(
            $request->validate(RecordAuditEventAction::rules()),
            $actor instanceof Model ? $actor : null,
        );

        return redirect()
            ->route('atrium.roster.audit.index')
            ->with('status', __('roster::roster.audit_recorded'));
    }
}
