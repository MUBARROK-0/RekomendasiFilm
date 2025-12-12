<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    protected $tmdb;

    public function __construct(TMDBService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    public function dashboard(Request $request)
    {
        $search = $request->input('search');
        $genre = $request->input('genre');
        $year = $request->input('year');
        $rating = $request->input('rating');
        $country = $request->input('country');
        $page = $request->input('page', 1);

        $filters = [];
        
        if ($genre) {
            $filters['with_genres'] = $genre;
        }
        
        if ($year) {
            $filters['primary_release_year'] = $year;
        }
        
        if ($rating) {
            $filters['vote_average.gte'] = $rating;
        }

        if ($search) {
            $movies = $this->tmdb->searchMovies($search, $page);
        } else {
            $movies = !empty($filters) 
                ? $this->tmdb->discoverMovies(array_merge($filters, ['page' => $page]))
                : $this->tmdb->getPopularMovies($page);
        }

        $genres = $this->tmdb->getGenres();
        
        return view('dashboard', [
            'movies' => $movies['results'] ?? [],
            'genres' => $genres['genres'] ?? [],
            'currentPage' => $page,
            'totalPages' => $movies['total_pages'] ?? 1,
            'search' => $search,
            'filters' => [
                'genre' => $genre,
                'year' => $year,
                'rating' => $rating,
                'country' => $country,
            ],
            'tmdb' => $this->tmdb  // TAMBAHKAN INI
        ]);
    }

    public function show($id)
    {
        $movie = $this->tmdb->getMovieDetails($id);
        
        if (!$movie || isset($movie['success']) && !$movie['success']) {
            abort(404, 'Film not found');
        }

        return view('film-detail', [
            'movie' => $movie,
            'tmdb' => $this->tmdb
        ]);
    }
}