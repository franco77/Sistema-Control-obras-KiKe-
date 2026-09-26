<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\Portal\PortalEntryController;
use App\Http\Controllers\QuotePdfController;
use App\Livewire\Portal\ExtraDecision;
use App\Livewire\Portal\ProjectDocuments;
use App\Livewire\Portal\ProjectExtras;
use App\Livewire\Portal\ProjectGallery;
use App\Livewire\Portal\ProjectIncidents;
use App\Livewire\Portal\ProjectMessages;
use App\Livewire\Portal\ProjectOverview;
use App\Livewire\Portal\ProjectTasks;
use App\Livewire\Portal\QuoteReview;
use App\Livewire\Portal\Provider\ProviderTasks;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal sin login (cliente y proveedor)
|--------------------------------------------------------------------------
| Flujo: el enlace del email entra por /portal/t/{token}, se canjea por una
| sesión de portal y se redirige a una URL limpia sin token. A partir de ahí
| el middleware `portal` revalida el token en cada petición y `portal.can`
| exige el permiso concreto de cada acción.
|
| Este fichero se carga con prefijo "portal" y nombres "portal.*".
*/

Route::get('t/{token}', [PortalEntryController::class, 'enter'])
    ->name('enter')
    ->where('token', '[A-Za-z0-9\-_]{16,128}');

Route::get('acceso-caducado', [PortalEntryController::class, 'expired'])->name('expired');
Route::post('salir', [PortalEntryController::class, 'leave'])->name('leave');

Route::middleware('portal')->group(function () {
    // --- Presupuesto -----------------------------------------------------
    Route::get('presupuesto', QuoteReview::class)
        ->middleware('portal.can:quote.view')
        ->name('quote');

    Route::get('presupuesto/{quote}/pdf', [QuotePdfController::class, 'show'])
        ->middleware('portal.can:quote.download')
        ->name('quote.pdf');

    // --- Obra ------------------------------------------------------------
    Route::middleware('portal.can:project.view')->group(function () {
        Route::get('obra', ProjectOverview::class)->name('project');
        Route::get('obra/tareas', ProjectTasks::class)->name('project.tasks');
        Route::get('obra/fotos', ProjectGallery::class)->name('project.gallery');
        Route::get('obra/incidencias', ProjectIncidents::class)->name('project.incidents');
        Route::get('obra/extras', ProjectExtras::class)->name('project.extras');
        Route::get('obra/documentos', ProjectDocuments::class)->name('project.documents');
        Route::get('obra/mensajes', ProjectMessages::class)->name('project.messages');
    });

    // --- Extra puntual enviado por su propio enlace ----------------------
    Route::get('extra', ExtraDecision::class)
        ->middleware('portal.can:extra.view')
        ->name('extra');

    // --- Portal del proveedor --------------------------------------------
    Route::get('proveedor', ProviderTasks::class)
        ->middleware('portal.can:provider.tasks')
        ->name('provider');

    // --- Ficheros --------------------------------------------------------
    Route::get('documentos/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('fotos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
});
