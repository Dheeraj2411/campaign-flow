<?php

namespace App\Policies;

use App\Models\ContactSegment;
use App\Models\User;

class ContactSegmentPolicy
{
    public function view(User $user, ContactSegment $segment): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $segment->workspace_id === $user->active_workspace_id;
    }

    public function create(User $user): bool
    {
        return $user->active_workspace_id !== null;
    }

    public function update(User $user, ContactSegment $segment): bool
    {
        return $this->view($user, $segment);
    }

    public function delete(User $user, ContactSegment $segment): bool
    {
        return $this->view($user, $segment);
    }
}
