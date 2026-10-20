<?php

namespace App\Services\Import;

final readonly class DownloadedFile
{
    public function __construct(
        public string $path,
        public string $mimeType,
        public string $extension,
    ) {}
}
