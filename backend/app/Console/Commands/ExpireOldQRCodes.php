<?php

namespace App\Console\Commands;

use App\Features\Checkout\Models\Ticket;
use Illuminate\Console\Command;

class ExpireOldQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tickets:expire-qr-codes
                            {--dry-run : Preview expired tickets without modifying them}';

    /**
     * The console command description.
     */
    protected $description = 'Invalidate expired QR codes by clearing qr_code_data, qr_code_secret, and expiry timestamps';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $query = Ticket::whereNotNull('qr_code_expires_at')
            ->where('qr_code_expires_at', '<', $now)
            ->whereNotNull('qr_code_data');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No expired QR codes found.');
            return 0;
        }

        $this->warn("Found {$total} expired QR code(s).");

        if ($this->option('dry-run')) {
            $this->table(
                ['id', 'ticket_id', 'expired_at', 'qr_code_data'],
                $query->get(['id', 'ticket_id', 'qr_code_expires_at', 'qr_code_data'])->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_id' => $ticket->ticket_id,
                        'expired_at' => $ticket->qr_code_expires_at,
                        'qr_code_data' => substr((string) $ticket->qr_code_data, 0, 40) . '...',
                    ];
                })
            );

            $this->info('Dry run complete. No changes were made.');
            return 0;
        }

        $updated = $query->update([
            'qr_code_data' => null,
            'qr_code_secret' => null,
            'qr_code_expires_at' => null,
            'qr_code_scanned_count' => 0,
            'last_qr_scan_at' => null,
        ]);

        $this->info("Expired {$updated} QR code(s) successfully.");

        // Re-fetch expired records to confirm
        $remaining = (clone $query)->count();
        if ($remaining > 0) {
            $this->warn("Warning: {$remaining} expired QR code(s) still present after cleanup.");
            return 1;
        }

        return 0;
    }
}
