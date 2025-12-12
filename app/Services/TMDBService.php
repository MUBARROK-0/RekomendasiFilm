<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class TMDBService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;
    protected $imageUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.tmdb.api_key');
        $this->baseUrl = config('services.tmdb.base_url');
        $this->imageUrl = config('services.tmdb.image_url');
    }

    public function getPopularMovies($page = 1)
    {
        return Cache::remember("tmdb_popular_{$page}", 3600, function () use ($page) {
            $response = $this->client->get("{$this->baseUrl}/movie/popular", [
                'query' => [
                    'api_key' => $this->apiKey,
                    'page' => $page,
                    'language' => 'en-US'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        });
    }

    public function searchMovies($query, $page = 1)
    {
        return Cache::remember("tmdb_search_{$query}_{$page}", 1800, function () use ($query, $page) {
            $response = $this->client->get("{$this->baseUrl}/search/movie", [
                'query' => [
                    'api_key' => $this->apiKey,
                    'query' => $query,
                    'page' => $page,
                    'language' => 'en-US'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        });
    }

    public function getMovieDetails($id)
    {
        return Cache::remember("tmdb_movie_{$id}", 7200, function () use ($id) {
            $response = $this->client->get("{$this->baseUrl}/movie/{$id}", [
                'query' => [
                    'api_key' => $this->apiKey,
                    'append_to_response' => 'credits,videos',
                    'language' => 'en-US'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        });
    }

    public function getGenres()
    {
        return Cache::remember('tmdb_genres', 86400, function () {
            $response = $this->client->get("{$this->baseUrl}/genre/movie/list", [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'en-US'
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        });
    }

    public function discoverMovies($filters = [])
    {
        $query = array_merge([
            'api_key' => $this->apiKey,
            'language' => 'en-US',
            'sort_by' => 'popularity.desc'
        ], $filters);

        $cacheKey = 'tmdb_discover_' . md5(serialize($query));
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            $response = $this->client->get("{$this->baseUrl}/discover/movie", [
                'query' => $query
            ]);
            
            return json_decode($response->getBody(), true);
        });
    }

    public function getImageUrl($path, $size = 'w500')
    {
        return $path ? "{$this->imageUrl}/{$size}{$path}" : asset('images/default-poster.jpg');
    }

    public function mapMoodToGenre($mood)
    {
        $moodMap = [
            'happy' => [35, 10751], // Comedy, Family
            'sad' => [18, 10749], // Drama, Romance
            'excited' => [28, 12, 878], // Action, Adventure, Sci-Fi
            'chill' => [10402, 36], // Music, History
            'fear' => [27, 53], // Horror, Thriller
            'adventure' => [12, 14], // Adventure, Fantasy
            'romantic' => [10749, 18], // Romance, Drama
            'mystery' => [9648, 80], // Mystery, Crime
        ];

        return $moodMap[strtolower($mood)] ?? [28]; // Default to Action
    }
}