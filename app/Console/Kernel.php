<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\LicenseService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run Telegram notification command every minute
        $schedule->command('telegram:send-notifications')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Run the console application.
     */
    public function handle($input, $output = null)
    {
        // Skip license validation for bootstrap/system commands
        $commandName = $input->getFirstArgument();
        $skipCommands = [
            'license:generate',
            'license:validate',
            'list',
            'help',
            'package:discover',
            'config:cache',
            'config:clear',
            'route:cache',
            'route:clear',
            'view:cache',
            'view:clear',
            'optimize',
            'optimize:clear',
            'key:generate',
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'db:seed',
        ];
        
        // Only validate license for user commands, not system/bootstrap commands
        if ($commandName && !in_array($commandName, $skipCommands)) {
            try {
                // Check if Laravel is fully bootstrapped
                if (!app()->bound('files')) {
                    // Laravel not bootstrapped yet, skip validation
                    return parent::handle($input, $output);
                }
                
                $licenseService = app(LicenseService::class);
                $validation = $licenseService->validateLicense();

                if (!$validation['valid']) {
                    if ($output) {
                        $output->writeln('<error>✗ ' . $validation['message'] . '</error>');
                        $output->writeln('');
                        $output->writeln('<comment>The application cannot run because the license key is invalid or expired.</comment>');
                        $output->writeln('<comment>Use "php artisan license:generate" to generate a new license key.</comment>');
                    } else {
                        echo "✗ " . $validation['message'] . "\n\n";
                        echo "The application cannot run because the license key is invalid or expired.\n";
                        echo "Use \"php artisan license:generate\" to generate a new license key.\n";
                    }
                    return 1;
                }
            } catch (\Exception $e) {
                // If license service fails (e.g., during bootstrap), skip validation
                // This allows composer install and other bootstrap commands to work
            }
        }

        return parent::handle($input, $output);
    }
}

