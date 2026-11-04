<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'orders:expire-pending-payments {--dry-run : Report eligible orders without changing them}';

    protected $description = 'Cancel online orders with payment pending for at least 30 minutes and release reserved stock';

    public function handle(): int
    {
        $count = OrderService::expirePendingPayments((bool) $this->option('dry-run'));

        $message = $this->option('dry-run')
            ? "Would expire {$count} pending online order(s)."
            : "Expired {$count} pending online order(s).";

        $this->info($message);

        return self::SUCCESS;
    }
}
