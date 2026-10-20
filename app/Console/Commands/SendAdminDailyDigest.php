<?php

namespace App\Console\Commands;

use App\Enums\CertificateStatus;
use App\Enums\UserRole;
use App\Mail\AdminDailyDigestEmail;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAdminDailyDigest extends Command
{
    protected $signature = 'admins:send-daily-digest';

    protected $description = 'Invia il riepilogo giornaliero (scadenze, certificati da approvare) agli amministratori che l\'hanno attivato';

    public function handle(): int
    {
        $pendingCount = Certificate::where('status', CertificateStatus::Pending->value)->count();
        $expiringCount = Certificate::valid()->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())->count();

        if ($pendingCount === 0 && $expiringCount === 0) {
            $this->info('Nessuna scadenza né certificato da approvare: nessun riepilogo da inviare.');

            return self::SUCCESS;
        }

        $admins = User::where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->where('daily_digest_enabled', true)
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new AdminDailyDigestEmail($admin, $pendingCount, $expiringCount));
        }

        $this->info("Riepilogo inviato a {$admins->count()} amministratori.");

        return self::SUCCESS;
    }
}
