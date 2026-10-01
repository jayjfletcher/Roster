<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Http\Response;
use JayI\Roster\Actions\DeleteSsoConnectionAction;

final class DestroySsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    public function rules(): array
    {
        return DeleteSsoConnectionAction::rules();
    }

    public function persist(): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return response()->noContent();
    }
}
