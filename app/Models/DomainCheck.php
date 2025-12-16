<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];

    /**
     * Owner of the domain check record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

