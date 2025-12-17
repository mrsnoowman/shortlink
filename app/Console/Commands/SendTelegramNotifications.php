<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\DomainStatusChange;
use App\Models\DomainCheck;
use App\Models\TargetUrl;
use App\Models\Shortlink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Telegram notifications to users about domain status changes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get token from env or use default
        $botToken = env('TELEGRAM_BOT_TOKEN', '8422912318:AAGxX8sld94TMHF1b_5M4FOyzYzDpmXB0ZE');
        
        if (!$botToken) {
            $this->error('Telegram bot token not configured. Please set TELEGRAM_BOT_TOKEN in .env');
            return 1;
        }

        $this->info('Starting Telegram notification process...');

        // Get all users with Telegram enabled
        $users = User::where('telegram_enabled', true)
            ->whereNotNull('telegram_chat_id')
            ->get();

        $totalSent = 0;
        $totalSkipped = 0;

        foreach ($users as $user) {
            // Check if it's time to send notification for this user
            $intervalMinutes = $user->telegram_interval_minutes ?? 5;
            $lastNotified = $user->last_telegram_notified_at;
            
            if ($lastNotified) {
                $minutesSinceLastNotification = now()->diffInMinutes($lastNotified);
                if ($minutesSinceLastNotification < $intervalMinutes) {
                    $this->line("Skipping user {$user->name} - interval not reached ({$minutesSinceLastNotification}/{$intervalMinutes} minutes)");
                    $totalSkipped++;
                    continue;
                }
            }

            // Get pending status changes for this user
            $pendingChanges = DomainStatusChange::where('user_id', $user->id)
                ->where('notified', false)
                ->orderBy('created_at', 'asc')
                ->with(['shortlink.alias', 'shortlink.targetUrls', 'targetUrl'])
                ->get();

            $anyMessageSent = false;
            $allSuccess = true;

            // Jika ada perubahan status, kirim pesan perubahan
            if ($pendingChanges->isNotEmpty()) {
                // Pisahkan perubahan antara domain checks dan shortlink (target URL)
                $domainCheckChanges = $pendingChanges->where('change_type', 'domain_check');
                $shortlinkChanges = $pendingChanges->where('change_type', 'target_url');

                // 1) Kirim laporan domain check (ringkasan semua domain yang dicek)
                if ($domainCheckChanges->isNotEmpty()) {
                    $domainMessage = $this->buildDomainCheckMessage($user, $domainCheckChanges);
                    $successDomain = $this->sendTelegramMessage($botToken, $user->telegram_chat_id, $domainMessage);

                    $anyMessageSent = $anyMessageSent || $successDomain;
                    $allSuccess = $allSuccess && $successDomain;
                }

                // 2) Kirim alert shortlink (perubahan domain/URL pada shortlink)
                if ($shortlinkChanges->isNotEmpty()) {
                    $shortlinkMessage = $this->buildShortlinkAlertMessage($user, $shortlinkChanges);
                    $successShortlink = $this->sendTelegramMessage($botToken, $user->telegram_chat_id, $shortlinkMessage);

                    $anyMessageSent = $anyMessageSent || $successShortlink;
                    $allSuccess = $allSuccess && $successShortlink;
                }

                if ($anyMessageSent && $allSuccess) {
                    // Mark all related changes as notified
                    $pendingChanges->each(function ($change) {
                        $change->update(['notified' => true]);
                    });
                }
            } else {
                // Tidak ada perubahan, kirim laporan status berkala (periodic report)
                $periodicDomainMessage = $this->buildPeriodicDomainCheckReport($user);
                $periodicShortlinkMessage = $this->buildPeriodicShortlinkReport($user);

                if ($periodicDomainMessage) {
                    $successDomain = $this->sendTelegramMessage($botToken, $user->telegram_chat_id, $periodicDomainMessage);
                    $anyMessageSent = $anyMessageSent || $successDomain;
                    $allSuccess = $allSuccess && $successDomain;
                }

                if ($periodicShortlinkMessage) {
                    $successShortlink = $this->sendTelegramMessage($botToken, $user->telegram_chat_id, $periodicShortlinkMessage);
                    $anyMessageSent = $anyMessageSent || $successShortlink;
                    $allSuccess = $allSuccess && $successShortlink;
                }
            }

            if ($anyMessageSent && $allSuccess) {
                // Update user's last notification time
                $user->update(['last_telegram_notified_at' => now()]);

                $messageType = $pendingChanges->isNotEmpty() ? "({$pendingChanges->count()} changes)" : "(periodic report)";
                $this->info("✓ Sent notification(s) to {$user->name} {$messageType}");
                $totalSent++;
            } else {
                if ($pendingChanges->isEmpty()) {
                    $this->line("No data to report for user {$user->name}");
                } else {
                    $this->error("✗ Failed to send one or more notifications to {$user->name}");
                }
            }
        }

        $this->info("\nCompleted: {$totalSent} sent, {$totalSkipped} skipped");
        return 0;
    }

    /**
     * Build notification message for domain checks (global domain status).
     */
    protected function buildDomainCheckMessage(User $user, $changes): string
    {
        $total = $changes->count();

        $message = "🔎 *Domain Check Report*\n\n";
        $message .= "Hello {$user->name},\n\n";
        $message .= "Here is the latest status for your monitored domains:\n\n";

        $blockedCount = 0;
        $activeCount = 0;

        $message .= "📊 *Domains with status change:* {$total}\n\n";

        foreach ($changes as $index => $change) {
            $statusBlocked = (bool) $change->new_status;
            $statusIcon = $statusBlocked ? '🚫' : '✅';
            $statusText = $statusBlocked ? 'Blocked' : 'Active';
            $domain = $change->domain ?? 'N/A';
            $number = $index + 1;

            $message .= "{$number}. {$domain} {$statusIcon} ({$statusText})\n";

            if ($statusBlocked) {
                $blockedCount++;
            } else {
                $activeCount++;
            }
        }

        $message .= "\n📌 *Summary:*\n";
        $message .= "• Blocked: {$blockedCount}\n";
        $message .= "• Active: {$activeCount}\n";
        $message .= "• Total Changes: {$total}\n";

        return $message;
    }

    /**
     * Build notification message for shortlink domain/URL alerts.
     */
    protected function buildShortlinkAlertMessage(User $user, $changes): string
    {
        $message = "🚨 *Shortlink Domain Alert*\n\n";
        $message .= "Hello {$user->name},\n\n";
        $message .= "We detected status changes on your shortlink target domains:\n\n";

        foreach ($changes as $change) {
            $isBlocked = (bool) $change->new_status;
            $statusIcon = $isBlocked ? '🚫' : '✅';
            $statusText = $isBlocked ? 'BLOCKED' : 'ACTIVE';

            $domain = $change->domain ?? 'N/A';
            $targetUrl = $change->url ?? 'N/A';

            $shortlink = $change->shortlink;
            $shortCode = $shortlink?->short_code ?? 'N/A';

            // Build full shortlink URL (use custom domain alias if present, otherwise APP_URL)
            $baseDomain = $shortlink?->alias?->custom_domain ?? config('app.url');
            $baseDomain = preg_replace('#^https?://#', '', (string) $baseDomain);
            $shortUrl = rtrim($baseDomain, '/') . '/' . $shortCode;

            $message .= "{$statusIcon} *Domain:* {$domain}\n";
            $message .= "📎 *Shortlink:* {$shortUrl}\n";
            $message .= "🔗 *Target URL:* {$targetUrl}\n";
            $message .= "📌 *Status:* {$statusText}\n";

            // Jika primary ter-block, cari dan tampilkan domain baru untuk redirect
            if ($isBlocked && $shortlink) {
                // Cek apakah ini adalah primary target yang ter-block
                // Note: After auto-switch, primary has changed, so we check using old_status
                $targetUrlModel = $change->targetUrl;
                
                // Cek apakah target URL ini adalah primary (saat ini atau sebelumnya)
                // Jika old_status = false (active) dan new_status = true (blocked), berarti baru ter-block
                $wasActive = !$change->old_status;
                $isNowBlocked = $change->new_status;
                
                if ($wasActive && $isNowBlocked) {
                    // Cari target URL baru yang aktif (primary baru atau target aktif pertama)
                    // Refresh shortlink untuk mendapatkan data terbaru setelah auto-switch
                    $shortlink->refresh();
                    $newPrimaryTarget = $shortlink->targetUrls()
                        ->where('is_blocked', false)
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($newPrimaryTarget) {
                        $message .= "🔄 *Automatically redirected to:* {$newPrimaryTarget->url} ✅ (ACTIVE)\n";
                    } else {
                        // Tidak ada target aktif, cek apakah ada fallback_url
                        $fallbackUrl = $shortlink->alias?->fallback_url;
                        if ($fallbackUrl) {
                            $message .= "🔄 *Fallback redirect to:* {$fallbackUrl}\n";
                        } else {
                            $message .= "⚠️ *No active target available*\n";
                        }
                    }
                }
            }

            $message .= "🕒 *Time:* " . $change->created_at->format('d M Y H:i:s') . "\n\n";
        }

        $message .= "✅ Your shortlink service will continue to operate using available active domains.\n";

        return $message;
    }

    /**
     * Build periodic domain check report (status saat ini tanpa perubahan).
     */
    protected function buildPeriodicDomainCheckReport(User $user): ?string
    {
        // Ambil semua domain checks untuk user ini
        $domainChecks = DomainCheck::where('user_id', $user->id)
            ->orderBy('domain', 'asc')
            ->get();

        if ($domainChecks->isEmpty()) {
            return null; // Tidak ada domain check, skip
        }

        $total = $domainChecks->count();
        $blockedCount = $domainChecks->where('is_blocked', true)->count();
        $activeCount = $domainChecks->where('is_blocked', false)->count();

        $message = "🔎 *Domain Check Report*\n\n";
        $message .= "Hello {$user->name},\n\n";
        $message .= "Here is the current status of your monitored domains:\n\n";

        $message .= "📊 *All Domains Status*\n";
        $message .= "Total Domains: {$total}\n\n";

        foreach ($domainChecks as $index => $domainCheck) {
            $statusIcon = $domainCheck->is_blocked ? '🚫' : '✅';
            $statusText = $domainCheck->is_blocked ? 'Blocked' : 'Active';
            $domain = $domainCheck->domain ?? 'N/A';
            $number = $index + 1;

            // Format: 1. https://google.com ✅
            $message .= "{$number}. {$domain} {$statusIcon}\n";
        }

        $message .= "\n📌 *Summary:*\n";
        $message .= "• Blocked: {$blockedCount}\n";
        $message .= "• Active: {$activeCount}\n";
        $message .= "• Total: {$total}\n";

        return $message;
    }

    /**
     * Build periodic shortlink report (status saat ini tanpa perubahan).
     */
    protected function buildPeriodicShortlinkReport(User $user): ?string
    {
        // Ambil semua shortlinks untuk user ini dengan target URLs
        $shortlinks = Shortlink::where('user_id', $user->id)
            ->with(['alias', 'targetUrls' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('id', 'asc');
            }])
            ->get();

        if ($shortlinks->isEmpty()) {
            return null; // Tidak ada shortlink, skip
        }

        $message = "📎 *Shortlink Status Report*\n\n";
        $message .= "Hello {$user->name},\n\n";
        $message .= "Here is the current status of your shortlinks:\n\n";

        $totalBlocked = 0;
        $totalActive = 0;

        foreach ($shortlinks as $shortlink) {
            $shortCode = $shortlink->short_code ?? 'N/A';
            
            // Bangun URL shortlink lengkap
            $baseDomain = $shortlink->alias?->custom_domain ?? config('app.url');
            $baseDomain = preg_replace('#^https?://#', '', (string) $baseDomain);
            $shortUrl = rtrim($baseDomain, '/') . '/' . $shortCode;

            $targetUrls = $shortlink->targetUrls;
            $activeTargets = $targetUrls->where('is_blocked', false);
            $blockedTargets = $targetUrls->where('is_blocked', true);
            
            $primaryTarget = $targetUrls->where('is_primary', true)->first();
            $activePrimary = $primaryTarget && !$primaryTarget->is_blocked;

            $message .= "📎 *Shortlink:* {$shortUrl}\n";
            
            if ($activePrimary) {
                $primaryDomain = parse_url($primaryTarget->url, PHP_URL_HOST) ?? $primaryTarget->url;
                $message .= "   ✅ Primary: {$primaryTarget->url}\n";
            } else {
                $firstActive = $activeTargets->first();
                if ($firstActive) {
                    $message .= "   ✅ Active: {$firstActive->url}\n";
                } else {
                    $message .= "   🚫 All targets blocked\n";
                }
            }

            $activeCount = $activeTargets->count();
            $blockedCount = $blockedTargets->count();
            
            if ($blockedCount > 0) {
                $message .= "   ⚠️ Blocked: {$blockedCount} target(s)\n";
            }

            $message .= "\n";

            if ($activeCount > 0) {
                $totalActive++;
            } else {
                $totalBlocked++;
            }
        }

        $message .= "📊 *Summary:*\n";
        $message .= "• Active Shortlinks: {$totalActive}\n";
        $message .= "• Blocked Shortlinks: {$totalBlocked}\n";
        $message .= "• Total: {$shortlinks->count()}\n";

        return $message;
    }

    /**
     * Send message to Telegram
     */
    protected function sendTelegramMessage(string $botToken, string $chatId, string $message): bool
    {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['ok']) && $data['ok'] === true;
            }

            Log::error('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram send error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
