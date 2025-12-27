<?php

namespace App\Http\Controllers;

use App\Services\GroqService;
use Illuminate\Http\Request;

class GroqAIController extends Controller
{
    protected $groq;
    protected $tmdb;

    public function __construct(GroqService $groq, \App\Services\TMDBService $tmdb)
    {
        $this->groq = $groq;
        $this->tmdb = $tmdb;
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

        // Attempt to find a local TMDB match by title so we can link to the internal detail page
        if (!empty($movie['title'])) {
            try {
                $search = $this->tmdb->searchMovies($movie['title'], 1);
                $matchedId = null;
                if (!empty($search['results'])) {
                    // Scoring-based matching to improve accuracy
                    $best = null;
                    $bestScore = 0;
                    $titleLower = mb_strtolower($movie['title']);
                    $movieYear = isset($movie['release_year']) ? (string)$movie['release_year'] : null;
                    $moviePoster = $movie['poster_url'] ?? null;

                    foreach ($search['results'] as $r) {
                        $score = 0;
                        $rTitle = mb_strtolower($r['title'] ?? '');
                        $rOrig = mb_strtolower($r['original_title'] ?? '');

                        // exact title match
                        if ($rTitle === $titleLower || $rOrig === $titleLower) {
                            $score += 100;
                        }

                        // year match
                        if ($movieYear && !empty($r['release_date']) && mb_substr($r['release_date'], 0, 4) === $movieYear) {
                            $score += 50;
                        }

                        // poster filename match if available
                        if ($moviePoster && !empty($r['poster_path'])) {
                            $posterBasenameMovie = basename(parse_url($moviePoster, PHP_URL_PATH));
                            $posterBasenameR = basename($r['poster_path']);
                            if ($posterBasenameMovie && $posterBasenameR && $posterBasenameMovie === $posterBasenameR) {
                                $score += 30;
                            }
                        }

                        // title similarity (Levenshtein-based)
                        $len = max(mb_strlen($rTitle), mb_strlen($titleLower), 1);
                        $lev = levenshtein($rTitle, $titleLower);
                        $similarity = max(0, 100 - intval(($lev / $len) * 100));
                        if ($similarity > 60) {
                            $score += $similarity; // reward reasonably similar titles
                        }

                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $best = $r;
                        }
                    }

                    // Only accept matches above a conservative threshold
                    if ($bestScore >= 80 && $best) {
                        $matchedId = $best['id'];
                    }
                }

                if ($matchedId) {
                    $movie['detail_local_url'] = route('film.detail', $matchedId);

                    // Fetch TMDB details to obtain a proper poster URL when AI didn't provide one
                    try {
                        $details = $this->tmdb->getMovieDetails($matchedId);
                        if (!empty($details['poster_path'])) {
                            $movie['poster_url'] = $this->tmdb->getImageUrl($details['poster_path']);
                        }
                        $movie['match_confidence'] = $bestScore ?? null;
                    } catch (\Throwable $e) {
                        // ignore detail fetch errors
                    }
                }

                // If we couldn't find a confident match but the AI returned no poster,
                // try to use the first search result's poster (conservative fallback)
                if (!$matchedId && !empty($search['results']) && (empty($movie['poster_url']) || strpos($movie['poster_url'], 'placeholder.com') !== false)) {
                    $first = $search['results'][0];
                    if (!empty($first['poster_path'])) {
                        try {
                            $movie['poster_url'] = $this->tmdb->getImageUrl($first['poster_path']);
                            $movie['detail_local_url'] = route('film.detail', $first['id']);
                            $movie['match_confidence'] = 50; // indicate fallback
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore lookup failures, don't block recommendation
            }
        }

        return response()->json(['movie' => $movie, 'error' => $error]);
    }
}
