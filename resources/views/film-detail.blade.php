@extends('layouts.app')

@section('title', $movie['title'] . ' | MOODFLIX')

@section('content')
<div class="container py-4">
    <!-- Back Button -->
    <a href="{{ url()->previous() }}" class="btn btn-outline-light mb-4">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
    
    <div class="row">
        <!-- Poster -->
        <div class="col-md-4 mb-4">
            <img src="{{ $tmdb->getImageUrl($movie['poster_path'], 'w500') }}" 
                 class="movie-poster" 
                 alt="{{ $movie['title'] }}"
                 onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
        </div>
        
        <!-- Details -->
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">{{ $movie['title'] }}</h1>
            <h4 class="text-muted">{{ $movie['original_title'] ?? '' }}</h4>
            
            <div class="d-flex flex-wrap align-items-center gap-3 my-4">
                <div class="rating-badge bg-warning text-dark p-2 rounded">
                    <i class="bi bi-star-fill me-1"></i>
                    <span class="fw-bold fs-5">{{ number_format($movie['vote_average'], 1) }}</span>
                    <small class="text-muted">/10</small>
                </div>
                
                <div class="text-muted">
                    <i class="bi bi-calendar me-1"></i> {{ date('F d, Y', strtotime($movie['release_date'])) }}
                </div>
                
                <div class="text-muted">
                    <i class="bi bi-clock me-1"></i> {{ $movie['runtime'] ?? 'N/A' }} min
                </div>
                
                @if($movie['spoken_languages'] ?? false)
                    <div class="text-muted">
                        <i class="bi bi-translate me-1"></i> 
                        {{ collect($movie['spoken_languages'])->pluck('english_name')->first() }}
                    </div>
                @endif
            </div>
            
            <!-- Genres -->
            <div class="mb-4">
                @foreach($movie['genres'] ?? [] as $genre)
                    <span class="badge badge-genre px-3 py-2 me-2 mb-2">{{ $genre['name'] }}</span>
                @endforeach
            </div>
            
            <!-- Overview -->
            <div class="card bg-dark border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title">Overview</h5>
                    <p class="card-text">{{ $movie['overview'] }}</p>
                </div>
            </div>
            
            <!-- Additional Info -->
            <div class="row">
                @if($movie['production_countries'] ?? false)
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-0">
                            <div class="card-body">
                                <h6><i class="bi bi-globe me-2"></i>Country</h6>
                                <p class="mb-0">
                                    {{ collect($movie['production_countries'])->pluck('name')->implode(', ') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($movie['production_companies'] ?? false)
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-0">
                            <div class="card-body">
                                <h6><i class="bi bi-building me-2"></i>Production</h6>
                                <p class="mb-0">
                                    {{ collect($movie['production_companies'])->pluck('name')->take(3)->implode(', ') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($movie['budget'] ?? false)
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-0">
                            <div class="card-body">
                                <h6><i class="bi bi-cash-coin me-2"></i>Budget</h6>
                                <p class="mb-0">${{ number_format($movie['budget']) }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($movie['revenue'] ?? false)
                    <div class="col-md-6 mb-3">
                        <div class="card bg-dark border-0">
                            <div class="card-body">
                                <h6><i class="bi bi-graph-up me-2"></i>Revenue</h6>
                                <p class="mb-0">${{ number_format($movie['revenue']) }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Tagline -->
            @if($movie['tagline'] ?? false)
                <div class="alert alert-dark border-0 mt-3">
                    <em>"{{ $movie['tagline'] }}"</em>
                </div>
            @endif
            
            <!-- Mood AI Recommendation -->
            <div class="mt-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary border-0">
                        <i class="bi bi-robot me-2"></i> Mood AI Suggestion
                    </div>
                    <div class="card-body">
                        <p>Based on this movie's genres, you might be in the mood for:</p>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $genres = collect($movie['genres'] ?? [])->pluck('name')->toArray();
                                $moodSuggestions = [];
                                
                                if (in_array('Comedy', $genres) || in_array('Family', $genres)) {
                                    $moodSuggestions[] = 'happy';
                                }
                                if (in_array('Drama', $genres) || in_array('Romance', $genres)) {
                                    $moodSuggestions[] = 'sad';
                                }
                                if (in_array('Action', $genres) || in_array('Adventure', $genres)) {
                                    $moodSuggestions[] = 'excited';
                                }
                                if (in_array('Horror', $genres) || in_array('Thriller', $genres)) {
                                    $moodSuggestions[] = 'fear';
                                }
                                
                                $moodSuggestions = array_slice(array_unique($moodSuggestions), 0, 3);
                            @endphp
                            
                            @foreach($moodSuggestions as $mood)
                                <a href="{{ route('mood.ai') }}?mood={{ $mood }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    {{ ucfirst($mood) }} Mood
                                </a>
                            @endforeach
                            
                            <a href="{{ route('mood.ai') }}" class="btn btn-primary btn-sm ms-auto">
                                Try Mood AI <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection