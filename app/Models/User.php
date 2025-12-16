<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'limit_short',
        'limit_domain',
        'limit_domain_check',
        'telegram_enabled',
        'telegram_chat_id',
        'telegram_interval_minutes',
        'last_telegram_notified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'telegram_enabled' => 'boolean',
            'last_telegram_notified_at' => 'datetime',
        ];
    }

    /**
     * Get the shortlink for the user.
     */
    public function shortlink(): HasOne
    {
        return $this->hasOne(Shortlink::class);
    }

    /**
     * Relasi ke role/toko yang dimiliki user.
     */
    public function roleRelation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}

