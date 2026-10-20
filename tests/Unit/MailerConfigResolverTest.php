<?php

use App\Enums\EmailAccountPurpose;
use App\Enums\SmtpEncryption;
use App\Models\EmailAccount;
use App\Services\Mail\MailerConfigResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resolver = new MailerConfigResolver();
});

it('usa la configurazione salvata nel database quando l\'account è configurato', function () {
    EmailAccount::factory()->create([
        'purpose' => EmailAccountPurpose::Pec->value,
        'from_address' => 'pec@corriferrara.test',
        'from_name' => 'A.S.D. Corriferrara',
        'host' => 'smtp.pec.test',
        'port' => 465,
        'encryption' => SmtpEncryption::Ssl->value,
        'username' => 'pec-user',
        'password' => 'segreto',
    ]);

    $from = $this->resolver->prepare(EmailAccountPurpose::Pec);

    expect($from->address)->toBe('pec@corriferrara.test');
    expect($from->name)->toBe('A.S.D. Corriferrara');

    $mailerName = $this->resolver->mailerName(EmailAccountPurpose::Pec);
    expect(config("mail.mailers.{$mailerName}.host"))->toBe('smtp.pec.test');
    expect(config("mail.mailers.{$mailerName}.port"))->toBe(465);
    expect(config("mail.mailers.{$mailerName}.password"))->toBe('segreto');
    expect(config("mail.mailers.{$mailerName}.scheme"))->toBe('smtps');
});

it('usa il fallback su .env quando l\'account non è ancora configurato', function () {
    EmailAccount::factory()->unconfigured()->create(['purpose' => EmailAccountPurpose::Classic->value]);

    $from = $this->resolver->prepare(EmailAccountPurpose::Classic);

    expect($from->address)->toBe(config('mail.from.address'));
    expect($from->name)->toBe(config('mail.from.name'));

    $mailerName = $this->resolver->mailerName(EmailAccountPurpose::Classic);
    expect(config("mail.mailers.{$mailerName}"))->toBe(config('mail.mailers.smtp'));
});

it('usa il fallback su .env quando la riga dell\'account non esiste affatto', function () {
    $from = $this->resolver->prepare(EmailAccountPurpose::Classic);

    expect($from->address)->toBe(config('mail.from.address'));
});
