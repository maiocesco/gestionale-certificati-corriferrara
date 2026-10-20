<?php

namespace App\Console\Commands;

use App\Services\ReminderSchedulingService;
use Illuminate\Console\Command;

class SendCertificateReminders extends Command
{
    protected $signature = 'certificates:send-reminders';

    protected $description = 'Accoda i promemoria di scadenza (30/10/3 giorni) e gli avvisi di scadenza (diffida/sospensione parziale) dovuti';

    public function handle(ReminderSchedulingService $service): int
    {
        $count = $service->sendDueNotifications();

        $this->info("Accodati {$count} avvisi.");

        return self::SUCCESS;
    }
}
