<?php

use App\Services\Import\CertificateFileMerger;
use App\Services\Import\DownloadedFile;
use setasign\Fpdi\Tcpdf\Fpdi;

function makeTestPdf(int $pages = 1): string
{
    $pdf = new Fpdi();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    for ($i = 0; $i < $pages; $i++) {
        $pdf->AddPage();
    }
    $path = tempnam(sys_get_temp_dir(), 'test-pdf-').'.pdf';
    $pdf->Output($path, 'F');

    return $path;
}

function makeTestImage(): string
{
    $path = tempnam(sys_get_temp_dir(), 'test-img-').'.png';
    $image = imagecreatetruecolor(100, 100);
    imagepng($image, $path);
    imagedestroy($image);

    return $path;
}

it('restituisce il file così com\'è se è l\'unico', function () {
    $file = new DownloadedFile(makeTestPdf(), 'application/pdf', 'pdf');

    $result = (new CertificateFileMerger())->merge([$file]);

    expect($result)->toBe($file);
});

it('unisce più PDF in un unico file con tutte le pagine', function () {
    $file1 = new DownloadedFile(makeTestPdf(2), 'application/pdf', 'pdf');
    $file2 = new DownloadedFile(makeTestPdf(1), 'application/pdf', 'pdf');

    $result = (new CertificateFileMerger())->merge([$file1, $file2]);

    expect($result->extension)->toBe('pdf');
    expect(file_exists($result->path))->toBeTrue();

    $pageCount = (new Fpdi())->setSourceFile($result->path);
    expect($pageCount)->toBe(3);
});

it('include un\'immagine come pagina del PDF unito', function () {
    $pdfFile = new DownloadedFile(makeTestPdf(1), 'application/pdf', 'pdf');
    $imageFile = new DownloadedFile(makeTestImage(), 'image/png', 'png');

    $result = (new CertificateFileMerger())->merge([$pdfFile, $imageFile]);

    $pageCount = (new Fpdi())->setSourceFile($result->path);
    expect($pageCount)->toBe(2);
});
