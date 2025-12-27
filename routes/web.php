<?php

use App\Http\Controllers\FilmController;
// Mood AI removed — controller kept but routes redirected/removed
use App\Http\Controllers\GroqAIController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FilmController::class, 'dashboard'])->name('dashboard');
Route::get('/film/{id}', [FilmController::class, 'show'])->name('film.detail');
// /mood-ai page removed — redirect users to Groq AI
Route::redirect('/mood-ai', '/groq-ai', 302);

// Groq AI routes
Route::get('/groq-ai', [GroqAIController::class, 'index'])->name('groq.ai');
Route::post('/groq-ai/analyze', [GroqAIController::class, 'analyze'])->name('groq.analyze');