<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use Illuminate\Http\Request;

class MoodAIController extends Controller
{
    protected $tmdb;

    public function __construct(TMDBService $tmdb)
    {
        $this->tmdb = $tmdb;
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
            'moods' => $moods,
            'tmdb' => $this->tmdb  // TAMBAHKAN INI
        ]);
    }

    public function recommend(Request $request)
    {
        $request->validate([
            'mood' => 'required|string'
        ]);

        $mood = $request->input('mood');
        $genres = $this->tmdb->mapMoodToGenre($mood);
        
        $movies = $this->tmdb->discoverMovies([
            'with_genres' => implode('|', $genres),
            'sort_by' => 'vote_average.desc',
            'vote_count.gte' => 100
        ]);

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
            'movies' => $movies['results'] ?? [],
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
            'tmdb' => $this->tmdb  // TAMBAHKAN INI
        ]);
    }
}