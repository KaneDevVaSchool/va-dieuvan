<?php

namespace App\Support;

use App\Models\ExpenseDocument;
use App\Models\User;

final class ExpenseDocumentAccess
{
    public static function userOwns(User $user, ExpenseDocument $doc): bool
    {
        return $doc->requester_user_id !== null && (int) $doc->requester_user_id === (int) $user->id;
    }

    public static function userCanView(User $user, ExpenseDocument $doc): bool
    {
        return $user->hasPermission('expense.view') || self::userOwns($user, $doc);
    }
}
