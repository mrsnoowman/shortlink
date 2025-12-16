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
            if (!$this->confirm('License key sudah ada. Apakah Anda ingin menggantinya?')) {
                $this->info('Operasi dibatalkan.');
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
                $this->error('Format tanggal tidak valid. Gunakan format Y-m-d (contoh: 2025-12-31)');
                return 1;
            }
        } else {
            $days = (int) $this->option('days');
            $expiresAt = Carbon::now()->addDays($days);
        }

        // Generate license
        try {
            if ($this->licenseService->generateLicense($key, $expiresAt)) {
                $this->info('✓ License key berhasil dibuat!');
                $this->line('');
                $this->line("Key: {$key}");
                $this->line("Expires at: {$expiresAt->format('d M Y H:i:s')}");
                $this->line("Days remaining: " . now()->diffInDays($expiresAt) . " hari");
                $this->line('');
                $this->warn('⚠ PENTING: Simpan license key ini dengan aman!');
                return 0;
            } else {
                $this->error('✗ Gagal membuat license key.');
                $this->line('');
                $this->warn('Kemungkinan penyebab:');
                $this->line('  - Directory storage/app tidak dapat dibuat');
                $this->line('  - Permission file tidak mencukupi');
                $this->line('  - Disk penuh atau error I/O');
                $this->line('');
                $this->line('Cek log di storage/logs/laravel.log untuk detail error.');
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

