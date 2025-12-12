<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;

class MoodAIController extends Controller
{
    protected $tmdb;
    protected $deepseek;

    public function __construct(TMDBService $tmdb, DeepSeekService $deepseek)
    {
        $this->tmdb = $tmdb;
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

    /**
     * Analyze free-text user input and return a single recommended movie (JSON).
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:3'
        ]);

        $text = $request->input('text');

        $analysis = $this->deepseek->analyzeText($text);

        // Collect a diverse candidate pool: search(text), search(keywords), discover(genres)
        $candidatesById = [];
        $tokens = array_filter(array_map('trim', preg_split('/\s+/', mb_strtolower($text))));
        $matchedTokens = [];

        // direct search (up to 20)
        $search = $this->tmdb->searchMovies($text, 1);
        foreach (array_slice($search['results'] ?? [], 0, 20) as $r) {
            if (!empty($r['id'])) $candidatesById[$r['id']] = $r;
        }

        // keyword-based search
        $keywords = $analysis['keywords'] ?? [];
        if (!empty($keywords)) {
            foreach (array_slice($keywords, 0, 6) as $kw) {
                $s = $this->tmdb->searchMovies($kw, 1);
                foreach (array_slice($s['results'] ?? [], 0, 10) as $r) {
                    if (!empty($r['id'])) $candidatesById[$r['id']] = $r;
                }
            }
        }

        // discover by genres
        $genres = $analysis['genres'] ?? [];
        if (empty($genres) && !empty($analysis['mood'])) {
            $genres = $this->tmdb->mapMoodToGenre($analysis['mood']);
        }
        if (!empty($genres)) {
            $discover = $this->tmdb->discoverMovies([
                'with_genres' => is_array($genres) ? implode('|', $genres) : $genres,
                'sort_by' => 'vote_count.desc',
                'vote_count.gte' => 10,
                'page' => 1
            ]);
            foreach (array_slice($discover['results'] ?? [], 0, 40) as $r) {
                if (!empty($r['id'])) $candidatesById[$r['id']] = $r;
            }
        }

        // Score candidates using token overlap, keyword presence, and popularity
        $candidates = array_values($candidatesById);
        $best = null;
        $bestScore = -INF;

        foreach ($candidates as $cand) {
            if (!empty($cand['adult'])) continue;
            $hay = mb_strtolower(($cand['title'] ?? '') . ' ' . ($cand['overview'] ?? ''));

            $tokenMatch = 0;
            $localMatched = [];
            foreach ($tokens as $t) {
                if ($t === '') continue;
                if (mb_strpos($hay, $t) !== false) {
                    $tokenMatch += 3;
                    $localMatched[] = $t;
                }
                if (mb_strpos($hay, $t) === 0) $tokenMatch += 2;
            }

            $keywordBoost = 0;
            foreach ($keywords as $k) {
                if ($k && mb_strpos($hay, mb_strtolower($k)) !== false) $keywordBoost += 5;
            }

            $voteAverage = $cand['vote_average'] ?? 0;
            $voteCount = $cand['vote_count'] ?? 0;
            $voteScore = ($voteAverage / 10) * 3 + log10($voteCount + 1);

            $score = $tokenMatch * 1.2 + $keywordBoost + $voteScore;
            $score += rand(0, 100) / 10000; // tiny tie-breaker

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $cand;
                $matchedTokens = array_merge($matchedTokens, $localMatched);
            }
        }

        $movie = $best;

        // final fallback: popular non-adult
        if (!$movie) {
            $popular = $this->tmdb->getPopularMovies(1);
            foreach ($popular['results'] ?? [] as $p) {
                if (empty($p['adult'])) {
                    $movie = $p;
                    break;
                }
            }
            $movie = $movie ?? ($popular['results'][0] ?? null);
        }

        // attach matched tokens info for explanation
        $matchedTokens = array_values(array_unique(array_filter($matchedTokens)));
        $analysis['matched_tokens'] = $matchedTokens;

        // Build explanation text for user
        $explanationParts = [];
        $explanationParts[] = "Saya menganalisis permintaan Anda: \"{$text}\".";

        if (!empty($analysis['mood'])) {
            $explanationParts[] = "Terdeteksi mood/intent: " . $analysis['mood'] . ".";
        }

        if (!empty($analysis['keywords'])) {
            $explanationParts[] = "Kata kunci yang relevan: " . implode(', ', array_slice($analysis['keywords'], 0, 6)) . ".";
        }

        if (!empty($matchedTokens)) {
            $explanationParts[] = "Saya mencocokkan kata-kata berikut dengan sinopsis/judul film: " . implode(', ', $matchedTokens) . ".";
        }

        if ($movie) {
            $title = $movie['title'] ?? ($movie['name'] ?? 'Film');
            $rating = isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 'N/A';
            $year = !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : '';
            $explanationParts[] = "Berdasarkan analisis tersebut, saya merekomendasikan satu film: \"{$title}\" ({$year}), rating {$rating}.";
            if (!empty($movie['overview'])) {
                $overview = strlen($movie['overview']) > 300 ? substr($movie['overview'], 0, 300) . '...' : $movie['overview'];
                $explanationParts[] = "Sinopsis singkat: {$overview}";
            }
            $explanationParts[] = "Alasan: kecocokan kata kunci dan peringkat yang baik.";
        } else {
            $explanationParts[] = "Maaf, saya tidak menemukan rekomendasi yang pas. Saya menampilkan film populer sebagai cadangan.";
        }

        $analysis['explanation'] = implode(' ', $explanationParts);

        return response()->json(['movie' => $movie, 'analysis' => $analysis]);
    }
}