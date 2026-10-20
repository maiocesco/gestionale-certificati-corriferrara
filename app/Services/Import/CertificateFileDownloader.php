<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Scarica un file pubblicamente condiviso da Google Drive a partire dal
 * link "open?id=...". Per i file più grandi Drive interpone una pagina
 * di avviso ("impossibile verificare la presenza di virus"): viene
 * gestita automaticamente recuperando il token di conferma e i cookie
 * di sessione dalla prima risposta.
 */
class CertificateFileDownloader
{
    private const MIME_TO_EXTENSION = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    /**
     * @throws RuntimeException se il link non è valido o il file non è scaricabile
     */
    public function download(string $driveUrl): DownloadedFile
    {
        $id = $this->extractFileId($driveUrl);

        if ($id === null) {
            throw new RuntimeException("Link Google Drive non riconosciuto: {$driveUrl}");
        }

        $downloadUrl = 'https://drive.google.com/uc?export=download&id='.$id;

        $response = Http::timeout(60)->get($downloadUrl);

        if (! $response->successful()) {
            throw new RuntimeException("Download fallito (HTTP {$response->status()}) per {$driveUrl}");
        }

        if ($this->isHtmlResponse($response)) {
            $response = $this->retryWithConfirmation($downloadUrl, $response);
        }

        if ($this->isHtmlResponse($response)) {
            throw new RuntimeException("Il file non è scaricabile automaticamente (permessi o conferma richiesta): {$driveUrl}");
        }

        $mimeType = $this->normalizeMime($response->header('Content-Type'));
        $extension = self::MIME_TO_EXTENSION[$mimeType] ?? $this->extensionFromContentDisposition($response->header('Content-Disposition'));

        if ($extension === null) {
            throw new RuntimeException("Tipo di file non riconosciuto ({$mimeType}) per {$driveUrl}");
        }

        $path = tempnam(sys_get_temp_dir(), 'cert-import-').'.'.$extension;
        file_put_contents($path, $response->body());

        return new DownloadedFile($path, $mimeType, $extension);
    }

    private function extractFileId(string $url): ?string
    {
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function isHtmlResponse($response): bool
    {
        return str_contains($this->normalizeMime($response->header('Content-Type')), 'text/html');
    }

    private function normalizeMime(?string $contentType): string
    {
        return strtolower(trim(explode(';', $contentType ?? '')[0]));
    }

    private function retryWithConfirmation(string $downloadUrl, $firstResponse)
    {
        if (! preg_match('/confirm=([0-9A-Za-z_-]+)/', $firstResponse->body(), $matches)) {
            return $firstResponse;
        }

        $cookies = collect($firstResponse->headers()['Set-Cookie'] ?? [])
            ->map(fn (string $cookie) => explode(';', $cookie)[0])
            ->implode('; ');

        return Http::timeout(60)
            ->withHeaders($cookies !== '' ? ['Cookie' => $cookies] : [])
            ->get($downloadUrl, ['confirm' => $matches[1]]);
    }

    private function extensionFromContentDisposition(?string $contentDisposition): ?string
    {
        if ($contentDisposition === null) {
            return null;
        }

        if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $contentDisposition, $matches)) {
            $extension = strtolower(pathinfo(urldecode($matches[1]), PATHINFO_EXTENSION));

            return $extension !== '' ? $extension : null;
        }

        return null;
    }
}
