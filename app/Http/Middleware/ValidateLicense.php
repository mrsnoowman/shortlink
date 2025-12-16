<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenseService;

class ValidateLicense
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validation = $this->licenseService->validateLicense();

        if (!$validation['valid']) {
            // If it's an API request, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => $validation['message'],
                    'error_code' => $validation['error'] ?? 'LICENSE_ERROR'
                ], 403);
            }

            // For web requests, show custom license error page
            return response()->view('errors.license', [
                'message' => $validation['message'],
                'error_code' => $validation['error'] ?? 'LICENSE_ERROR'
            ], 403);
        }

        return $next($request);
    }
}

