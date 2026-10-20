<?php

use App\Mail\RetentionNoticeEmail;
use App\Models\Certificate;
use App\Models\RetentionSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
});

it('avvisa gli amministratori quando compaiono nuovi certificati da eliminare', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $certificate = Certificate::factory()->rejected()->create(['expires_at' => now()->subYears(11)->toDateString()]);

    $this->artisan('certificates:retention-notify')->assertSuccessful();

    Mail::assertQueued(RetentionNoticeEmail::class, fn ($mail) => $mail->admin->is($admin) && $mail->count === 1);
    expect($certificate->refresh()->retention_notified_at)->not->toBeNull();
});

it('non avvisa di nuovo per un certificato già notificato', function () {
    Mail::fake();

    User::factory()->admin()->create();
    Certificate::factory()->rejected()->create([
        'expires_at' => now()->subYears(11)->toDateString(),
        'retention_notified_at' => now()->subMonth(),
    ]);

    $this->artisan('certificates:retention-notify')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('non invia nulla se non ci sono nuovi certificati da eliminare', function () {
    Mail::fake();

    User::factory()->admin()->create();
    Certificate::factory()->approved()->expiringInDays(10)->create();

    $this->artisan('certificates:retention-notify')->assertSuccessful();

    Mail::assertNothingQueued();
});
