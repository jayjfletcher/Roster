<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JayI\Roster\Actions\Concerns\ProfileRules;
use JayI\Roster\Events\Action\ProfileUpdatedActionEvent;
use JayI\Roster\Events\Action\ProfileUpdatingActionEvent;
use JayI\Roster\Support\Users;

final class UpdateProfileAction
{
    use ProfileRules;

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::profileRules();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Model $user, array $data): Model
    {
        ProfileUpdatingActionEvent::dispatch($user, $data);

        DB::transaction(function () use ($user, $data): void {
            $this->users->profile($user)->update(self::profileAttributes($data));
        });

        $user = $user->load('rosterProfile');

        ProfileUpdatedActionEvent::dispatch($user);

        return $user;
    }
}
