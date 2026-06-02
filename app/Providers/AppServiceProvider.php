<?php

namespace App\Providers;

use App\Models\DispatchRequest;
use App\Services\Ocr\DocumentOcrEngine;
use App\Services\Ocr\StubDocumentOcrEngine;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentOcrEngine::class, StubDocumentOcrEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('dispatchRequest', function (string $value) {
            return DispatchRequest::withTrashed()->findOrFail((int) $value);
        });

        Route::bind('signedDocumentVersion', function (string $value) {
            return \App\Models\SignedDocumentVersion::query()->findOrFail((int) $value);
        });
    }
}
