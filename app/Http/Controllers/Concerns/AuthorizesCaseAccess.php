<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ForensicCase;
use Illuminate\Support\Facades\Auth;

trait AuthorizesCaseAccess
{
    protected function authorizeCaseAccess(ForensicCase $case): void
    {
        $user = Auth::user();

        if (in_array($user->role, ['Administrator', 'Reviewer'])) {
            return;
        }

        if (!$case->assignedUsers->contains($user->id)) {
            abort(403, 'Access denied. You are not assigned to this case.');
        }
    }
}
