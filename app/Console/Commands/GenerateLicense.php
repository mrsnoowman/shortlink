<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GenerateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:generate 
                            {--key= : License key (optional, will generate if not provided)}
                            {--days=365 : Number of days until expiration}
                            {--date= : Expiration date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new license key for the application';

    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if license already exists
        if ($this->licenseService->hasLicense()) {
            if (!$this->confirm('A license key already exists. Do you want to replace it?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Get or generate key
        $key = $this->option('key');
        if (!$key) {
            $key = Str::random(32);
            $this->info("Generated license key: {$key}");
        }

        // Get expiration date
        $expiresAt = null;
        if ($this->option('date')) {
            try {
                $expiresAt = Carbon::parse($this->option('date'));
            } catch (\Exception $e) {
                $this->error('Invalid date format. Use Y-m-d (example: 2025-12-31)');
                return 1;
            }
        } else {
            $days = (int) $this->option('days');
            $expiresAt = Carbon::now()->addDays($days);
        }

        // Generate license
        try {
            if ($this->licenseService->generateLicense($key, $expiresAt)) {
                $this->info('✓ License key generated successfully!');
                $this->line('');
                $this->line("Key: {$key}");
                $this->line("Expires at: {$expiresAt->format('d M Y H:i:s')}");
                $this->line("Days remaining: " . now()->diffInDays($expiresAt) . " day(s)");
                $this->line('');
                $this->warn('⚠ IMPORTANT: Store this license key securely!');
                return 0;
            } else {
                $this->error('✗ Failed to generate license key.');
                $this->line('');
                $this->warn('Possible causes:');
                $this->line('  - The storage/app directory could not be created');
                $this->line('  - File permissions are insufficient');
                $this->line('  - Disk full or I/O error');
                $this->line('');
                $this->line('Check storage/logs/laravel.log for error details.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->line('');
            $this->line('Detail: ' . $e->getTraceAsString());
            return 1;
        }
    }
}

