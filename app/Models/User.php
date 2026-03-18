<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active_workspace_id',
        'is_active',
        'can_edit_profile',
    ];

    public function workspaces(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Workspace::class , 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function activeWorkspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workspace::class , 'active_workspace_id');
    }

    public function isPlatformAdmin(): bool
    {
        // Simple implementation, ID 1 is the initial admin
        return $this->id === 1;
    }

    public function hasRoleInWorkspace(string $role, Workspace $workspace): bool
    {
        return $this->workspaces()
            ->where('workspace_id', $workspace->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_workspace_id' => 'integer',
            'is_active' => 'boolean',
            'can_edit_profile' => 'boolean',
        ];
    }
}
