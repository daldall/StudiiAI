<?php

use App\Http\Controllers\AcademicToolkitController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AcademicToolkitController::class, 'index'])->name('dashboard');

Route::post('/ai/text-tools', [AcademicToolkitController::class, 'textTools'])->name('ai.text-tools');
Route::post('/ai/flashcards', [AcademicToolkitController::class, 'flashcards'])->name('ai.flashcards');
Route::post('/ai/citation', [AcademicToolkitController::class, 'citation'])->name('ai.citation');
Route::post('/ai/study-plan', [AcademicToolkitController::class, 'studyPlan'])->name('ai.study-plan');
Route::post('/ai/chat', [AcademicToolkitController::class, 'chat'])->name('ai.chat');

