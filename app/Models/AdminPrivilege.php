<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AdminPrivilege extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_key',
        'group_title',
        'page_key',
        'action_key',
        'page_title',
        'action_title',
        'is_allowed',
        'sidebar_unique_marker',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Enforce sidebar uniqueness constraint
            if ($model->group_key === 'sidebar' && $model->is_allowed) {
                // Generate the unique marker
                $marker = 'sidebar:' . $model->page_key;
                $model->sidebar_unique_marker = $marker;

                // Check if this sidebar permission is already assigned to another user
                $existing = static::query()
                    ->where('group_key', 'sidebar')
                    ->where('page_key', $model->page_key)
                    ->where('is_allowed', true)
                    ->where('id', '!=', $model->id ?? 0)
                    ->first();

                if ($existing) {
                    throw new \Exception(
                        "This menu/sidebar permission '{$model->page_title}' is already assigned to " .
                        ($existing->user?->name ?? 'another user') . 
                        ". Each sidebar access can only be assigned to one sub-admin."
                    );
                }
            } else {
                // Non-sidebar or unassigned privileges don't need the marker
                $model->sidebar_unique_marker = null;
            }
        });

        static::updated(function (self $model) {
            // When disabling a privilege, clear the marker
            if ($model->wasChanged('is_allowed') && !$model->is_allowed && $model->group_key === 'sidebar') {
                $model->update(['sidebar_unique_marker' => null]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who owns this sidebar permission (if it's assigned)
     */
    public function sidebarOwner(): ?User
    {
        if ($this->group_key === 'sidebar' && $this->is_allowed) {
            return $this->user;
        }
        return null;
    }
}
