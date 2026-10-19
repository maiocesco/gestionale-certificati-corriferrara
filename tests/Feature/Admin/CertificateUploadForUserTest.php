<?php

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->athlete = User::factory()->create();
});

it('permette all\'amministratore di caricare un certificato per conto di un atleta', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.certificates.store', $this->athlete), [
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
        'file' => UploadedFile::fake()->create('certificato.pdf', 500, 'application/pdf'),
    ]);

    $response->assertRedirect(route('admin.users.edit', $this->athlete));

    $certificate = $this->athlete->certificates()->first();
    expect($certificate)->not->toBeNull();
    expect($certificate->uploaded_by)->toBe($this->admin->id);
    expect($certificate->user_id)->toBe($this->athlete->id);
    expect($certificate->status)->toBe(CertificateStatus::Pending);
});

it('impedisce a un atleta di caricare un certificato per conto di un altro atleta tramite la rotta admin', function () {
    $other = User::factory()->create();

    $this->actingAs($this->athlete)->post(route('admin.users.certificates.store', $other), [
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
        'file' => UploadedFile::fake()->create('certificato.pdf', 500, 'application/pdf'),
    ])->assertForbidden();
});
