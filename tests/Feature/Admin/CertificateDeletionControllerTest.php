<?php

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\RetentionSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
});

it('impedisce a un non amministratore di accedere all\'elenco', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.certificate-deletion.index'))->assertForbidden();
});

it('mostra solo i certificati oltre il periodo di conservazione', function () {
    $old = Certificate::factory()->rejected()->create(['expires_at' => now()->subYears(11)->toDateString()]);
    Certificate::factory()->approved()->expiringInDays(10)->create(); // valido, non deve comparire

    $response = $this->actingAs($this->admin)->get(route('admin.certificate-deletion.index'));

    $response->assertOk()->assertSee($old->user->fullName());
});

it('mostra la pagina di conferma per un certificato eliminabile', function () {
    $certificate = Certificate::factory()->rejected()->create(['expires_at' => now()->subYears(11)->toDateString()]);

    $this->actingAs($this->admin)
        ->get(route('admin.certificate-deletion.confirm-delete', $certificate))
        ->assertOk();
});

it('rifiuta di mostrare la conferma per un certificato non ancora eliminabile', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(10)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.certificate-deletion.confirm-delete', $certificate))
        ->assertNotFound();
});

it('elimina il file, riduce il certificato al record minimo e registra l\'audit log', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('certificato.pdf', 100, 'application/pdf');
    Storage::disk('local')->putFileAs('certificates/1', $file, 'test.pdf');

    $certificate = Certificate::factory()->rejected()->create([
        'expires_at' => now()->subYears(11)->toDateString(),
        'file_path' => 'certificates/1/test.pdf',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('admin.certificate-deletion.destroy', $certificate));

    $response->assertRedirect(route('admin.certificate-deletion.index'));
    Storage::disk('local')->assertMissing('certificates/1/test.pdf');

    $certificate->refresh();
    expect($certificate->file_path)->toBeNull();
    expect($certificate->deleted_by)->toBe($this->admin->id);

    expect(AuditLog::where('action', 'certificate.retention_deleted')->where('auditable_id', $certificate->id)->exists())->toBeTrue();
});

it('rifiuta di eliminare un certificato non ancora eliminabile anche forzando la richiesta', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(10)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.certificate-deletion.destroy', $certificate))
        ->assertNotFound();

    expect($certificate->refresh()->deleted_at)->toBeNull();
});
