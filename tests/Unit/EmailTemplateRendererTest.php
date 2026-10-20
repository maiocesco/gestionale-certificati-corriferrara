<?php

use App\Enums\EmailTemplateKey;
use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->renderer = new EmailTemplateRenderer();
});

it('sostituisce i segnaposto ed effettua l\'escape dei valori nella versione HTML', function () {
    EmailTemplate::factory()->create(['key' => EmailTemplateKey::Signature->value, 'body_markdown' => '']);

    $rendered = $this->renderer->renderTemplate(
        'Ciao {{nome}}',
        'Benvenuto {{nome}}.',
        ['nome' => '<b>Mario</b>']
    );

    expect($rendered->subject)->toBe('Ciao Mario');
    expect($rendered->html)->toContain('&lt;b&gt;Mario&lt;/b&gt;');
    expect($rendered->html)->not->toContain('<b>Mario</b>');
    expect($rendered->text)->toContain('Benvenuto Mario.');
    expect($rendered->text)->not->toContain('<b>');
});

it('aggiunge la firma comune in fondo al messaggio', function () {
    EmailTemplate::factory()->create(['key' => EmailTemplateKey::Signature->value, 'body_markdown' => 'FIRMA_DI_TEST']);

    $rendered = $this->renderer->renderTemplate('Oggetto', 'Corpo del messaggio.', []);

    expect($rendered->text)->toContain('Corpo del messaggio.')
        ->and($rendered->text)->toContain('FIRMA_DI_TEST')
        ->and($rendered->html)->toContain('FIRMA_DI_TEST');
});

it('lascia vuoto un segnaposto senza valore corrispondente', function () {
    EmailTemplate::factory()->create(['key' => EmailTemplateKey::Signature->value, 'body_markdown' => '']);

    $rendered = $this->renderer->renderTemplate(null, 'Valore: {{non_fornito}}.', []);

    expect(trim($rendered->text))->toBe('Valore: .');
    expect($rendered->subject)->toBeNull();
});

it('render() legge il testo salvato per una chiave e applica gli stessi segnaposto', function () {
    EmailTemplate::factory()->create([
        'key' => EmailTemplateKey::Welcome->value,
        'subject' => 'Benvenuto {{nome}}',
        'body_markdown' => 'Ciao {{nome}}, la tua email è {{email}}.',
    ]);
    EmailTemplate::factory()->create(['key' => EmailTemplateKey::Signature->value, 'body_markdown' => '—']);

    $rendered = $this->renderer->render(EmailTemplateKey::Welcome, [
        'nome' => 'Giulia',
        'email' => 'giulia@example.com',
    ]);

    expect($rendered->subject)->toBe('Benvenuto Giulia');
    expect($rendered->text)->toContain('Ciao Giulia, la tua email è giulia@example.com.');
});
