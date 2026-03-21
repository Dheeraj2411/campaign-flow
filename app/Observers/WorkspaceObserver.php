<?php

namespace App\Observers;

use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class WorkspaceObserver
{
    public function saved(Workspace $workspace): void
    {
        Cache::forget("workspace:{$workspace->id}:settings");
    }

    public function deleted(Workspace $workspace): void
    {
        // Forget all workspace related cache keys if deleted
        Cache::forget("workspace:{$workspace->id}:settings");
        Cache::forget("workspace:{$workspace->id}:chatbot_config");
    }
}
