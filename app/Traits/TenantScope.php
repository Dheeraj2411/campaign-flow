<?php

namespace App\Traits;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScope
{
    public static function bootTenantScope(): void
    {
        static::addGlobalScope('workspace_id', function (Builder $builder) {
            if ($workspaceId = TenantContext::getWorkspaceId()) {
                $builder->where($builder->getQuery()->from . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function (Model $model) {
            if (! $model->workspace_id && ($workspaceId = TenantContext::getWorkspaceId())) {
                $model->workspace_id = $workspaceId;
            }
        });
    }
}
