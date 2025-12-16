<?php

namespace App\Http\Controllers;

use App\Models\Shortlink;
use App\Models\RedirectLog;
use App\Models\Alias;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ShortlinkController extends Controller
{
    /**
     * Redirect shortlink to target URL
     */
    public function redirect(string $shortCode): RedirectResponse
    {
        // List of reserved routes that should not be treated as shortlinks
        $reservedRoutes = ['admin', 'api', 'livewire', 'filament', 'sanctum', '_ignition'];
        
        if (in_array($shortCode, $reservedRoutes)) {
            abort(404);
        }

        // Get the current domain/host from request
        $currentHost = request()->getHost();
        
        // Try to find alias based on current domain
        // Match exact domain or domain with/without scheme
        $alias = Alias::where('custom_domain', $currentHost)
            ->orWhere('custom_domain', 'http://' . $currentHost)
            ->orWhere('custom_domain', 'https://' . $currentHost)
            ->orWhere('custom_domain', 'like', $currentHost . '%')
            ->first();

        $shortlink = Shortlink::with('alias')->where('short_code', $shortCode)->first();

        if (!$shortlink) {
            // Shortlink not found - use fallback_url from alias if available
            if ($alias && $alias->fallback_url) {
                return redirect($alias->fallback_url);
            }
            abort(404, 'Shortlink not found');
        }

        // 1) Try redirecting to primary target URL if it's not blocked.
        //    If primary is blocked, automatically switch to first available non-blocked target.
        $primaryTarget = $shortlink->targetUrls()
            ->where('is_primary', true)
            ->first();

        // If primary exists and is not blocked, use it
        if ($primaryTarget && !$primaryTarget->is_blocked) {
            $targetUrl = $primaryTarget;
        } else {
            // Primary is blocked or doesn't exist, find first non-blocked target
            $targetUrl = $shortlink->targetUrls()
                ->where('is_blocked', false)
                ->orderBy('is_primary', 'desc') // Prioritize primary if exists
                ->orderBy('id', 'asc') // Then by ID for consistency
                ->first();
        }

        // 2) If all targets are blocked but there are still records,
        //    redirect randomly to any of them (as a fallback).
        if (! $targetUrl) {
            $fallbackTarget = $shortlink->targetUrls()
                ->inRandomOrder()
                ->first();

            if ($fallbackTarget) {
                $targetUrl = $fallbackTarget;
            }
        }

        // 3) Legacy fallback: if there are no TargetUrl records at all,
        //    but the old single target_url column is filled, use it.
        if (! $targetUrl && $shortlink->target_url) {
            $targetUrl = (object) ['url' => $shortlink->target_url];
        }

        // 4) If still no target URL, try to use fallback_url from alias
        if (! $targetUrl || empty($targetUrl->url)) {
            // First, try to get alias from shortlink's alias_id
            $shortlinkAlias = $shortlink->alias;
            
            // If shortlink has alias, use its fallback_url
            if ($shortlinkAlias && $shortlinkAlias->fallback_url) {
                return redirect($shortlinkAlias->fallback_url);
            }
            
            // Otherwise, try to use alias from current domain
            if ($alias && $alias->fallback_url) {
                return redirect($alias->fallback_url);
            }
            
            abort(404, 'No target URL found for this shortlink');
        }

        // Log redirect
        $this->logRedirect(request(), $shortlink);

        // Redirect to the first active target URL (others are backup)
        return redirect($targetUrl->url);
    }

    /**
     * Store redirect log with basic UA parsing.
     */
    protected function logRedirect(Request $request, Shortlink $shortlink): void
    {
        $ua = $request->userAgent() ?? '';
        $parsed = $this->parseUserAgent($ua);

        RedirectLog::create([
            'shortlink_id' => $shortlink->id,
            'country' => $request->header('CF-IPCountry'),
            'ip' => $request->ip(),
            'referrer' => $request->headers->get('referer'),
            'browser' => $parsed['browser'] ?? null,
            'browser_version' => $parsed['browser_version'] ?? null,
            'platform' => $parsed['platform'] ?? null,
            'platform_version' => $parsed['platform_version'] ?? null,
            'device_type' => $parsed['device_type'] ?? null,
        ]);
    }

    /**
     * Minimal user-agent parsing (best effort).
     */
    protected function parseUserAgent(string $ua): array
    {
        $browser = $version = $platform = $platformVersion = $device = null;

        // Platform detection
        if (preg_match('/Windows NT ([0-9.]+)/i', $ua, $m)) {
            $platform = 'Windows';
            $platformVersion = $m[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $ua, $m)) {
            $platform = 'macOS';
            $platformVersion = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([0-9.]+)/i', $ua, $m)) {
            $platform = 'Android';
            $platformVersion = $m[1];
        } elseif (preg_match('/iPhone OS ([0-9_]+)/i', $ua, $m) || preg_match('/iPad; CPU OS ([0-9_]+)/i', $ua, $m)) {
            $platform = 'iOS';
            $platformVersion = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Linux/i', $ua)) {
            $platform = 'Linux';
        }

        // Browser detection
        $browserMap = [
            'Edge' => '/Edg\/([0-9\.]+)/i',
            'Chrome' => '/Chrome\/([0-9\.]+)/i',
            'Firefox' => '/Firefox\/([0-9\.]+)/i',
            'Safari' => '/Version\/([0-9\.]+)\s+Safari/i',
            'Opera' => '/OPR\/([0-9\.]+)/i',
            'IE' => '/MSIE\s([0-9\.]+)/i',
        ];

        foreach ($browserMap as $name => $regex) {
            if (preg_match($regex, $ua, $m)) {
                $browser = $name;
                $version = $m[1] ?? null;
                // Special case: Safari without Version token
                if ($name === 'Safari' && !$version && preg_match('/Safari\/([0-9\.]+)/i', $ua, $m2)) {
                    $version = $m2[1];
                }
                break;
            }
        }

        // Device type
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone/i', $ua)) {
            $device = 'mobile';
        } elseif (preg_match('/Tablet|iPad/i', $ua)) {
            $device = 'tablet';
        } else {
            $device = 'desktop';
        }

        return [
            'browser' => $browser,
            'browser_version' => $version,
            'platform' => $platform,
            'platform_version' => $platformVersion,
            'device_type' => $device,
        ];
    }
}

