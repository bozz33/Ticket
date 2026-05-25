<?php

use App\Http\Controllers\Api\V1\OrganizationProfileController;
use App\Http\Controllers\Api\V1\Public\PublicAccessPassController;
use App\Http\Controllers\Api\V1\Public\PublicCallForProjectSubmissionController;
use App\Http\Controllers\Api\V1\Public\PublicContentController;
use App\Http\Controllers\Api\V1\Public\PublicFormSubmissionController;
use App\Http\Controllers\Api\V1\Public\PublicFrontPageController;
use App\Http\Controllers\Api\V1\Public\PublicMarketplaceSessionController;
use App\Http\Controllers\Api\V1\Public\PublicOnboardingController;
use App\Http\Controllers\Api\V1\Public\PublicPaymentController;
use App\Http\Controllers\Api\V1\Public\PublicReceiptVerificationController;
use App\Http\Controllers\Api\V1\Public\PublicTicketReservationController;
use App\Http\Controllers\Api\V1\PublicPlatformConfigurationController;
use App\Http\Controllers\Api\V1\PublicReferenceDataController;
use App\Http\Controllers\Api\V1\PublicTenantDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function (): void {
    Route::get('/platform/configuration', [PublicPlatformConfigurationController::class, 'show']);
    Route::get('/content', [PublicContentController::class, 'globalIndex']);
    Route::get('/content/filters', [PublicContentController::class, 'globalFilters']);
    Route::get('/content/categories', [PublicContentController::class, 'globalCategoryOverview']);
    Route::get('/content/cities', [PublicContentController::class, 'globalCityOverview']);
    Route::get('/content/speakers', [PublicContentController::class, 'globalSpeakerHighlights']);
    Route::get('/content/summary', [PublicContentController::class, 'globalSummary']);
    Route::get('/content/search/suggestions', [PublicContentController::class, 'globalSearchSuggestions']);
    Route::get('/content/{module}/{slug}/related', [PublicContentController::class, 'globalRelated']);
    Route::get('/content/{module}/{slug}', [PublicContentController::class, 'globalShow']);
    Route::post('/marketplace/session/exchange', [PublicMarketplaceSessionController::class, 'exchange'])
        ->middleware('throttle:tenant-auth');
    Route::get('/front/pages', [PublicFrontPageController::class, 'index']);
    Route::post('/onboarding/register', [PublicOnboardingController::class, 'register'])
        ->middleware('throttle:public-onboarding');
    Route::get('/references/countries', [PublicReferenceDataController::class, 'countries']);
    Route::get('/references/countries/{country}/states', [PublicReferenceDataController::class, 'states']);
    Route::get('/references/countries/{country}/cities', [PublicReferenceDataController::class, 'cities']);
    Route::get('/references/cities/search', [PublicReferenceDataController::class, 'searchCities']);
    Route::get('/resource-types', [PublicReferenceDataController::class, 'resourceTypes']);
});

Route::middleware(['initialize.tenant.route'])->group(function (): void {
    Route::get('/public/tenants/{tenant}/organization-profile', [OrganizationProfileController::class, 'showPublic']);
    Route::get('/public/tenants/{tenant}/organization-profile/catalog', [OrganizationProfileController::class, 'showPublicCatalog']);
    Route::get('/public/tenants/{tenant}/documents', [PublicTenantDocumentController::class, 'index']);
    Route::get('/public/tenants/{tenant}/documents/{document}', [PublicTenantDocumentController::class, 'show']);
    Route::get('/public/tenants/{tenant}/access-passes/{code}', [PublicAccessPassController::class, 'show'])
        ->middleware('throttle:public-pass-lookup');
    Route::get('/public/tenants/{tenant}/receipts/{receipt}/verify', [PublicReceiptVerificationController::class, 'show'])
        ->middleware('throttle:public-pass-lookup');
    Route::get('/public/tenants/{tenant}/content', [PublicContentController::class, 'index']);
    Route::get('/public/tenants/{tenant}/content/filters', [PublicContentController::class, 'filters']);
    Route::get('/public/tenants/{tenant}/forms/{formDefinition}', [PublicFormSubmissionController::class, 'show']);
    Route::post('/public/tenants/{tenant}/forms/{formDefinition}/submissions', [PublicFormSubmissionController::class, 'submit'])
        ->middleware('throttle:public-call-for-project-apply');
    Route::get('/public/tenants/{tenant}/content/{module}/{slug}', [PublicContentController::class, 'show']);
    Route::post('/public/tenants/{tenant}/calls-for-projects/{callForProject}/applications', PublicCallForProjectSubmissionController::class)
        ->middleware('throttle:public-call-for-project-apply');
    Route::get('/public/tenants/{tenant}/payment-options', [PublicPaymentController::class, 'options']);
    Route::post('/public/tenants/{tenant}/payments/initialize', [PublicPaymentController::class, 'initialize'])
        ->middleware('throttle:public-payment-initialize');
    Route::get('/public/tenants/{tenant}/payments/verify/{reference}', [PublicPaymentController::class, 'verify'])
        ->middleware('throttle:public-payment-verify');
    Route::post('/public/tenants/{tenant}/ticket-reservations', [PublicTicketReservationController::class, 'store'])
        ->middleware('throttle:public-payment-initialize');
    Route::get('/public/tenants/{tenant}/ticket-reservations/{reservation}', [PublicTicketReservationController::class, 'show'])
        ->middleware('throttle:public-pass-lookup');
    Route::delete('/public/tenants/{tenant}/ticket-reservations/{reservation}', [PublicTicketReservationController::class, 'destroy'])
        ->middleware('throttle:public-payment-initialize');
});
