@extends('layouts.app')

@section('content')
<!-- Search and Filter -->
<div class="mb-4">
    <form action="{{ route('dashboard') }}" method="GET" id="filterForm">
        <!-- Search placed at top -->
        <div class="mb-3 d-flex justify-content-start">
            <div class="search-top">
                <input type="text" 
                       class="form-control search-box" 
                       placeholder="Search" 
                       name="search" 
                       value="{{ $search ?? '' }}">
            </div>
        </div>

        <!-- All Films header with short filters on same row -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="fw-bold mb-0 all-films-title">All Films</h1>
            </div>

            <div class="d-flex gap-2 align-items-center filters-row">
                <select class="form-select filter-select short" name="year" onchange="this.form.submit()">
                    <option value="">YEAR</option>
                    @for($y = date('Y'); $y >= 2000; $y--)
                        <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <select class="form-select filter-select short" name="country" onchange="this.form.submit()">
                    <option value="">COUNTRY</option>
                    @foreach($countries as $code => $name)
                        <option value="{{ $code }}" {{ ($filters['country'] ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <select class="form-select filter-select short" name="rating" onchange="this.form.submit()">
                    <option value="">RATING</option>
                    <option value="7" {{ ($filters['rating'] ?? '') == '7' ? 'selected' : '' }}>7+ Stars</option>
                    <option value="8" {{ ($filters['rating'] ?? '') == '8' ? 'selected' : '' }}>8+ Stars</option>
                    <option value="9" {{ ($filters['rating'] ?? '') == '9' ? 'selected' : '' }}>9+ Stars</option>
                </select>

                <select class="form-select filter-select short" name="genre" onchange="this.form.submit()">
                    <option value="">GENRES</option>
                    @foreach($genres as $genre)
                        <option value="{{ $genre['id'] }}" {{ ($filters['genre'] ?? '') == $genre['id'] ? 'selected' : '' }}>
                            {{ $genre['name'] }}
                        </option>
                    @endforeach
                </select>


            </div>
        </div>
    </form>
</div>

<!-- Movies Grid -->
<div class="row g-4">
    @forelse($movies as $movie)
        <div class="col-6 col-sm-6 col-md-4 col-lg-3">
            <div class="movie-card position-relative">
                <a href="{{ route('film.detail', $movie['id']) }}" class="text-decoration-none">
                    <div class="position-relative overflow-hidden rounded-lg movie-poster-wrapper">
                        <img src="{{ $tmdb->getImageUrl($movie['poster_path'], 'w500') }}" 
                             class="w-100 h-100 object-fit-cover movie-poster" 
                             alt="{{ $movie['title'] }}"
                             onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center opacity-0 hover-overlay rounded-lg">
                            <i class="bi bi-play-circle text-white" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </a>
                <div class="mt-3">
                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h6>
                    <div class="d-flex gap-1 flex-wrap">
                        @php
                            $genreNames = [];
                            foreach(array_slice($movie['genre_ids'] ?? [], 0, 2) as $genreId) {
                                $genre = collect($genres)->firstWhere('id', $genreId);
                                if ($genre) $genreNames[] = $genre['name'];
                            }
                        @endphp
                        @foreach($genreNames as $genre)
                            <span class="badge bg-secondary small">{{ $genre }}</span>
                        @endforeach
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-star-fill text-warning"></i> {{ number_format($movie['vote_average'], 1) }} • {{ date('Y', strtotime($movie['release_date'])) }}
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-film display-1 text-muted"></i>
                <h3 class="mt-3">No movies found</h3>
                <p class="text-muted">Try adjusting your search or filters</p>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if(isset($totalPages) && $totalPages > 1)
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                <a class="page-link bg-dark text-light border-dark" 
                   href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">
                    Previous
                </a>
            </li>
            
            @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                    <a class="page-link bg-dark text-light border-dark" 
                       href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">
                        {{ $i }}
                    </a>
                </li>
            @endfor
            
            <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                <a class="page-link bg-dark text-light border-dark" 
                   href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">
                    Next
                </a>
            </li>
        </ul>
    </nav>
@endif
@endsection

<style>
    .filter-select {
        background-color: #fff;
        border: 1px solid #1a1a1a;
        color: #1a1a1a;
        padding: 0.45rem 0.6rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.05em;
    }

    .filter-select.short {
        min-width: 110px;
        max-width: 140px;
        padding: 0.32rem 0.6rem;
        height: 40px;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
    }

    @media (max-width: 991px) {
        .filters-row {
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .search-top {
            max-width: 100%;
            width: 100%;
        }

        .search-top .search-box {
            width: 100%;
            max-width: 100%;
        }

        .filters-row .filter-select.short {
            min-width: 110px;
            max-width: 130px;
        }

        .filters-row {
            gap: 0.5rem;
        }
    }

    .search-top {
        display: flex;
        align-items: center;
        border: 1px solid #1a1a1a;
        border-radius: 0.5rem;
        overflow: hidden;
        padding-left: 0;
        width: 100%;
        max-width: 500px; /* further extended width */
    }

    .search-top .search-box {
        width: 100%;
        max-width: none;
        border: none !important;
        padding: 0.6rem 1rem;
        background-color: #ffffff;
        color: #1a1a1a;
        outline: none;
    }

    .search-top .search-box:focus {
        background-color: #ffffff;
        color: #1a1a1a;
        box-shadow: 0 0 0 0.12rem rgba(26,26,26,0.06);
    }

    /* Smaller All Films title */
    h1.all-films-title {
        font-size: 1.5rem !important; /* ensure override */
        font-weight: 700;
        letter-spacing: 0.02em;
        margin: 0;
        line-height: 1.15;
    }

    @media (max-width: 991px) {
        .search-top .search-box {
            max-width: 100%;
        }

        .all-films-title {
            font-size: 0.95rem;
        }
    }

    .filter-select:hover,
    .filter-select:focus {
        background-color: #ffffff;
        border-color: #1a1a1a;
        color: #1a1a1a;
        box-shadow: 0 0 0 0.06rem rgba(26, 26, 26, 0.08);
    }

    .filter-select option {
        background-color: #ffffff;
        color: #1a1a1a;
    }

    .movie-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .movie-card h6 {
        color: #1a1a1a;
    }

    .movie-card .text-muted {
        color: #666 !important;
    }

    .movie-poster-wrapper {
        aspect-ratio: 3 / 4.5;
        background: linear-gradient(135deg, #f0f0f0, #e8e8e8);
    }

    .movie-poster {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .movie-card:hover .movie-poster {
        transform: scale(1.05);
    }

    .hover-overlay {
        background: rgba(0, 0, 0, 0.5);
        transition: opacity 0.3s ease;
    }

    .movie-card:hover .hover-overlay {
        opacity: 1 !important;
    }

    /* Remove blue focus/tap highlight and make active same as hover on dashboard buttons/links */
    .filter-select:focus,
    .search-top .search-box:focus,
    .page-link:focus,
    .page-link:active,
    .movie-card a:focus,
    .movie-card a:active,
    button:focus,
    button:active {
        outline: none !important;
        box-shadow: none !important;
        -webkit-box-shadow: none !important;
        -webkit-tap-highlight-color: transparent;
    }

    .page-link:active,
    .page-link:focus {
        background-color: #1a1a1a !important;
        color: #fff !important;
        border-color: #1a1a1a !important;
        box-shadow: 0 0 0 0.06rem rgba(26, 26, 26, 0.08);
    }
</style>