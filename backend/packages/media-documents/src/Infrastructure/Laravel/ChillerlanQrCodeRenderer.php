<?php

namespace Ticket\MediaDocuments\Infrastructure\Laravel;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Ticket\MediaDocuments\Contracts\QrCodeRenderer;

class ChillerlanQrCodeRenderer implements QrCodeRenderer
{
    public function renderSvg(string|array $payload): string
    {
        $svg = (string) (new QRCode(new QROptions([
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'outputBase64' => false,
        ])))->render($this->normalizePayload($payload));

        return trim($svg);
    }

    public function renderDataUri(string|array $payload): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->renderSvg($payload));
    }

    private function normalizePayload(string|array $payload): string
    {
        if (is_string($payload)) {
            return $payload;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
