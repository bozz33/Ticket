<?php

namespace Ticket\MediaDocuments;

use Illuminate\Support\ServiceProvider;
use Ticket\MediaDocuments\Contracts\QrCodeRenderer;
use Ticket\MediaDocuments\Infrastructure\Laravel\ChillerlanQrCodeRenderer;

class MediaDocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QrCodeRenderer::class, ChillerlanQrCodeRenderer::class);
    }
}
