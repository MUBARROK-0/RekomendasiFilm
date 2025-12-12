<aside class="sidebar">
    <div class="position-sticky pt-4 px-3">
        <!-- Logo -->
        <div class="mb-5">
            <h2 class="fw-bold text-light" style="font-size: 1.3rem; letter-spacing: 2px;">MOODFLIX</h2>
        </div>
        
        <!-- Navigation -->
        <nav class="nav flex-column gap-3">
            <a class="nav-link text-light d-flex align-items-center" href="{{ route('dashboard') }}" style="padding: 0.75rem 1rem; border-radius: 0.375rem; transition: all 0.3s;">
                <i class="bi bi-grid-3x2 me-3" style="font-size: 1.25rem;"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link text-light d-flex align-items-center" href="{{ route('mood.ai') }}" style="padding: 0.75rem 1rem; border-radius: 0.375rem; transition: all 0.3s;">
                <i class="bi bi-robot me-3" style="font-size: 1.25rem;"></i>
                <span>Mood AI</span>
            </a>
        </nav>
    </div>
</aside>

<style>
    .sidebar {
        background-color: #1a1a1f;
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link {
        color: #b0b0b0 !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
        color: #ffffff !important;
        background-color: rgba(233, 69, 96, 0.1);
        padding-left: 1.5rem;
    }

    .sidebar .nav-link.active {
        color: #e94560 !important;
        background-color: rgba(233, 69, 96, 0.15);
        border-left: 3px solid #e94560;
        padding-left: 0.75rem;
    }
</style>