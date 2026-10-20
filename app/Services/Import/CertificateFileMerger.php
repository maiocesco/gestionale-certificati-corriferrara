<?php

namespace App\Services\Import;

use Imagick;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

/**
 * Unisce più file scaricati (pagine diverse dello stesso certificato) in
 * un unico PDF, dato che il gestionale prevede un solo file per
 * certificato. Se riceve un solo file lo restituisce così com'è, senza
 * conversioni inutili.
 */
class CertificateFileMerger
{
    /**
     * @param  list<DownloadedFile>  $files
     */
    public function merge(array $files): DownloadedFile
    {
        if (count($files) === 1) {
            return $files[0];
        }

        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($files as $file) {
            if ($file->extension === 'pdf') {
                $this->appendPdfPages($pdf, $file->path);
            } else {
                $this->appendImagePage($pdf, $file);
            }
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'cert-merged-').'.pdf';
        $pdf->Output($outputPath, 'F');

        return new DownloadedFile($outputPath, 'application/pdf', 'pdf');
    }

    private function appendPdfPages(Fpdi $pdf, string $path): void
    {
        $pageCount = $pdf->setSourceFile($path);

        for ($page = 1; $page <= $pageCount; $page++) {
            $templateId = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
    }

    /**
     * Aggiunge l'immagine come pagina A4, convertendola prima in JPEG se
     * il formato (es. HEIC) non è direttamente supportato.
     */
    private function appendImagePage(Fpdi $pdf, DownloadedFile $file): void
    {
        $imagePath = $file->path;

        if (! in_array($file->extension, ['jpg', 'jpeg', 'png'], true)) {
            $imagePath = $this->convertToJpeg($file->path);
        }

        $pdf->AddPage('P', 'A4');
        $pdf->Image($imagePath, 10, 10, 190, 0, '', '', '', true, 300);
    }

    private function convertToJpeg(string $path): string
    {
        try {
            $image = new Imagick($path);
            $image->setImageFormat('jpeg');
            $jpegPath = tempnam(sys_get_temp_dir(), 'cert-import-convert-').'.jpg';
            $image->writeImage($jpegPath);

            return $jpegPath;
        } catch (Throwable $e) {
            throw new \RuntimeException("Impossibile convertire l'immagine {$path} per l'unione nel PDF: {$e->getMessage()}");
        }
    }
}
