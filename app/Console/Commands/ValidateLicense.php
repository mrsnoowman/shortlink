<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenseService;

class ValidateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the current license key';

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
        $this->info('Validating license key...');
        $this->line('');

        $info = $this->licenseService->getLicenseInfo();
        $validation = $this->licenseService->validateLicense();

        if ($info['exists']) {
            $this->line("License Key: " . ($info['key'] ? substr($info['key'], 0, 8) . '...' : 'N/A'));
            $this->line("Created At: " . ($info['created_at'] ?? 'N/A'));
            $this->line("Domain: " . ($info['domain'] ?? 'N/A'));
            $this->line("Expires At: " . ($info['expires_at'] ?? 'N/A'));
            $this->line('');
        }

        if ($validation['valid']) {
            $this->info('✓ ' . $validation['message']);
            if (isset($validation['days_remaining'])) {
                $this->line("Days Remaining: {$validation['days_remaining']} hari");
            }
            if (isset($validation['warning'])) {
                $this->warn('⚠ ' . $validation['warning']);
            }
            return 0;
        } else {
            $this->error('✗ ' . $validation['message']);
            return 1;
        }
    }
}

