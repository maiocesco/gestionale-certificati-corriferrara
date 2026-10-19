<?php

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->athlete = User::factory()->create();

    $file = UploadedFile::fake()->create('certificato.pdf', 500, 'application/pdf');
    $path = $file->storeAs("certificates/{$this->athlete->id}", 'test.pdf', 'local');

    $this->certificate = Certificate::factory()->for($this->athlete)->create([
        'file_path' => $path,
        'file_mime' => 'application/pdf',
        'file_original_name' => 'certificato.pdf',
    ]);
});

it('permette al proprietario di visualizzare il proprio file', function () {
    $response = $this->actingAs($this->athlete)->get(route('certificates.file', $this->certificate));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

it('permette all\'amministratore di visualizzare qualunque file', function () {
    $this->actingAs($this->admin)->get(route('certificates.file', $this->certificate))->assertOk();
});

it('impedisce a un altro atleta di visualizzare il file', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('certificates.file', $this->certificate))->assertForbidden();
});

it('registra ogni accesso al file nell\'audit log', function () {
    expect(AuditLog::count())->toBe(0);

    $this->actingAs($this->admin)->get(route('certificates.file', $this->certificate));

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('certificate.file.viewed');
    expect($log->auditable_id)->toBe($this->certificate->id);
    expect($log->auditable_type)->toBe(Certificate::class);
});
