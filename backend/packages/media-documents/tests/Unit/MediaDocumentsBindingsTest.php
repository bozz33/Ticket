<?php

namespace Ticket\MediaDocuments\Tests\Unit;

use Tests\TestCase;
use Ticket\MediaDocuments\Contracts\QrCodeRenderer;

class MediaDocumentsBindingsTest extends TestCase
{
    public function test_qr_code_renderer_contract_is_bound(): void
    {
        $this->assertInstanceOf(QrCodeRenderer::class, app(QrCodeRenderer::class));
    }

    public function test_qr_code_renderer_returns_svg_data_uri(): void
    {
        $dataUri = app(QrCodeRenderer::class)->renderDataUri(['code' => 'PASS-001']);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }
}
