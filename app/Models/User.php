<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'salary',
        'employee_no',
        'phone',
        'aadhaar_no',
        'pan_no',
        'dob',
        'profile_pic',
    ];

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
            'is_active' => 'boolean',
            'salary' => 'decimal:2',
            'dob' => 'date',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_user')->withTimestamps();
    }

    public function privileges(): HasMany
    {
        return $this->hasMany(AdminPrivilege::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(AdminLoginLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class, 'actor_user_id');
    }

    public function ownedActivityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class, 'owner_user_id');
    }

    public function securityNotifications(): HasMany
    {
        return $this->hasMany(AdminSecurityNotification::class, 'owner_user_id');
    }

    public function adminRoleKeys(): array
    {
        $keys = $this->relationLoaded('roles')
            ? $this->roles->pluck('role_key')->all()
            : $this->roles()->pluck('role_key')->all();

        if (!empty($this->role)) {
            $keys[] = $this->role;
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public function hasAdminRole(string|array $roleKeys): bool
    {
        $roleKeys = is_array($roleKeys) ? $roleKeys : [$roleKeys];

        return count(array_intersect($this->adminRoleKeys(), $roleKeys)) > 0;
    }
}
