<?php

namespace App\Http\Controllers;

use App\Services\GroqService;
use Illuminate\Http\Request;

class GroqAIController extends Controller
{
    protected $groq;

    public function __construct(GroqService $groq)
    {
        $this->groq = $groq;
    }

    public function index()
    {
        $moods = [
            'happy' => ['name' => 'Happy 😊', 'color' => 'success'],
            'sad' => ['name' => 'Sad 😢', 'color' => 'info'],
            'romantic' => ['name' => 'Romantic 💖', 'color' => 'danger'],
            'adventure' => ['name' => 'Adventure 🗺️', 'color' => 'success'],
            'fear' => ['name' => 'Fear 😨', 'color' => 'dark'],
        ];

        return view('groq-ai', [
            'moods' => $moods,
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate(['text' => 'required|string|min:3']);
        $text = $request->input('text');

        $movie = $this->groq->recommendFromMood($text);
        $error = $this->groq->getLastError();

        if (!$movie) {
            return response()->json(['movie' => null, 'error' => $error]);
        }

        return response()->json(['movie' => $movie, 'error' => $error]);
    }
}
