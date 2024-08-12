<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailerController;
use App\Http\Controllers\FranchiseMailerController;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::post('/mailer',[MailerController::class, 'index'])->name('index');
Route::post('/franchise-mailer',[FranchiseMailerController::class, 'index'])->name('franchise');
Route::get('/test',[MailerController::class, 'test'])->name('test');
