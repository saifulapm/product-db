<?php

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')
    ->middleware([
        'panel:admin',
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
    ])
    ->group(function () {
        Route::post('mockups/{record}/upload-pdf', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'handlePdfUpload'])->name('mockups.upload-pdf');
        Route::post('mockups/{record}/update-product-status', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'updateProductStatus'])->name('mockups.update-product-status');
        Route::post('mockups/{record}/update-product-notes', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'updateProductNotes'])->name('mockups.update-product-notes');
        Route::post('mockups/{record}/save-product', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'saveProduct'])->name('mockups.save-product');
        Route::post('mockups/{record}/upload-product-image', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'uploadProductImage'])->name('mockups.upload-product-image');
        Route::post('mockups/{record}/add-comment', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'addComment'])->name('mockups.add-comment');
        Route::post('mockups/{record}/send-to-client', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'sendToClient'])->name('mockups.send-to-client');
        Route::get('mockups/sms-templates', [\App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission::class, 'getSmsTemplates'])->name('mockups.sms-templates');
    });
