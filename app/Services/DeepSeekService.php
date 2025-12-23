<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;
    protected $debug;
    // Last error message from the last DeepSeek call (if any)
    protected $lastError;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.deepseek.api_key');
        $this->baseUrl = config('services.deepseek.base_url');
        $this->debug = config('services.deepseek.debug', false);
    }

    /**
     * Analyze free-text and return structured data.
     * Expected return shape: ['mood' => string|null, 'genres' => array<int>]
     */
    public function analyzeText(string $text): array
    {
        // If DeepSeek configuration is not provided, use a local heuristic analyzer.
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return $this->localAnalyze($text);
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/analyze';
            $payload = [
                'json' => ['text' => $text],
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 5,
            ];

            if ($this->debug) {
                Log::debug('DeepSeek analyze request', ['url' => $url, 'text' => mb_substr($text, 0, 1000)]);
            }

            $response = $this->client->post($url, $payload);
            $body = $response->getBody()->getContents();

            if ($this->debug) {
                Log::debug('DeepSeek analyze response', ['status' => $response->getStatusCode(), 'body' => mb_substr($body, 0, 2000)]);
            }

            $data = json_decode($body, true);

            // Normalize response: support multiple possible shapes from DeepSeek.
            $mood = $data['mood'] ?? $data['intent'] ?? null;
            $genres = $data['genres'] ?? $data['genre_ids'] ?? [];
            $keywords = $data['keywords'] ?? $data['tokens'] ?? [];

            if (!is_array($genres)) {
                $genres = [];
            }

            if (!is_array($keywords)) {
                $keywords = [];
            }

            // If DeepSeek returned very little, fallback to local heuristic
            if (empty($mood) && empty($genres) && empty($keywords)) {
                return $this->localAnalyze($text);
            }

            return ['mood' => $mood ? strtolower($mood) : null, 'genres' => $genres, 'keywords' => $keywords, 'raw' => $data];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('DeepSeek analyze failed', ['message' => $this->lastError, 'text' => mb_substr($text, 0, 1000)]);
            // fallback to local analyzer
            $fallback = $this->localAnalyze($text);
            if ($this->debug) {
                Log::info('DeepSeek analyze fallback to localAnalyze', ['fallback' => $fallback]);
            }
            return $fallback;
        }
    }

    /**
     * Ask DeepSeek to recommend a movie directly based on free-text input.
     * Returns a normalized movie array or null.
     */
    public function recommendByText(string $text): ?array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return $this->localRecommendByText($text);
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/recommend';
            $payload = [
                'json' => ['text' => $text],
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 6,
            ];

            if ($this->debug) {
                Log::debug('DeepSeek recommendByText request', ['url' => $url, 'text' => mb_substr($text, 0, 1000)]);
            }

            $response = $this->client->post($url, $payload);
            $body = $response->getBody()->getContents();

            if ($this->debug) {
                Log::debug('DeepSeek recommendByText response', ['status' => $response->getStatusCode(), 'body' => mb_substr($body, 0, 2000)]);
            }

            $data = json_decode($body, true);

            // Normalize: accept either { movie: {...} } or { results: [{...}] } or top-level object
            $movie = $data['movie'] ?? ($data['results'][0] ?? $data[0] ?? $data);

            if (empty($movie) || !is_array($movie)) return null;

            return $this->normalizeMovieShape($movie);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('DeepSeek recommendByText failed', ['message' => $this->lastError, 'text' => mb_substr($text, 0, 1000)]);
            if ($this->debug) {
                Log::info('DeepSeek recommendByText fallback to localRecommendByText');
            }
            return $this->localRecommendByText($text);
        }
    }

    /**
     * Ask DeepSeek to recommend a movie for a given mood/intent.
     */
    public function recommendByMood(string $mood): ?array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return $this->localRecommendByMood($mood);
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/recommend';
            $payload = [
                'json' => ['mood' => $mood],
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 6,
            ];

            if ($this->debug) {
                Log::debug('DeepSeek recommendByMood request', ['url' => $url, 'mood' => $mood]);
            }

            $response = $this->client->post($url, $payload);
            $body = $response->getBody()->getContents();

            if ($this->debug) {
                Log::debug('DeepSeek recommendByMood response', ['status' => $response->getStatusCode(), 'body' => mb_substr($body, 0, 2000)]);
            }

            $data = json_decode($body, true);

            $movie = $data['movie'] ?? ($data['results'][0] ?? $data[0] ?? $data);
            if (empty($movie) || !is_array($movie)) return null;

            return $this->normalizeMovieShape($movie);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('DeepSeek recommendByMood failed', ['message' => $this->lastError, 'mood' => $mood]);
            if ($this->debug) {
                Log::info('DeepSeek recommendByMood fallback to localRecommendByMood', ['mood' => $mood]);
            }
            return $this->localRecommendByMood($mood);
        }
    }

    private function normalizeMovieShape(array $movie): array
    {
        // Try common keys from various API responses
        $title = $movie['title'] ?? $movie['name'] ?? $movie['movie_title'] ?? null;
        $overview = $movie['overview'] ?? $movie['description'] ?? $movie['summary'] ?? null;
        $poster = $movie['poster_url'] ?? $movie['poster'] ?? $movie['image'] ?? $movie['poster_path'] ?? null;
        $year = $movie['year'] ?? ($movie['release_year'] ?? null);
        $rating = $movie['rating'] ?? $movie['vote_average'] ?? null;
        $detailUrl = $movie['url'] ?? $movie['detail_url'] ?? null;

        return array_filter([
            'title' => $title,
            'overview' => $overview,
            'poster_url' => $poster,
            'release_year' => $year,
            'rating' => $rating,
            'detail_url' => $detailUrl,
        ]);
    }

    private function localRecommendByText(string $text): ?array
    {
        // Fallback: simple heuristic using analyzeText to pick a local curated movie
        $analysis = $this->localAnalyze($text);
        $mood = $analysis['mood'] ?? null;
        return $this->localRecommendByMood($mood ?: 'happy');
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?? null;
    }

    private function localRecommendByMood(string $mood): ?array
    {
        $library = [
            'happy' => [
                ['title' => 'Amélie', 'overview' => 'Seperti kisah seorang pelayan cafe yang ingin menyebarkan kebaikan.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Amelie', 'release_year' => 2001, 'rating' => 8.3],
                ['title' => 'The Grand Budapest Hotel', 'overview' => 'Komedi petualangan penuh warna dari Wes Anderson.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Grand+Budapest', 'release_year' => 2014, 'rating' => 8.1],
            ],
            'sad' => [
                ['title' => 'Requiem for a Dream', 'overview' => 'Drama intens tentang kecanduan dan kehancuran.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Requiem', 'release_year' => 2000, 'rating' => 8.3],
            ],
            'romantic' => [
                ['title' => 'Pride & Prejudice', 'overview' => 'Kisah cinta klasik berdasarkan novel Jane Austen.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Pride+%26+Prejudice', 'release_year' => 2005, 'rating' => 7.8],
            ],
            'adventure' => [
                ['title' => 'Mad Max: Fury Road', 'overview' => 'Aksi post-apocalyptic yang intens.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Mad+Max', 'release_year' => 2015, 'rating' => 8.1],
            ],
            'fear' => [
                ['title' => 'The Witch', 'overview' => 'Horor atmosferik pada masa awal kolonial.', 'poster_url' => 'https://via.placeholder.com/300x450?text=The+Witch', 'release_year' => 2015, 'rating' => 6.8],
            ],
            'mystery' => [
                ['title' => 'Gone Girl', 'overview' => 'Mystery-thriller tentang pernikahan dan kebohongan.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Gone+Girl', 'release_year' => 2014, 'rating' => 8.0],
            ],
        ];

        $list = $library[$mood] ?? $library['happy'];
        return $list[0] ?? null;
    }

    private function localAnalyze(string $text): array
    {
        $lower = mb_strtolower($text);
        $genres = [];
        $mood = null;
        $keywords = [];

        // Keyword-based genre mapping
        $map = [
            'pembunuh' => [80, 53],
            'pembunuhan' => [80, 53],
            'bunuh' => [80, 53],
            'membunuh' => [80, 53],
            'misteri' => [9648, 80],
            'horor' => [27, 53],
            'seram' => [27, 53],
            'cinta' => [10749, 18],
            'romantis' => [10749, 18],
            'komedi' => [35],
            'aksi' => [28],
            'petualangan' => [12, 14],
            'anak' => [],
            'anak-anak' => [],
            'anak' => [],
        ];

        foreach ($map as $kw => $gids) {
            if (mb_strpos($lower, $kw) !== false) {
                $genres = array_merge($genres, $gids);
                $keywords[] = $kw;
            }
        }

        $genres = array_values(array_unique(array_filter($genres)));

        // Rough mood inference
        if (preg_match('/(senang|bahagia|gembira|lucu)/u', $lower)) {
            $mood = 'happy';
        } elseif (preg_match('/(sedih|galau|duka)/u', $lower)) {
            $mood = 'sad';
        } elseif (preg_match('/(menakutkan|horor|seram)/u', $lower)) {
            $mood = 'fear';
        } elseif (preg_match('/(cinta|romantis)/u', $lower)) {
            $mood = 'romantic';
        } elseif (preg_match('/(petualang|petualangan|aksi)/u', $lower)) {
            $mood = 'adventure';
        }

        return ['mood' => $mood, 'genres' => $genres, 'keywords' => $keywords];
    }
}
