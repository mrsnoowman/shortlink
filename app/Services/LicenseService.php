<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseService
{
    protected $licensePath;
    protected $licenseData;

    public function __construct()
    {
        $this->licensePath = storage_path('app/license.key');
    }

    /**
     * Check if license key exists
     */
    public function hasLicense(): bool
    {
        // Use native PHP file_exists as fallback if Facade not available
        try {
            if (class_exists('Illuminate\Support\Facades\File')) {
                return \Illuminate\Support\Facades\File::exists($this->licensePath);
            }
        } catch (\Exception $e) {
            // Fallback to native PHP
        }
        return file_exists($this->licensePath);
    }

    /**
     * Get license data
     */
    public function getLicenseData(): ?array
    {
        if (!$this->hasLicense()) {
            return null;
        }

        try {
            // Use native PHP file_get_contents as fallback
            $content = null;
            try {
                if (class_exists('Illuminate\Support\Facades\File')) {
                    $content = \Illuminate\Support\Facades\File::get($this->licensePath);
                } else {
                    $content = file_get_contents($this->licensePath);
                }
            } catch (\Exception $e) {
                $content = file_get_contents($this->licensePath);
            }
            
            if ($content === false) {
                return null;
            }
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (class_exists('Illuminate\Support\Facades\Log')) {
                    \Illuminate\Support\Facades\Log::error('Invalid license file format');
                }
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::error('Error reading license file: ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Validate license key
     */
    public function validateLicense(): array
    {
        if (!$this->hasLicense()) {
            return [
                'valid' => false,
                'message' => 'License key not found. The application cannot run.',
                'error' => 'NO_LICENSE'
            ];
        }

        $data = $this->getLicenseData();
        
        if (!$data) {
            return [
                'valid' => false,
                'message' => 'License key is invalid or corrupted. The application cannot run.',
                'error' => 'INVALID_LICENSE'
            ];
        }

        // Check if key exists
        if (!isset($data['key']) || empty($data['key'])) {
            return [
                'valid' => false,
                'message' => 'License key is empty. The application cannot run.',
                'error' => 'EMPTY_KEY'
            ];
        }

        // Check if expiry date exists
        if (!isset($data['expires_at'])) {
            return [
                'valid' => false,
                'message' => 'License key does not have an expiry date. The application cannot run.',
                'error' => 'NO_EXPIRY'
            ];
        }

        // Check if expired
        try {
            $expiresAt = Carbon::parse($data['expires_at']);
            
            if ($expiresAt->isPast()) {
                $daysExpired = now()->diffInDays($expiresAt);
                return [
                    'valid' => false,
                    'message' => "License key expired on {$expiresAt->format('d M Y H:i:s')} ({$daysExpired} day(s) ago). The application cannot run.",
                    'error' => 'EXPIRED',
                    'expires_at' => $expiresAt
                ];
            }

            // Check if expiring soon (within 7 days)
            $daysUntilExpiry = now()->diffInDays($expiresAt, false);
            $warning = null;
            if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
                $warning = "License key will expire in {$daysUntilExpiry} day(s).";
            }

            return [
                'valid' => true,
                'message' => 'License key valid.',
                'expires_at' => $expiresAt,
                'days_remaining' => $daysUntilExpiry,
                'warning' => $warning
            ];
        } catch (\Exception $e) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::error('Error parsing expiry date: ' . $e->getMessage());
            }
            return [
                'valid' => false,
                'message' => 'Invalid expiry date format. The application cannot run.',
                'error' => 'INVALID_DATE'
            ];
        }
    }

    /**
     * Generate license key
     */
    public function generateLicense(string $key, Carbon $expiresAt): bool
    {
        try {
            // Ensure storage/app directory exists
            $storageAppPath = storage_path('app');
            if (!File::isDirectory($storageAppPath)) {
                File::makeDirectory($storageAppPath, 0755, true);
            }

            // Get domain safely (handle console context)
            $domain = 'localhost';
            try {
                if (app()->runningInConsole()) {
                    $domain = gethostname() ?: 'localhost';
                } else {
                    $domain = request()->getHost() ?? 'localhost';
                }
            } catch (\Exception $e) {
                $domain = 'localhost';
            }

            $data = [
                'key' => $key,
                'expires_at' => $expiresAt->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'domain' => $domain
            ];

            $content = json_encode($data, JSON_PRETTY_PRINT);
            
            if ($content === false) {
                Log::error('Error encoding license data: ' . json_last_error_msg());
                return false;
            }

            File::put($this->licensePath, $content);
            
            // Set file permissions (readable only by owner) - only on Unix systems
            if (PHP_OS_FAMILY !== 'Windows') {
                @chmod($this->licensePath, 0600);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error generating license: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Delete license key
     */
    public function deleteLicense(): bool
    {
        if (!$this->hasLicense()) {
            return false;
        }

        try {
            // Use native PHP unlink as fallback
            try {
                if (class_exists('Illuminate\Support\Facades\File')) {
                    return \Illuminate\Support\Facades\File::delete($this->licensePath);
                } else {
                    return @unlink($this->licensePath);
                }
            } catch (\Exception $e) {
                return @unlink($this->licensePath);
            }
        } catch (\Exception $e) {
            if (class_exists('Illuminate\Support\Facades\Log')) {
                \Illuminate\Support\Facades\Log::error('Error deleting license: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Get license info
     */
    public function getLicenseInfo(): array
    {
        $data = $this->getLicenseData();
        $validation = $this->validateLicense();

        return [
            'exists' => $this->hasLicense(),
            'valid' => $validation['valid'],
            'key' => $data['key'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'domain' => $data['domain'] ?? null,
            'message' => $validation['message'],
            'days_remaining' => $validation['days_remaining'] ?? null,
            'warning' => $validation['warning'] ?? null
        ];
    }
}

