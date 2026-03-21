<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\TenantScope);
    }
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'trigger',
        'conditions',
        'actions',
        'flow_data',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions'    => 'array',
        'flow_data'  => 'array',
        'is_active'  => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function executions()
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
