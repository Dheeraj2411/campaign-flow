<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSegment extends Model
{
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

            if (!in_array($operator, ['=', '!=', 'like', 'not like', 'in', 'not in'])) {
                continue;
            }

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
