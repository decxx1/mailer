<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailerController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('/mailer',[MailerController::class, 'index'])->name('index');
