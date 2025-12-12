@extends('layouts.app')

@section('title', $movie['title'] . ' | MOODFLIX')

@section('content')
<div class="detail-container">
    <div class="row g-5">
        <!-- Poster Section -->
        <div class="col-lg-4 col-md-5">
            <div class="poster-wrapper">
                <img src="{{ $tmdb->getImageUrl($movie['poster_path'], 'w500') }}" 
                     class="movie-poster-detail" 
                     alt="{{ $movie['title'] }}"
                     onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
            </div>
        </div>
        
        <!-- Details Section -->
        <div class="col-lg-8 col-md-7">
            <!-- Title -->
            <h1 class="display-4 fw-bold mb-1">{{ $movie['title'] }}</h1>
            
            <!-- Metadata: Year, Country, Duration, Rating -->
            <div class="metadata-line mb-3">
                <span class="metadata-item">{{ date('Y', strtotime($movie['release_date'])) }}</span>
                <span class="metadata-separator">•</span>
                @if($movie['production_countries'] ?? false)
                    <span class="metadata-item">
                        {{ collect($movie['production_countries'])->pluck('name')->first() }}
                    </span>
                    <span class="metadata-separator">•</span>
                @endif
                <span class="metadata-item">{{ $movie['runtime'] ?? 'N/A' }} min</span>
                <span class="metadata-separator">•</span>
                <span class="metadata-item">
                    <i class="bi bi-star-fill text-warning"></i> {{ number_format($movie['vote_average'], 1) }}/10
                </span>
            </div>
            
            <!-- Genre Badges -->
            <div class="genre-badges mb-4">
                @foreach($movie['genres'] ?? [] as $genre)
                    <span class="genre-badge">{{ $genre['name'] }}</span>
                @endforeach
            </div>
            
            <!-- Overview/Synopsis -->
            @if($movie['overview'])
                <div class="synopsis-section">
                    <p class="synopsis-text">{{ $movie['overview'] }}</p>
                </div>
            @endif
            
            <!-- Additional Info Cards -->
            <div class="additional-info mt-5">
                <div class="info-grid">
                    @if($movie['production_countries'] ?? false)
                        <div class="info-card">
                            <div class="info-label">Country</div>
                            <div class="info-value">
                                {{ collect($movie['production_countries'])->pluck('name')->implode(', ') }}
                            </div>
                        </div>
                    @endif
                    
                    @if($movie['production_companies'] ?? false)
                        <div class="info-card">
                            <div class="info-label">Production</div>
                            <div class="info-value">
                                {{ collect($movie['production_companies'])->pluck('name')->take(2)->implode(', ') }}
                            </div>
                        </div>
                    @endif
                    
                    @if($movie['budget'] ?? false)
                        <div class="info-card">
                            <div class="info-label">Budget</div>
                            <div class="info-value">${{ number_format($movie['budget']) }}</div>
                        </div>
                    @endif
                    
                    @if($movie['revenue'] ?? false)
                        <div class="info-card">
                            <div class="info-label">Revenue</div>
                            <div class="info-value">${{ number_format($movie['revenue']) }}</div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Tagline -->
            @if($movie['tagline'] ?? false)
                <div class="tagline-section mt-4">
                    <em>"{{ $movie['tagline'] }}"</em>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

<style>
    .detail-container {
        padding: 0;
    }

    /* Poster */
    .poster-wrapper {
        position: sticky;
        top: 30px;
    }

    .movie-poster-detail {
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
        transition: transform 0.3s ease;
    }

    .movie-poster-detail:hover {
        transform: scale(1.02);
    }

    /* Title and Metadata */
    h1 {
        color: #1a1a1a;
        line-height: 1.2;
        margin-bottom: 0.5rem !important;
    }

    .metadata-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.95rem;
        color: #666;
    }

    .metadata-item {
        color: #333;
    }

    .metadata-separator {
        color: #999;
        margin: 0 0.25rem;
    }

    /* Genre Badges */
    .genre-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .genre-badge {
        display: inline-block;
        background-color: #f0f0f0;
        color: #333;
        padding: 0.5rem 1.25rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid #ddd;
    }

    /* Synopsis */
    .synopsis-section {
        margin-top: 2rem;
    }

    .synopsis-text {
        color: #333;
        line-height: 1.8;
        font-size: 0.95rem;
    }

    /* Additional Info Grid */
    .additional-info {
        border-top: 1px solid #e0e0e0;
        padding-top: 2rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1.5rem;
    }

    .info-card {
        border: 1px solid #e0e0e0;
        padding: 1rem;
        border-radius: 8px;
        background-color: #f9f9f9;
    }

    .info-label {
        font-size: 0.75rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .info-value {
        color: #1a1a1a;
        font-weight: 500;
    }

    /* Tagline */
    .tagline-section {
        color: #555;
        font-style: italic;
        padding: 1.5rem;
        background-color: #f5f5f5;
        border-left: 3px solid #e94560;
        border-radius: 4px;
    }

    .btn-primary {
        background-color: #e94560;
        border-color: #e94560;
    }

    .btn-primary:hover {
        background-color: #d13a54;
        border-color: #d13a54;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .poster-wrapper {
            position: static;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 1.75rem;
        }

        .metadata-line {
            font-size: 0.85rem;
        }

        .genre-badge {
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
        }

        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>