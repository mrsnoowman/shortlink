<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shortlink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alias_id',
        'short_code',
        'target_url',
        'target_urls',
    ];

    protected $casts = [
        'target_urls' => 'array',
    ];

    /**
     * Get the user that owns the shortlink.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Custom domain alias for this shortlink.
     */
    public function alias(): BelongsTo
    {
        return $this->belongsTo(Alias::class);
    }

    /**
     * Get the target URLs for the shortlink.
     */
    public function targetUrls(): HasMany
    {
        return $this->hasMany(TargetUrl::class);
    }

    /**
     * Redirect logs for this shortlink.
     */
    public function redirectLogs(): HasMany
    {
        return $this->hasMany(RedirectLog::class);
    }
}

