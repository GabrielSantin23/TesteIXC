<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\PaymentsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get("/", function () {
    if (Auth::check()) {
        return redirect()->route("dashboard");
    }
    return redirect()->route("login");
})->name("home");

Route::middleware(["auth", "verified"])->group(function () {

    Route::get("/dashboard", function () {
        return view("dashboard");
    })->name("dashboard");

    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    Route::get('/pagamento', [PaymentsController::class, 'index'])->name('pagamento.index');
    Route::get('/pagamento/transferir', [PaymentsController::class, 'create'])->name('pagamento.create');
    Route::post('/pagamento/transferir', [PaymentsController::class, 'store'])->name('pagamento.store');
    Route::get('/pagamento/historico', [PaymentsController::class, 'history'])->name('pagamento.history');
    Route::get('/pagamento/transacao/{transaction}', [PaymentsController::class, 'show'])->name('pagamento.show');

    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource("users", UserController::class);
    });
});

require __DIR__."/auth.php";
