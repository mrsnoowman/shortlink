<?php

namespace App\Http\Controllers;

use App\Models\Shortlink;
use Illuminate\Http\Request;

class ShortlinkAnalyticsExportController extends Controller
{
    /**
     * Export redirect logs for a shortlink as CSV (Excel compatible).
     */
    public function __invoke(Request $request, Shortlink $shortlink)
    {
        $user = $request->user();

        // Only master can see all; others limited to own shortlinks.
        if ($user?->role !== 'master' && $shortlink->user_id !== $user?->id) {
            abort(403);
        }

        $fileName = 'shortlink-' . $shortlink->short_code . '-redirects.csv';

        return response()->streamDownload(function () use ($shortlink) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Time',
                'IP',
                'Country',
                'Referrer',
                'Browser',
                'Browser Version',
                'Platform',
                'Platform Version',
                'Device Type',
            ]);

            $shortlink->redirectLogs()
                ->orderBy('created_at')
                ->chunk(500, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            optional($log->created_at)->format('Y-m-d H:i:s'),
                            $log->ip,
                            $log->country,
                            $log->referrer,
                            $log->browser,
                            $log->browser_version,
                            $log->platform,
                            $log->platform_version,
                            $log->device_type,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}


