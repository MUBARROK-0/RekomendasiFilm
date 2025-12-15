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

        // Support for country filter (TMDB discover parameter)
        if ($country) {
            // Use origin country code for discover API
            $filters['with_origin_country'] = $country;
        }

        if ($search) {
            // When searching by text, use the search endpoint
            $movies = $this->tmdb->searchMovies($search, $page);

            // If a country filter is also selected, further filter search results by checking
            // each movie's production countries (requires extra details requests).
            if ($country && !empty($movies['results'])) {
                $filtered = [];
                foreach ($movies['results'] as $m) {
                    $details = $this->tmdb->getMovieDetails($m['id']);
                    $productionCountries = collect($details['production_countries'] ?? [])->pluck('iso_3166_1')->all();
                    if (in_array($country, $productionCountries)) {
                        $filtered[] = $m;
                    }
                }
                $movies['results'] = $filtered;
                // Recompute total_pages conservatively
                $movies['total_pages'] = 1;
            }
        } else {
            $movies = !empty($filters) 
                ? $this->tmdb->discoverMovies(array_merge($filters, ['page' => $page]))
                : $this->tmdb->getPopularMovies($page);
        }

        $genres = $this->tmdb->getGenres();

        // Expanded country list for filter dropdown (ISO 3166-1 alpha-2)
        $countries = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'ID' => 'Indonesia',
            'CN' => 'China',
            'FR' => 'France',
            'DE' => 'Germany',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'BR' => 'Brazil',
            'IN' => 'India',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'NL' => 'Netherlands',
            'RU' => 'Russia',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'TR' => 'Turkey',
            'PL' => 'Poland',
            'TH' => 'Thailand',
            'PH' => 'Philippines',
            'VN' => 'Vietnam',
        ];
        
        return view('dashboard', [
            'movies' => $movies['results'] ?? [],
            'genres' => $genres['genres'] ?? [],
            'countries' => $countries,
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