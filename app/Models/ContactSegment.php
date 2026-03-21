<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSegment extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\TenantScope);
    }
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function scopeApplyConditions($query, array $conditions)
    {
        foreach ($conditions as $condition) {
            if (!isset($condition['field'], $condition['operator'], $condition['value'])) {
                continue;
            }

            $field    = $condition['field'];
            $operator = $condition['operator'];
            $value    = $condition['value'];

            if (!in_array($operator, ['=', '!=', 'like', 'not like', 'ilike', 'not ilike', 'in', 'not in'])) {
                continue;
            }

            // PostgreSQL: use case-insensitive ILIKE
            if ($operator === 'like') $operator = 'ilike';
            if ($operator === 'not like') $operator = 'not ilike';

            if (in_array($operator, ['in', 'not in']) && is_array($value)) {
                $query->whereIn($field, $value, $operator === 'not in');
            } else {
                $query->where($field, $operator, $value);
            }
        }

        return $query;
    }

    public function contacts()
    {
        return Contact::where('workspace_id', $this->workspace_id)
            ->where(function ($query) {
                $this->scopeApplyConditions($query, $this->conditions ?? []);
            });
    }
}
