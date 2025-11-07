<?php

namespace App\Policies;

use App\Models\DuplicationJob;
use App\Models\User;

class DuplicationJobPolicy
{
    public function view(User $user, DuplicationJob $job): bool
    {
        return $job->requested_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}

