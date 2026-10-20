<?php

namespace App\Services\Mail;

readonly class RenderedTemplate
{
    public function __construct(
        public ?string $subject,
        public string $html,
        public string $text,
    ) {}
}
