<?php

namespace Ticket\ContentTraining;

use Illuminate\Support\ServiceProvider;
use Ticket\ContentTraining\Application\TrainingService;
use Ticket\ContentTraining\Contracts\TrainingContentCatalog;

class ContentTrainingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TrainingContentCatalog::class, TrainingService::class);
    }
}
