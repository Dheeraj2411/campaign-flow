<?php

namespace App\Tenancy;

use App\Models\Workspace;

class TenantContext
{
    protected static ?Workspace $workspace = null;

    public static function setWorkspace(Workspace $workspace): void
    {
        static::$workspace = $workspace;
    }

    public static function getWorkspace(): ?Workspace
    {
        return static::$workspace;
    }

    public static function getWorkspaceId(): ?int
    {
        return static::$workspace?->id;
    }
}
