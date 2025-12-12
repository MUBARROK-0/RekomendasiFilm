@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Movie Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('mood.ai') }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-robot me-1"></i> Try Mood AI
            </a>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="row mb-4">
    <div class="col-12">
        <form action="{{ route('dashboard') }}" method="GET" id="filterForm">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control search-box" 
                               placeholder="Search movies..." 
                               name="search" 
                               value="{{ $search ?? '' }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="row g-2">
                        <div class="col">
                            <select class="form-select search-box" name="genre">
                                <option value="">All Genres</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre['id'] }}" {{ ($filters['genre'] ?? '') == $genre['id'] ? 'selected' : '' }}>
                                        {{ $genre['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col">
                            <select class="form-select search-box" name="year">
                                <option value="">All Years</option>
                                @for($y = date('Y'); $y >= 2000; $y--)
                                    <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <div class="col">
                            <select class="form-select search-box" name="rating">
                                <option value="">Any Rating</option>
                                <option value="7" {{ ($filters['rating'] ?? '') == '7' ? 'selected' : '' }}>7+ Stars</option>
                                <option value="8" {{ ($filters['rating'] ?? '') == '8' ? 'selected' : '' }}>8+ Stars</option>
                                <option value="9" {{ ($filters['rating'] ?? '') == '9' ? 'selected' : '' }}>9+ Stars</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Movies Grid -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    @forelse($movies as $movie)
        <div class="col">
            <div class="card h-100">
                <a href="{{ route('film.detail', $movie['id']) }}">
                    <img src="{{ $tmdb->getImageUrl($movie['poster_path'], 'w500') }}" 
                         class="card-img-top" 
                         alt="{{ $movie['title'] }}"
                         onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
                </a>
                <div class="card-body">
                    <h5 class="card-title">{{ $movie['title'] }}</h5>
                    <div class="mb-2">
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-star-fill me-1"></i> {{ number_format($movie['vote_average'], 1) }}
                        </span>
                        <span class="text-muted ms-2">{{ date('Y', strtotime($movie['release_date'])) }}</span>
                    </div>
                    <p class="card-text small text-muted">
                        {{ Str::limit($movie['overview'], 100) }}
                    </p>
                    <div class="d-flex flex-wrap">
                        @foreach(array_slice($movie['genre_ids'] ?? [], 0, 2) as $genreId)
                            @php
                                $genreName = collect($genres)->firstWhere('id', $genreId)['name'] ?? '';
                            @endphp
                            @if($genreName)
                                <span class="badge badge-genre mb-1">{{ $genreName }}</span>
                            @endif
                        @endforeach
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