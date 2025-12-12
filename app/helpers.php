<?php

use App\Services\TMDBService;

if (!function_exists('tmdb')) {
    function tmdb()
    {
        return app(TMDBService::class);
    }
}

if (!function_exists('tmdb_image_url')) {
    function tmdb_image_url($path, $size = 'w500')
    {
        return app(TMDBService::class)->getImageUrl($path, $size);
    }
}