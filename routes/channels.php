<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('workspace.{workspaceId}', function ($user, $workspaceId) {
    if (!$user) return false;
    
    return (int) $user->active_workspace_id === (int) $workspaceId 
        || $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});
