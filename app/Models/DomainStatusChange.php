<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainStatusChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shortlink_id',
        'target_url_id',
        'domain_check_id',
        'domain',
        'url',
        'old_status',
        'new_status',
        'change_type',
        'notified',
    ];

    protected $casts = [
        'old_status' => 'boolean',
        'new_status' => 'boolean',
        'notified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class);
    }

    public function targetUrl(): BelongsTo
    {
        return $this->belongsTo(TargetUrl::class);
    }

    public function domainCheck(): BelongsTo
    {
        return $this->belongsTo(DomainCheck::class);
    }
}
