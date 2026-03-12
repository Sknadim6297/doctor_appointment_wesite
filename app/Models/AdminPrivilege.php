<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPrivilege extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_key',
        'group_title',
        'page_key',
        'page_title',
        'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }
}
