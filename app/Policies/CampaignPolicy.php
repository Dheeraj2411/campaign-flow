<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function view(User $user, Campaign $campaign): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $workspace = $campaign->workspace;
        if (!$workspace) {
            return false;
        }

        return $user->hasRoleInWorkspace('owner', $workspace)
            || $user->hasRoleInWorkspace('admin', $workspace)
            || $user->hasRoleInWorkspace('member', $workspace);
    }

    public function create(User $user): bool
    {
        return $user->activeWorkspace !== null;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $this->view($user, $campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->view($user, $campaign);
    }
}
