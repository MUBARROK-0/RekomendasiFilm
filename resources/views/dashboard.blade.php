@extends('layouts.app')

@section('content')
<!-- Header Section -->
<div class="mb-5">
    <h1 class="display-5 fw-bold mb-1">All Films</h1>
</div>

<!-- Search and Filter -->
<div class="mb-4">
    <form action="{{ route('dashboard') }}" method="GET" id="filterForm">
        <!-- Search Bar -->
        <div class="mb-3">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-transparent border-secondary">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" 
                       class="form-control search-box border-secondary" 
                       placeholder="Search" 
                       name="search" 
                       value="{{ $search ?? '' }}">
            </div>
        </div>
        
        <!-- Filter Dropdowns -->
        <div class="row g-2">
            <div class="col-md-3 col-6">
                <select class="form-select filter-select" name="year">
                    <option value="">YEAR</option>
                    @for($y = date('Y'); $y >= 2000; $y--)
                        <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div class="col-md-3 col-6">
                <select class="form-select filter-select" name="country">
                    <option value="">COUNTRY</option>
                    <option value="US" {{ ($filters['country'] ?? '') == 'US' ? 'selected' : '' }}>United States</option>
                    <option value="GB" {{ ($filters['country'] ?? '') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                    <option value="JP" {{ ($filters['country'] ?? '') == 'JP' ? 'selected' : '' }}>Japan</option>
                    <option value="KR" {{ ($filters['country'] ?? '') == 'KR' ? 'selected' : '' }}>South Korea</option>
                </select>
            </div>
            
            <div class="col-md-3 col-6">
                <select class="form-select filter-select" name="rating">
                    <option value="">RATING</option>
                    <option value="7" {{ ($filters['rating'] ?? '') == '7' ? 'selected' : '' }}>7+ Stars</option>
                    <option value="8" {{ ($filters['rating'] ?? '') == '8' ? 'selected' : '' }}>8+ Stars</option>
                    <option value="9" {{ ($filters['rating'] ?? '') == '9' ? 'selected' : '' }}>9+ Stars</option>
                </select>
            </div>
            
            <div class="col-md-3 col-6">
                <select class="form-select filter-select" name="genre">
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
                    <h6 class="fw-bold text-light mb-1 text-truncate" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h6>
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
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        color: #1a1a1a;
        padding: 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.05em;
    }

    .filter-select:hover,
    .filter-select:focus {
        background-color: #ffffff;
        border-color: #e94560;
        color: #1a1a1a;
        box-shadow: 0 0 0 0.25rem rgba(233, 69, 96, 0.15);
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
</style>