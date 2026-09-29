<?php

namespace App\Console\Commands;

use App\Services\WhatsAppNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppNotifications extends Command
{
    protected $signature = 'whatsapp:process
        {--interval=2 : Seconds to sleep between polling cycles}
        {--once : Process all pending and exit}';

    protected $description = 'Process pending WhatsApp notifications from the pending_notifications table via OpenClaw';

    public function handle(WhatsAppNotifier $whatsapp): int
    {
        $once = $this->option('once');
        $interval = (int) $this->option('interval');

        if ($interval < 1) {
            $interval = 2;
        }

        if ($once) {
            $this->processBatch($whatsapp);
            return Command::SUCCESS;
        }

        $this->info('WhatsApp notification processor started (polling every ' . $interval . 's)');
        $this->info('Press Ctrl+C to stop.');

        while (true) {
            try {
                $this->processBatch($whatsapp);
            } catch (\Exception $e) {
                Log::error('WhatsApp processor error: ' . $e->getMessage());
                $this->error('Error: ' . $e->getMessage());
            }

            sleep($interval);
        }
    }

    protected function processBatch(WhatsAppNotifier $whatsapp): void
    {
        $pending = DB::table('pending_notifications')
            ->whereNull('sent_at')
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        foreach ($pending as $notification) {
            $this->line("Processing notification #{$notification->id} ({$notification->type}) for seller #{$notification->seller_id}");

            try {
                $phone = $whatsapp->formatPhone($notification->seller_phone);

                if (!$phone) {
                    $this->warn("No phone for notification #{$notification->id}, marking as sent anyway");
                    DB::table('pending_notifications')
                        ->where('id', $notification->id)
                        ->update(['sent_at' => now()]);
                    continue;
                }

                $success = $whatsapp->sendToWhatsApp($phone, $notification->message);

                if ($success) {
                    DB::table('pending_notifications')
                        ->where('id', $notification->id)
                        ->update(['sent_at' => now()]);
                    $this->info("✓ Sent notification #{$notification->id} to {$phone}");
                } else {
                    $this->warn("✗ Failed to send notification #{$notification->id} to {$phone}, will retry");
                }
            } catch (\Exception $e) {
                Log::error("Failed to process notification #{$notification->id}: " . $e->getMessage());
                $this->error("Exception for #{$notification->id}: " . $e->getMessage());
            }

            // Small delay between sends to avoid rate limits
            usleep(500000); // 0.5 seconds
        }
    }
}
