@extends('layouts.app')

@section('title', 'Mood AI | MOODFLIX')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold"><i class="bi bi-robot text-primary"></i> Mood AI</h1>
                <p class="lead text-muted">Select your current mood and we'll recommend the perfect movies for you</p>
            </div>
            
            <!-- Mood Selection Form -->
            <form action="{{ route('mood.recommend') }}" method="POST" id="moodForm">
                @csrf
                <input type="hidden" name="mood" id="selectedMood">
                
                <!-- Mood Selection Cards -->
                <div class="row g-4 mb-5">
                    @foreach($moods as $key => $mood)
                        <div class="col-md-4 col-sm-6">
                            <div class="card mood-card border-{{ $mood['color'] }} text-center h-100"
                                 onclick="selectMood('{{ $key }}')"
                                 style="cursor: pointer;">
                                <div class="card-body py-5">
                                    <div class="display-1 mb-3">
                                        @php
                                            $icon = match($key) {
                                                'happy' => 'bi-emoji-smile',
                                                'sad' => 'bi-emoji-frown',
                                                'excited' => 'bi-emoji-heart-eyes',
                                                'chill' => 'bi-emoji-sunglasses',
                                                'fear' => 'bi-emoji-dizzy',
                                                'adventure' => 'bi-compass',
                                                'romantic' => 'bi-heart',
                                                'mystery' => 'bi-search',
                                                default => 'bi-emoji-neutral'
                                            };
                                        @endphp
                                        <i class="bi {{ $icon }}"></i>
                                    </div>
                                    <h3 class="card-title">{{ $mood['name'] }}</h3>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
            
            <!-- Recommendations -->
            @if(isset($movies) && count($movies) > 0)
                <div class="mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="bi bi-film text-primary me-2"></i>
                            Recommended for {{ $moodName ?? '' }} Mood
                        </h2>
                        <span class="badge bg-primary">{{ count($movies) }} movies</span>
                    </div>
                    
                    <div class="row g-4">
                        @foreach($movies as $movie)
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <img src="{{ $tmdb->getImageUrl($movie['poster_path'], 'w300') }}" 
                                                 class="img-fluid rounded-start h-100" 
                                                 alt="{{ $movie['title'] }}"
                                                 style="object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $movie['title'] }}</h5>
                                                <div class="mb-2">
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-star-fill me-1"></i> {{ number_format($movie['vote_average'], 1) }}
                                                    </span>
                                                    <span class="text-muted ms-2">{{ date('Y', strtotime($movie['release_date'])) }}</span>
                                                </div>
                                                <p class="card-text small text-muted">
                                                    {{ Str::limit($movie['overview'], 120) }}
                                                </p>
                                                <a href="{{ route('film.detail', $movie['id']) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Reset Button -->
                    <div class="text-center mt-4">
                        <a href="{{ route('mood.ai') }}" class="btn btn-outline-light">
                            <i class="bi bi-arrow-repeat me-1"></i> Select Different Mood
                        </a>
                    </div>
                </div>
            @elseif(request()->isMethod('post'))
                <div class="text-center py-5">
                    <i class="bi bi-emoji-neutral display-1 text-muted"></i>
                    <h3 class="mt-3">No recommendations found</h3>
                    <p class="text-muted">Try selecting a different mood</p>
                    <a href="{{ route('mood.ai') }}" class="btn btn-primary mt-3">
                        Try Again
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function selectMood(mood) {
        document.getElementById('selectedMood').value = mood;
        document.getElementById('moodForm').submit();
    }
</script>
@endsection