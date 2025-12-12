<?php

namespace App\Services;

use GuzzleHttp\Client;

class DeepSeekService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.deepseek.api_key');
        $this->baseUrl = config('services.deepseek.base_url');
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
            $response = $this->client->post(rtrim($this->baseUrl, '/') . '/analyze', [
                'json' => ['text' => $text],
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => 5,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

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

            return ['mood' => $mood ? strtolower($mood) : null, 'genres' => $genres, 'keywords' => $keywords];
        } catch (\Throwable $e) {
            return $this->localAnalyze($text);
        }
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
