<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::view('/login', 'welcome')->name('login');

/** Service worker thực tế build ở `public/build/sw.js` nhưng đăng ký tại `/sw.js` để scope `/` hợp lệ (file trong `/build/` chỉ được max-scope `/build/`). */
Route::get('/sw.js', function () {
    $path = public_path('build/sw.js');
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
        'Cache-Control' => 'public, max-age=0, must-revalidate',
    ]);
});

Route::view('/{any?}', 'welcome')->where('any', '.*');
