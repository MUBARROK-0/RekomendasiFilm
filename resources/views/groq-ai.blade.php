@extends('layouts.app')

@section('title', 'Groq AI | MOODFLIX')

@section('content')
<div class="mood-ai-container">
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="main-title">What do you feel like watching today?</h1>
            <p class="lead text-muted mb-4">Enter your mood and movie preferences — let AI pick the best film for you.</p>

            <div class="search-container">
                <div class="search-top">
                    <input type="text" id="groqText" class="search-box" placeholder="A warm romantic comedy, cozy and funny">
                </div>
                <button id="groqBtn" class="search-button">Recommend</button>
            </div>

            <div id="groqStatus" class="status-message" style="display:none"></div>
        </div>
    </div>

    <div id="groqResult" style="display:none" class="result-section">
        <div class="container">
            <div id="groqCard"></div>
            <div class="text-center mt-4">
                <button id="groqReset" class="btn btn-outline-secondary">Back</button>
            </div>
        </div>
    </div>

    <!-- Mood selector removed per UI update -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const groqBtn = document.getElementById('groqBtn');
    const groqText = document.getElementById('groqText');
    const groqStatus = document.getElementById('groqStatus');
    const groqResult = document.getElementById('groqResult');
    const groqCard = document.getElementById('groqCard');
    const groqReset = document.getElementById('groqReset');

    groqBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const text = groqText.value.trim();
        if (!text || text.length < 3) {
            groqStatus.style.display = 'block';
            groqStatus.textContent = 'Masukkan minimal 3 karakter.';
            return;
        }

        groqStatus.style.display = 'block';
        groqStatus.textContent = 'Menganalisis...';
        groqBtn.disabled = true;

        fetch("{{ route('groq.analyze') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ text })
        }).then(async (res) => {
            groqBtn.disabled = false;
            if (!res.ok) {
                groqStatus.textContent = 'Server error: ' + res.status;
                return;
            }
            const data = await res.json();
            groqStatus.style.display = 'none';
            if (!data.movie) {
                groqResult.style.display = 'block';
                groqCard.innerHTML = `<div class="alert alert-warning">No recommendation. ${data.error ? ('Error: ' + data.error) : ''}</div>`;
                return;
            }

            const m = data.movie;
            const poster = m.poster_url || 'https://via.placeholder.com/300x450?text=No+Poster';
            groqResult.style.display = 'block';
            groqCard.innerHTML = `
                <div class="recommendation-card">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <img src="${poster}" class="img-fluid rounded" onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">
                        </div>
                        <div class="col-md-8">
                            <h3>${m.title}</h3>
                            <div class="mb-2"><strong>Year:</strong> ${m.release_year || 'N/A'} • <strong>Rating:</strong> ${m.rating || 'N/A'}</div>
                            <p>${m.overview || ''}</p>
                            ${m.detail_local_url ? `<p><a href="${m.detail_local_url}" class="btn btn-primary">View Details</a></p>` : (m.detail_url ? `<p><a href="${m.detail_url}" target="_blank" class="btn btn-primary">View Details</a></p>` : '')}
                            ${m.reason ? `<p class="text-muted"><em>${m.reason}</em></p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).catch(err => {
            groqBtn.disabled = false;
            groqStatus.textContent = 'Request failed';
            console.error(err);
        });
    });

    groqReset.addEventListener('click', function (e) {
        e.preventDefault();
        groqText.value = '';
        groqResult.style.display = 'none';
        groqCard.innerHTML = '';
    });
});
</script>

<style>
/* Groq AI — refined visual style (no logic changes) */
.hero-section {
    padding: 64px 40px;
    background-color: #ffffff;
}

.hero-content {
    text-align: center;
    max-width: 980px;
    margin: 0 auto;
}

.subtitle {
    font-size: 0.95rem;
    font-weight: 700;
    color: #6b6b6b;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.main-title {
    font-size: 3rem;
    font-weight: 800;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.lead {
    font-size: 1rem;
    color: #6b6b6b;
}

.search-container {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.25rem;
    align-items: center;
}

.search-container .search-top {
    display: flex;
    align-items: center;
    border: 1px solid #1a1a1a;
    border-radius: 0.5rem;
    overflow: hidden;
    padding-left: 0;
    width: 100%;
    max-width: 720px;
    background-color: #ffffff;
}

.search-container .search-top .search-box {
    width: 100%;
    border: none !important;
    padding: 0.85rem 1.25rem;
    background-color: #ffffff;
    color: #1a1a1a;
    outline: none;
    font-size: 1rem;
}

.search-container .search-top .search-box:focus {
    background-color: #ffffff;
    color: #1a1a1a;
    box-shadow: 0 0 0 0.12rem rgba(26,26,26,0.06);
}

.search-button {
    padding: 0.95rem 1.35rem;
    border-radius: 999px;
    background: linear-gradient(180deg, #ff6b7a, #e94560);
    color: #fff;
    border: none;
    font-weight: 700;
    box-shadow: 0 8px 24px rgba(233,69,96,0.18);
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
}

.search-button:hover { transform: translateY(-2px); }
.search-button:active { transform: translateY(0); }
.search-button:focus { outline: none; box-shadow: 0 0 0 0.06rem rgba(233,69,96,0.12); }

.status-message { margin-top: 1rem; color: #666; font-size: 0.95rem; }

.result-section { padding: 36px 24px; background: #fff; }

.recommendation-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem;
    box-shadow: 0 20px 40px rgba(12,12,12,0.06);
    border: 1px solid rgba(0,0,0,0.04);
}

.recommendation-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.recommendation-card h3 { font-size: 1.5rem; font-weight: 700; color: #111; margin-top: 0; }
.recommendation-card p { color: #444; line-height: 1.65; }

.recommendation-meta { color: #6b6b6b; font-size: 0.95rem; margin-bottom: 0.75rem; }
.recommendation-reason { color: #777; font-style: italic; font-size: 0.9rem; }

/* Mood cards */
.mood-cards-section .card { transition: transform 0.18s ease, box-shadow 0.18s ease; cursor: pointer; }
.mood-cards-section .card:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(15,15,15,0.06); }

@media (max-width: 768px) {
    .main-title { font-size: 2rem; }
    .search-container { flex-direction: column; gap: 0.75rem; }
    .search-button { width: 100%; }
    .recommendation-card { padding: 1rem; }
}
</style>
@endsection