<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h1 class="fw-bold text-danger">MOODFLIX</h1>
            <p class="text-muted">Find films for your mood</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-light d-flex align-items-center" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-light d-flex align-items-center" href="{{ route('mood.ai') }}">
                    <i class="bi bi-robot me-2"></i>
                    Mood AI
                </a>
            </li>
        </ul>
        
        <div class="mt-5 px-3">
            <div class="card bg-dark text-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle me-2"></i>About MOODFLIX</h6>
                    <p class="card-text small">
                        Discover movies based on your current mood using our AI recommendation system.
                    </p>
                    <div class="text-muted small">
                        <i class="bi bi-database me-1"></i> Powered by TMDB API
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>