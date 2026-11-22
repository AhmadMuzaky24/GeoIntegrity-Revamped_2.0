<?php

namespace App\Support;

use App\Models\User;

/**
 * Demo identity: whoever is selected in the session, else the first user.
 *
 * ponytail: session switch instead of login — the demo needs attribution, not
 * security. Swap for auth()->user() once real accounts exist.
 */
class Actor
{
    public const SESSION_KEY = 'actor_id';

    public static function current(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : User::orderBy('id')->first();
    }

    public static function name(): string
    {
        return self::current()?->name ?? 'Anonim';
    }
}
