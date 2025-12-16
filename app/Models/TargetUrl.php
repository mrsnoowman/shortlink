<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TargetUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'shortlink_id',
        'url',
        'is_blocked',
        'is_primary',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'is_primary' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-switch primary when primary target becomes blocked
        static::updated(function ($targetUrl) {
            // Check if this is a primary target that just became blocked
            if ($targetUrl->isDirty('is_blocked') && $targetUrl->is_blocked && $targetUrl->is_primary) {
                // Use DB facade to avoid triggering model events (prevent infinite loop)
                DB::transaction(function () use ($targetUrl) {
                    // Find first non-blocked target for this shortlink
                    $newPrimary = DB::table('target_urls')
                        ->where('shortlink_id', $targetUrl->shortlink_id)
                        ->where('is_blocked', false)
                        ->where('id', '!=', $targetUrl->id)
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($newPrimary) {
                        // Unset old primary and set new primary
                        DB::table('target_urls')
                            ->where('id', $targetUrl->id)
                            ->update(['is_primary' => false]);

                        DB::table('target_urls')
                            ->where('id', $newPrimary->id)
                            ->update(['is_primary' => true]);
                    } else {
                        // No active target found, just unset primary
                        DB::table('target_urls')
                            ->where('id', $targetUrl->id)
                            ->update(['is_primary' => false]);
                    }
                });
            }
        });
    }

    /**
     * Get the shortlink that owns the target URL.
     */
    public function shortlink(): BelongsTo
    {
        return $this->belongsTo(Shortlink::class);
    }
}

