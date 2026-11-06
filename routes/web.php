<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{MovimentoController, CatController, EntityController, DashboardController};
use App\Http\Middleware\InitializeSelectedEntity; // Assicurati che questo sia incluso
use Illuminate\Support\Facades\Redirect; // Necessario per Redirect::to

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
Route::post('manual-login', [ProfileController::class, 'manualLogin'])
    ->name('manual.login.post')->middleware('guest');

// Includi le rotte di autenticazione standard di Laravel (login, register, reset password, etc.)
require __DIR__.'/auth.php';


// --- Gruppo di Rotte Autenticate e Verificate ---
// Middleware 'auth' per utenti loggati, 'verified' per utenti con email verificata
Route::middleware(['auth', 'verified'])->group(function () {

    // Rotte del profilo utente
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/entita-predefinita', [ProfileController::class, 'updateDefaultEntity'])->name('profile.default-entity');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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

        // Export CSV e cestino dei movimenti
        Route::get('/movimenti/export', [MovimentoController::class,'export'])->name('movimenti.export');
        Route::get('/movimenti/cestino', [MovimentoController::class,'trash'])->name('movimenti.cestino');
        Route::patch('/movimenti/{id}/ripristina', [MovimentoController::class,'restore'])->name('movimenti.restore');
        Route::delete('/movimenti/{id}/elimina-definitivamente', [MovimentoController::class,'forceDestroy'])->name('movimenti.forceDestroy');

        // Rotte CRUD per i movimenti (show non è implementata)
        Route::resource('movimenti', MovimentoController::class)->except('show');

        // Rotta per la selezione e salvataggio dell'entità
        Route::post('/seleziona-entita', [EntityController::class,'storeSelectedEntity'])->name('salvaEntita');

        // Rotte CRUD per le categorie (show non è implementata)
        Route::resource('categoria', CatController::class)->except('show');

        // Rotte della Dashboard (protette da middleware 'password_protect')
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard')->middleware('password_protect');
        Route::post('dashboard', [DashboardController::class, 'index'])
            ->middleware(['throttle:5,1', 'password_protect']);
    });
});


// --- Rotte Amministratore ---
Route::prefix('admin')->name('admin.')->group(function() {
    // Reindirizza la root /admin alla pagina di login dell'amministratore
    Route::get('/', function () { return Redirect::to(route('admin.login')); });

    // Rotte di login e logout per l'amministratore (guest:admin middleware se disponibile)
    Route::get('/login', [\App\Http\Controllers\Admin\LoginController::class, 'loginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/logout', [\App\Http\Controllers\Admin\LoginController::class, 'logout'])->name('logout');

    // Rotte specifiche dell'area admin (richiede autenticazione come 'admin')
    Route::middleware('auth:admin')->group(function() {
        Route::get('/dashboard', function () {
            return view('admin-dashboard');
        })->name('dashboard');
    });
});
