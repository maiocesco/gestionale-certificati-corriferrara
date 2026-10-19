<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dimensione massima dei certificati caricati
    |--------------------------------------------------------------------------
    |
    | In megabyte, configurabile tramite CERTIFICATE_MAX_SIZE_MB.
    |
    */
    'max_size_mb' => (int) env('CERTIFICATE_MAX_SIZE_MB', 10),

    /*
    |--------------------------------------------------------------------------
    | Estensioni ammesse
    |--------------------------------------------------------------------------
    |
    | Immagine (JPG, PNG, HEIC) o PDF, come da specifica. La validazione
    | controlla anche il MIME type reale del file, non solo l'estensione.
    |
    */
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'heic', 'heif', 'pdf'],

];
