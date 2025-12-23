@extends('layouts.app')

@section('title', 'Mood AI | MOODFLIX')

@section('content')
<div class="mood-ai-container">
    <!-- Main Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h2 class="subtitle">STOP SEARCHING</h2>
            <h1 class="main-title">START WATCHING</h1>
            
            <div class="search-container">
                <input 
                    type="text" 
                    id="analyzeText" 
                    class="search-input" 
                    placeholder="Description ur mood..."
                >
                <button id="analyzeBtn" class="search-button">Search</button>
            </div>
            
            <div id="analyzeStatus" class="status-message" style="display:none"></div>
        </div>
    </div>

    <!-- Analyze Result (Hidden by default) -->
    <div id="analyzeResult" style="display:none" class="result-section">
        <div class="container">
            <div id="analyzeCard"></div>
            <div class="text-center mt-4">
                <button id="analyzeReset" class="btn btn-outline-secondary">Back to Search</button>
            </div>
        </div>
    </div>

    <!-- Mood Selection Cards (Hidden by default) -->
    <div id="moodCardsSection" style="display:none" class="mood-cards-section">
        <div class="container">
            <h3 class="text-center mb-5">Or select your mood</h3>
            <form action="{{ route('mood.recommend') }}" method="POST" id="moodForm">
                @csrf
                <input type="hidden" name="mood" id="selectedMood">
                
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
        </div>
    </div>
            
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
                                            @php
                                                $poster = $movie['poster_url'] ?? $movie['poster'] ?? ($movie['poster_path'] ?? null);
                                                $year = $movie['release_year'] ?? ($movie['release_date'] ?? null);
                                                $rating = $movie['rating'] ?? ($movie['vote_average'] ?? null);
                                            @endphp
                                            <img src="{{ $poster ?? 'https://via.placeholder.com/300x450?text=No+Poster' }}" 
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
                                                        <i class="bi bi-star-fill me-1"></i> {{ isset($rating) ? number_format($rating, 1) : 'N/A' }}
                                                    </span>
                                                    <span class="text-muted ms-2">{{ $year ? (is_numeric($year) ? $year : date('Y', strtotime($year))) : '' }}</span>
                                                </div>
                                                <p class="card-text small text-muted">
                                                    {{ Str::limit($movie['overview'] ?? '', 120) }}
                                                </p>
                                                @if(!empty($movie['detail_url']))
                                                    <a href="{{ $movie['detail_url'] }}" target="_blank"
                                                       class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                @endif
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
    
    // AJAX analyze handler
    document.addEventListener('DOMContentLoaded', function () {
        const analyzeBtn = document.getElementById('analyzeBtn');
        const analyzeReset = document.getElementById('analyzeReset');
        const analyzeText = document.getElementById('analyzeText');
        const analyzeStatus = document.getElementById('analyzeStatus');
        const analyzeResult = document.getElementById('analyzeResult');
        const analyzeCard = document.getElementById('analyzeCard');

        analyzeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const text = analyzeText.value.trim();
            if (!text || text.length < 3) {
                analyzeStatus.style.display = 'block';
                analyzeStatus.textContent = 'Masukkan minimal 3 karakter.';
                return;
            }

            analyzeStatus.style.display = 'block';
            analyzeStatus.textContent = 'Menganalisis...';
            analyzeBtn.disabled = true;

            fetch("{{ route('mood.analyze') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ text })
            }).then(async (res) => {
                analyzeBtn.disabled = false;
                if (!res.ok) {
                    const err = await res.text();
                    analyzeStatus.textContent = 'Server error: ' + res.status;
                    console.error(err);
                    return;
                }

                const data = await res.json();
                analyzeStatus.style.display = 'none';

                if (!data.movie) {
                    analyzeResult.style.display = 'block';

                    // Show DeepSeek error if available
                    if (data.analysis && data.analysis.deepseek_error) {
                        analyzeCard.innerHTML = `<div class="alert alert-warning">DeepSeek tidak dapat dihubungi: ${data.analysis.deepseek_error}</div>`;
                    } else {
                        analyzeCard.innerHTML = `<div class="alert alert-warning">Tidak ada rekomendasi ditemukan.</div>`;
                    }

                    return;
                }

                const m = data.movie;
                const posterUrl = m.poster_url || m.poster || m.poster_path || 'https://via.placeholder.com/300x450?text=No+Poster';

                analyzeResult.style.display = 'block';
                const explanation = data.analysis && data.analysis.explanation ? data.analysis.explanation : '';
                const releaseYear = m.release_year || (m.release_date ? new Date(m.release_date).getFullYear() : 'N/A');
                const rating = m.rating ? Number(m.rating).toFixed(1) : 'N/A';

                // Build details button only if a detail_url is present
                const detailsButton = m.detail_url ? `<a href="${m.detail_url}" target="_blank" class="btn btn-primary">View Details</a>` : '';

                analyzeCard.innerHTML = `
                    <div class="recommendation-header mb-4">
                        <p class="explanation-text">${explanation}</p>
                    </div>
                    <div class="recommendation-card">
                        <div class="row g-4">
                            <div class="col-lg-4 col-md-5">
                                <div class="recommendation-poster">
                                    <img src="${posterUrl}" class="img-fluid" style="object-fit:cover; width:100%; height:100%; border-radius:12px;" onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-7">
                                <h2 class="recommendation-title">${m.title}</h2>
                                <div class="recommendation-metadata">
                                    <span class="metadata-item">${releaseYear}</span>
                                    <span class="metadata-separator">•</span>
                                    <span class="metadata-item"><i class="bi bi-star-fill" style="color: #ffc107;"></i> ${rating}/10</span>
                                </div>
                                <p class="recommendation-overview mt-4">${m.overview ? m.overview : 'No overview available'}</p>
                                <div class="mt-4">
                                    ${detailsButton}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).catch(err => {
                analyzeBtn.disabled = false;
                analyzeStatus.textContent = 'Request failed';
                console.error(err);
            });
        });

        analyzeReset.addEventListener('click', function (e) {
            e.preventDefault();
            analyzeText.value = '';
            analyzeStatus.style.display = 'none';
            analyzeResult.style.display = 'none';
            analyzeCard.innerHTML = '';
        });
    });
</script>

<style>
    .mood-ai-container {
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 280px);
    }

    /* Hero Section */
    .hero-section {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 40px;
        background-color: #ffffff;
    }

    .hero-content {
        text-align: center;
        max-width: 900px;
        width: 100%;
    }

    .subtitle {
        font-size: 1.125rem;
        font-weight: 600;
        letter-spacing: 0.15em;
        color: #666;
        margin-bottom: 1rem;
        text-transform: uppercase;
    }

    .main-title {
        font-size: 4rem;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 3rem;
        line-height: 1.1;
    }

    .search-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .search-input {
        flex: 1;
        min-width: 300px;
        max-width: 600px;
        padding: 1rem 1.5rem;
        border: 2px solid #1a1a1a;
        border-radius: 50px;
        font-size: 1rem;
        background-color: #ffffff;
        color: #1a1a1a;
        outline: none;
        transition: all 0.3s ease;
    }

    .search-input::placeholder {
        color: #999;
    }

    .search-input:focus {
        border-color: #e94560;
        box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
    }

    .search-button {
        padding: 1rem 2.5rem;
        background-color: #777;
        color: #ffffff;
        border: none;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-button:hover {
        background-color: #666;
        transform: translateY(-2px);
    }

    .search-button:active {
        transform: translateY(0);
    }

    .status-message {
        font-size: 0.95rem;
        color: #666;
        margin-top: 1rem;
    }

    /* Result Section */
    .result-section {
        padding: 40px;
        background-color: #ffffff;
    }

    /* Mood Cards Section */
    .mood-cards-section {
        padding: 40px;
        background-color: #ffffff;
    }

    /* Recommendation Styling */
    .recommendation-header {
        padding: 0;
    }

    .explanation-text {
        font-size: 1rem;
        line-height: 1.8;
        color: #333;
        margin: 0;
        font-style: italic;
    }

    .recommendation-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 2rem;
    }

    .recommendation-poster {
        position: relative;
        padding-top: 150%;
        overflow: hidden;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .recommendation-poster img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .recommendation-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }

    .recommendation-metadata {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        color: #666;
        margin-bottom: 1.5rem;
    }

    .recommendation-metadata .metadata-item {
        color: #333;
    }

    .recommendation-metadata .metadata-separator {
        color: #999;
        margin: 0 0.25rem;
    }

    .recommendation-overview {
        font-size: 0.95rem;
        line-height: 1.8;
        color: #333;
        margin-bottom: 1.5rem;
    }

    .recommendation-card .btn-primary {
        background-color: #e94560;
        border-color: #e94560;
        padding: 0.6rem 1.5rem;
        font-weight: 500;
    }

    .recommendation-card .btn-primary:hover {
        background-color: #d13a54;
        border-color: #d13a54;
    }
    @media (max-width: 768px) {
        .hero-section {
            padding: 40px 20px;
        }

        .main-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .subtitle {
            font-size: 1rem;
        }

        .search-container {
            flex-direction: column;
            gap: 0.75rem;
        }

        .search-input {
            min-width: 100%;
            max-width: 100%;
        }

        .search-button {
            width: 100%;
        }
    }
</style>
@endsection