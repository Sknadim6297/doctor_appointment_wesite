<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_title',
        'role_key',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_role_user')->withTimestamps();
    }
}
