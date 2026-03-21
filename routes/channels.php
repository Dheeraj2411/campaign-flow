<?php

use Illuminate\Support\Facades\Broadcast;

// Per-user private channel (used by AppLayout & AdminLayout for notifications)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Per-workspace private channel (used by Inbox for real-time messages)
Broadcast::channel('workspace.{workspaceId}', function ($user, $workspaceId) {
    if (!$user) {
        return false;
    }
    
    if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) {
        return true;
    }
    
    $isActive = (int) $user->active_workspace_id === (int) $workspaceId;
    return $isActive || $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});

Broadcast::channel('chat.{workspaceId}', function ($user, $workspaceId) {
    if (!$user) return false;
    if (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin()) return true;
    return (int) $user->active_workspace_id === (int) $workspaceId || $user->workspaces->contains($workspaceId);
});
