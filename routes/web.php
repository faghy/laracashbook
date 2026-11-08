<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{MovimentoController, CatController, EntityController, DashboardController, UserController};
use App\Http\Middleware\InitializeSelectedEntity; // Assicurati che questo sia incluso

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Qui puoi registrare le rotte web per la tua applicazione. Queste
| rotte sono caricate dal RouteServiceProvider all'interno di un gruppo
| che contiene il middleware "web". Crea ora qualcosa di fantastico!
|
*/

// --- Rotte Pubbliche / Guest (non richiedono autenticazione) ---
Route::get('manual-login', [ProfileController::class, 'getInfo'])
    ->name('manual.login')->middleware('guest');
// Oltre al limite per email (LoginRequest), al massimo 10 tentativi al minuto per indirizzo IP
Route::post('manual-login', [ProfileController::class, 'manualLogin'])
    ->name('manual.login.post')->middleware(['guest', 'throttle:10,1']);

// Includi le rotte di autenticazione standard di Laravel (login, register, reset password, etc.)
require __DIR__.'/auth.php';


// --- Gruppo di Rotte Autenticate e Verificate ---
// Middleware 'auth' per utenti loggati, 'verified' per utenti con email verificata
Route::middleware(['auth', 'verified', 'active'])->group(function () {

    // Rotte del profilo utente
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('not_in_demo');
    Route::patch('/profile/entita-predefinita', [ProfileController::class, 'updateDefaultEntity'])->name('profile.default-entity');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware('not_in_demo');

    // --- Gruppo di Rotte che necessitano dell'entità inizializzata ---
    // Queste rotte usano anche il middleware InitializeSelectedEntity
    Route::middleware(InitializeSelectedEntity::class)->group(function() {

        // Rotta principale dopo il login (MovimentoController@index)
        Route::get('/', [MovimentoController::class, 'index']);

        // Rotte per la ricerca e i filtri dei movimenti per data
        Route::get('/movimenti/cercadata', [MovimentoController::class,'cercadata'])->name('movimenti.cercadata');
        Route::get('/movimenti/oggi', [MovimentoController::class,'today'])->name('movimenti.oggi');
        Route::get('/movimenti/ieri', [MovimentoController::class,'yesterday'])->name('movimenti.ieri');
        Route::get('/movimenti/altroieri', [MovimentoController::class,'beforeyesterday'])->name('movimenti.altroieri');

        // Cestino dei movimenti (ripristino per tutti, export ed eliminazione definitiva solo admin)
        Route::get('/movimenti/export', [MovimentoController::class,'export'])->name('movimenti.export')->middleware('can:admin');
        Route::get('/movimenti/cestino', [MovimentoController::class,'trash'])->name('movimenti.cestino');
        Route::patch('/movimenti/{id}/ripristina', [MovimentoController::class,'restore'])->name('movimenti.restore');
        Route::delete('/movimenti/{id}/elimina-definitivamente', [MovimentoController::class,'forceDestroy'])->name('movimenti.forceDestroy')->middleware('can:admin');

        // Rotte CRUD per i movimenti (show non è implementata)
        Route::resource('movimenti', MovimentoController::class)->except('show');

        // Rotta per la selezione e salvataggio dell'entità
        Route::post('/seleziona-entita', [EntityController::class,'storeSelectedEntity'])->name('salvaEntita');

        // --- Solo admin e superadmin: categorie e dashboard (totali) ---
        Route::middleware('can:admin')->group(function () {
            Route::get('/categoria/{id}/elimina', [CatController::class, 'confirmDelete'])->name('categoria.delete');
            Route::post('/categoria/{id}/sposta-movimenti', [CatController::class, 'moveMovements'])->name('categoria.move');
            Route::resource('categoria', CatController::class)->except('show');

            // Dashboard, con la password aggiuntiva se PASSWORD_PROTECT è impostata
            Route::get('dashboard', [DashboardController::class, 'index'])
                ->name('dashboard')->middleware('password_protect');
            Route::post('dashboard', [DashboardController::class, 'index'])
                ->middleware(['throttle:5,1', 'password_protect']);
        });
    });

    // --- Gestione utenti (admin e superadmin): non dipende dall'entità selezionata ---
    Route::middleware('can:admin')->group(function () {
        // Gestione utenti: l'admin vede quelli delle sue entità, il superadmin tutti
        Route::get('/utenti', [UserController::class, 'index'])->name('utenti.index');
        Route::get('/utenti/nuovo', [UserController::class, 'create'])->name('utenti.create');
        Route::get('/utenti/{user}/modifica', [UserController::class, 'edit'])->name('utenti.edit');
        Route::middleware('not_in_demo')->group(function () {
            Route::post('/utenti', [UserController::class, 'store'])->name('utenti.store');
            Route::patch('/utenti/{user}', [UserController::class, 'update'])->name('utenti.update');
            Route::post('/utenti/{user}/nuova-password', [UserController::class, 'resetPassword'])->name('utenti.password');
            Route::post('/utenti/{user}/stato', [UserController::class, 'toggleActive'])->name('utenti.toggle');
        });
    });

    // --- Solo superadmin: entità ---
    Route::middleware('can:superadmin')->group(function () {
        Route::get('/entita', [EntityController::class, 'index'])->name('entita.index');
        Route::middleware('not_in_demo')->group(function () {
            Route::post('/entita', [EntityController::class, 'store'])->name('entita.store');
            Route::patch('/entita/{entity}', [EntityController::class, 'update'])->name('entita.update');
            Route::delete('/entita/{entity}', [EntityController::class, 'destroy'])->name('entita.destroy');
        });
    });
});
