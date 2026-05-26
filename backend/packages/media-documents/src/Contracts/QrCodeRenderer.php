<?php

namespace Ticket\MediaDocuments\Contracts;

interface QrCodeRenderer
{
    public function renderSvg(string|array $payload): string;

    public function renderDataUri(string|array $payload): string;
}
