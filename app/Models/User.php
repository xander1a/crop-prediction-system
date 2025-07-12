<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
   // app/Models/User.php
protected $fillable = [
    'name',
    'email',
    'phone',
    'role',
    'password',
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
        ];
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(User_alerts::class);
    }





    public static function getRoles()
    {
        return [
            'farmer' => 'Farmer',
            'trader' => 'Trader',
            'policymaker' => 'Policy Maker',
            'admin' => 'Administrator',
        ];
    }

    /**
     * Available language preferences
     */
    public static function getLanguages()
    {
        return [
            'en' => 'English',
            'fr' => 'French',
            'rw' => 'Kinyarwanda',
            'sw' => 'Swahili',
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->is_active ?? true;
    }

    /**
     * Get formatted role name
     */
    public function getRoleNameAttribute()
    {
        return ucfirst($this->role);
    }

    /**
     * Get formatted language name
     */
    public function getLanguageNameAttribute()
    {
        $languages = self::getLanguages();
        return $languages[$this->language_preference] ?? strtoupper($this->language_preference);
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to search users
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('email', 'like', '%' . $term . '%')
              ->orWhere('phone', 'like', '%' . $term . '%');
        });
    }

    /**
     * Get user's full contact info
     */
    public function getFullContactAttribute()
    {
        $contact = $this->email;
        if ($this->phone) {
            $contact .= ' | ' . $this->phone;
        }
        return $contact;
    }

    /**
     * Get user's initials for avatar
     */
    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->name);
        $initials = '';
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        return substr($initials, 0, 2);
    }

    /**
     * Get role color for UI
     */
    public function getRoleColorAttribute()
    {
        $colors = [
            'admin' => 'red',
            'policymaker' => 'blue',
            'trader' => 'yellow',
            'farmer' => 'green',
        ];

        return $colors[$this->role] ?? 'gray';
    }

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!isset($user->is_active)) {
                $user->is_active = true;
            }
        });
    }
}
