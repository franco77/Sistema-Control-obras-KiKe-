<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotePdfController;
use App\Livewire\Admin\Calendar\CalendarBoard;
use App\Livewire\Admin\Catalog\CatalogManager;
use App\Livewire\Admin\Clients\ClientForm;
use App\Livewire\Admin\Clients\ClientIndex;
use App\Livewire\Admin\Clients\ClientShow;
use App\Livewire\Admin\Conversations\ConversationIndex;
use App\Livewire\Admin\Conversations\ConversationShow;
use App\Livewire\Admin\Dashboard\Dashboard;
use App\Livewire\Admin\Documents\DocumentIndex;
use App\Livewire\Admin\Projects\ProjectBoard;
use App\Livewire\Admin\Projects\ProjectIndex;
use App\Livewire\Admin\Projects\ProjectShow;
use App\Livewire\Admin\Providers\ProviderForm;
use App\Livewire\Admin\Providers\ProviderIndex;
use App\Livewire\Admin\Providers\ProviderShow;
use App\Livewire\Admin\Quotes\QuoteBuilder;
use App\Livewire\Admin\Quotes\QuoteIndex;
use App\Livewire\Admin\Quotes\QuoteShow;
use App\Livewire\Admin\Settings\SettingsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Breeze redirige a «dashboard» tras autenticarse; aqui el panel es /panel.
Route::redirect('/dashboard', '/panel')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
| Todo el back-office son componentes Livewire de página completa. La
| autorización fina se resuelve en las policies y en los propios componentes.
*/
Route::middleware(['auth', 'verified'])->prefix('panel')->as('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    // Clientes e inmuebles ------------------------------------------------
    Route::get('clientes', ClientIndex::class)->name('clients.index');
    Route::get('clientes/nuevo', ClientForm::class)->name('clients.create');
    Route::get('clientes/{client}', ClientShow::class)->name('clients.show');
    Route::get('clientes/{client}/editar', ClientForm::class)->name('clients.edit');

    // Proveedores ---------------------------------------------------------
    Route::get('proveedores', ProviderIndex::class)->name('providers.index');
    Route::get('proveedores/nuevo', ProviderForm::class)->name('providers.create');
    Route::get('proveedores/{provider}', ProviderShow::class)->name('providers.show');
    Route::get('proveedores/{provider}/editar', ProviderForm::class)->name('providers.edit');

    // Presupuestos --------------------------------------------------------
    Route::get('presupuestos', QuoteIndex::class)->name('quotes.index');
    Route::get('presupuestos/nuevo', QuoteBuilder::class)->name('quotes.create');
    Route::get('presupuestos/{quote}', QuoteShow::class)->name('quotes.show');
    Route::get('presupuestos/{quote}/editar', QuoteBuilder::class)->name('quotes.edit');
    Route::get('presupuestos/{quote}/pdf', [QuotePdfController::class, 'show'])->name('quotes.pdf');

    // Obras ---------------------------------------------------------------
    Route::get('obras', ProjectIndex::class)->name('projects.index');
    Route::get('obras/tablero', ProjectBoard::class)->name('projects.board');
    Route::get('obras/{project}', ProjectShow::class)->name('projects.show');

    // Agenda, catálogo, documentos y mensajes -----------------------------
    Route::get('agenda', CalendarBoard::class)->name('calendar.index');
    Route::get('catalogo', CatalogManager::class)->name('catalog.index');
    Route::get('documentos', DocumentIndex::class)->name('documents.index');
    Route::get('mensajes', ConversationIndex::class)->name('conversations.index');
    Route::get('mensajes/{conversation}', ConversationShow::class)->name('conversations.show');

    // Configuración -------------------------------------------------------
    Route::get('configuracion', SettingsPage::class)
        ->middleware('role:admin')
        ->name('settings.index');

    // Ficheros servidos con control de acceso ------------------------------
    Route::get('documentos/{document}/descargar', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('fotos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
