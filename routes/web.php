<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\UmbraController;
use App\Http\Controllers\ApiTestController;

Route::get('/', [ApiTestController::class, 'index']);

Route::get('/umbra/files', [UmbraController::class, 'listFiles']);
Route::get('/umbra/folders', [UmbraController::class, 'listFolders']);

// TODO Choose between showFolderContents and listFoldersFromWeb
Route::get('/umbra/folder/{path?}', [UmbraController::class, 'showFolderContents'])
    ->where('path', '.*');
//Route::get('/umbra/folder/{folder}', [UmbraController::class, 'listFolderContents']);

Route::get('/umbra/web-folders', [UmbraController::class, 'listFoldersFromWeb']);

/*Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
