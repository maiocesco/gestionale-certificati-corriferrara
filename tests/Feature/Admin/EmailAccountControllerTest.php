<?php

use App\Enums\EmailAccountPurpose;
use App\Mail\AccountTestEmail;
use App\Models\EmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    EmailAccount::factory()->unconfigured()->create(['purpose' => EmailAccountPurpose::Pec->value]);
    EmailAccount::factory()->unconfigured()->create(['purpose' => EmailAccountPurpose::Classic->value]);
});

it('impedisce a un non amministratore di accedere alla pagina degli account email', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.email-accounts.index'))->assertForbidden();
});

it('mostra la pagina degli account email all\'amministratore', function () {
    $this->actingAs($this->admin)->get(route('admin.email-accounts.index'))->assertOk();
});

it('aggiorna un account email e cifra la password', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.email-accounts.update', EmailAccountPurpose::Classic->value), [
        '_purpose' => EmailAccountPurpose::Classic->value,
        'from_address' => 'info@corriferrara.test',
        'from_name' => 'A.S.D. Corriferrara',
        'host' => 'smtp.corriferrara.test',
        'port' => 587,
        'encryption' => 'starttls',
        'username' => 'info',
        'password' => 'segreto-123',
    ]);

    $response->assertRedirect(route('admin.email-accounts.index'));

    $account = EmailAccount::where('purpose', EmailAccountPurpose::Classic->value)->first();
    expect($account->from_address)->toBe('info@corriferrara.test');
    expect($account->host)->toBe('smtp.corriferrara.test');
    expect($account->password)->toBe('segreto-123');
    expect($account->getAttributes()['password'])->not->toBe('segreto-123'); // salvata cifrata
});

it('non sovrascrive la password esistente se il campo è lasciato vuoto', function () {
    $account = EmailAccount::where('purpose', EmailAccountPurpose::Classic->value)->first();
    $account->update(['host' => 'smtp.corriferrara.test', 'from_address' => 'info@corriferrara.test', 'password' => 'vecchia-password']);

    $this->actingAs($this->admin)->put(route('admin.email-accounts.update', EmailAccountPurpose::Classic->value), [
        '_purpose' => EmailAccountPurpose::Classic->value,
        'from_address' => 'info@corriferrara.test',
        'host' => 'smtp.corriferrara.test',
        'port' => 587,
        'encryption' => 'starttls',
        'password' => '',
    ]);

    expect($account->refresh()->password)->toBe('vecchia-password');
});

it('invia un\'email di prova per l\'account scelto', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.email-accounts.test', EmailAccountPurpose::Pec->value));

    $response->assertRedirect(route('admin.email-accounts.index'));
    Mail::assertSent(AccountTestEmail::class, fn ($mail) => $mail->purpose === EmailAccountPurpose::Pec);
});
