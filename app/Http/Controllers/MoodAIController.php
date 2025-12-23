<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class MoodAIController extends Controller
{
    protected $deepseek;

    public function __construct(DeepSeekService $deepseek)
    {
        $this->deepseek = $deepseek;
    }

    public function index()
    {
        $moods = [
            'happy' => ['name' => 'Happy 😊', 'color' => 'success'],
            'sad' => ['name' => 'Sad 😢', 'color' => 'info'],
            'excited' => ['name' => 'Excited 🤩', 'color' => 'warning'],
            'chill' => ['name' => 'Chill 😌', 'color' => 'primary'],
            'fear' => ['name' => 'Fear 😨', 'color' => 'dark'],
            'adventure' => ['name' => 'Adventure 🗺️', 'color' => 'success'],
            'romantic' => ['name' => 'Romantic 💖', 'color' => 'danger'],
            'mystery' => ['name' => 'Mystery 🕵️', 'color' => 'secondary'],
        ];

        return view('mood-ai', [
            'moods' => $moods
        ]);
    }

    public function recommend(Request $request)
    {
        $request->validate([
            'mood' => 'required|string'
        ]);

        $mood = $request->input('mood');

        // Use DeepSeek to recommend a movie for the given mood (no TMDB involved)
        $movie = $this->deepseek->recommendByMood($mood);

        $moodNames = [
            'happy' => 'Happy',
            'sad' => 'Sad',
            'excited' => 'Excited',
            'chill' => 'Chill',
            'fear' => 'Fear',
            'adventure' => 'Adventure',
            'romantic' => 'Romantic',
            'mystery' => 'Mystery',
        ];

        return view('mood-ai', [
            'movies' => $movie ? [$movie] : [],
            'selectedMood' => $mood,
            'moodName' => $moodNames[$mood] ?? ucfirst($mood),
            'moods' => [
                'happy' => ['name' => 'Happy 😊', 'color' => 'success'],
                'sad' => ['name' => 'Sad 😢', 'color' => 'info'],
                'excited' => ['name' => 'Excited 🤩', 'color' => 'warning'],
                'chill' => ['name' => 'Chill 😌', 'color' => 'primary'],
                'fear' => ['name' => 'Fear 😨', 'color' => 'dark'],
                'adventure' => ['name' => 'Adventure 🗺️', 'color' => 'success'],
                'romantic' => ['name' => 'Romantic 💖', 'color' => 'danger'],
                'mystery' => ['name' => 'Mystery 🕵️', 'color' => 'secondary'],
            ],
            // no TMDB here by design
        ]);
    }

    /**
     * Analyze free-text user input and return a single recommended movie (JSON).
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:3'
        ]);

        $text = $request->input('text');

        // Ask DeepSeek to analyze and recommend directly (no TMDB involved)
        $analysis = $this->deepseek->analyzeText($text);
        $movie = $this->deepseek->recommendByText($text);

        // surface DeepSeek errors (if any) to the frontend for easier debugging
        $analysis['deepseek_error'] = $this->deepseek->getLastError();

        // Build explanation text for user based on DeepSeek analysis
        $matchedTokens = $analysis['keywords'] ?? [];
        $analysis['matched_tokens'] = array_values(array_unique(array_filter($matchedTokens)));

        $explanationParts = [];
        $explanationParts[] = "Saya menganalisis permintaan Anda: \"{$text}\".";

        if (!empty($analysis['mood'])) {
            $explanationParts[] = "Terdeteksi mood/intent: " . $analysis['mood'] . ".";
        }

        if (!empty($analysis['keywords'])) {
            $explanationParts[] = "Kata kunci yang relevan: " . implode(', ', array_slice($analysis['keywords'], 0, 6)) . ".";
        }

        if (!empty($analysis['matched_tokens'])) {
            $explanationParts[] = "Kata yang cocok: " . implode(', ', $analysis['matched_tokens']) . ".";
        }

        if ($movie) {
            $title = $movie['title'] ?? 'Film';
            $rating = isset($movie['rating']) ? number_format($movie['rating'], 1) : 'N/A';
            $year = $movie['release_year'] ?? 'N/A';
            $explanationParts[] = "Berdasarkan analisis tersebut, saya merekomendasikan satu film: \"{$title}\" ({$year}), rating {$rating}.";
            if (!empty($movie['overview'])) {
                $overview = strlen($movie['overview']) > 300 ? substr($movie['overview'], 0, 300) . '...' : $movie['overview'];
                $explanationParts[] = "Sinopsis singkat: {$overview}";
            }
            $explanationParts[] = "Alasan: berdasarkan rekomendasi DeepSeek.";
        } else {
            $explanationParts[] = "Maaf, saya tidak menemukan rekomendasi yang pas dari DeepSeek.";
        }

        $analysis['explanation'] = implode(' ', $explanationParts);

        return response()->json(['movie' => $movie, 'analysis' => $analysis]);
    }
}