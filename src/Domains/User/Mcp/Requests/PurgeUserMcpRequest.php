<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\User\Actions\PurgeUserAction;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Support\Users;
use Laravel\Mcp\Response;

/**
 * Works on deleted records too.
 */
final class PurgeUserMcpRequest extends Request
{
    private ?Model $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.purge';
    }

    protected function rules(): array
    {
        return PurgeUserAction::rules() + ['user' => ['required']];
    }

    protected function handle(array $validated): Response
    {
        app(PurgeUserAction::class)->execute($this->trashed(), $this->actor());

        return Response::text('Deleted permanently.');
    }

    private function trashed(): Model
    {
        return $this->resolved ??= app(Users::class)->findWithTrashedOrFail($this->get('user'));
    }
}
