<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedirectLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shortlink_id',
        'country',
        'ip',
        'referrer',
        'browser',
        'browser_version',
        'platform',
        'platform_version',
        'device_type',
    ];

    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class);
    }
}

