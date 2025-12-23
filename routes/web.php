<?php

use App\Http\Controllers\FilmController;
use App\Http\Controllers\MoodAIController;
use App\Http\Controllers\GroqAIController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FilmController::class, 'dashboard'])->name('dashboard');
Route::get('/film/{id}', [FilmController::class, 'show'])->name('film.detail');
Route::get('/mood-ai', [MoodAIController::class, 'index'])->name('mood.ai');
Route::post('/mood-ai/recommend', [MoodAIController::class, 'recommend'])->name('mood.recommend');
Route::post('/mood-ai/analyze', [MoodAIController::class, 'analyze'])->name('mood.analyze');

// Groq AI routes
Route::get('/groq-ai', [GroqAIController::class, 'index'])->name('groq.ai');
Route::post('/groq-ai/analyze', [GroqAIController::class, 'analyze'])->name('groq.analyze');