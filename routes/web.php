<?php

use Illuminate\Support\Facades\{Auth, Route};
use App\Http\Controllers\Website\{
    HomeController,
    ChatController
};

Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')
    ->prefix('chat')
    ->name('chat.')
    ->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('/send', [ChatController::class, 'send'])->name('send');
    Route::get('/messages', [ChatController::class, 'fetchMessages'])->name('fetchMessages');
    Route::post('/mark-seen', [ChatController::class, 'markAsSeen'])->name('markAsSeen');
    Route::get('/chat/unseen-counts', [ChatController::class, 'fetchUnseenCounts'])->name('unseen-counts');
    Route::patch('/update/{id}', [ChatController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [ChatController::class, 'destroy'])->name('destroy');
});

