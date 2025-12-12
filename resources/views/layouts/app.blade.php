<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MOODFLIX')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #17181C;
            --main-bg: #0f0f1a;
            --card-bg: #16213e;
            --text-light: #e6e6e6;
            --primary-color: #e94560ff;
        }
        
        body {
            background-color: #ffffff;
            color: #1a1a1a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            width: 292px;
            padding-top: 20px;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px 40px;
            background-color: #ffffff;
            color: #1a1a1a;
        }
        
        .card {
            background-color: var(--card-bg);
            border: none;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .card-img-top {
            height: 300px;
            object-fit: cover;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #1a1a1a;
        }
        
        .badge-genre {
            background-color: var(--primary-color);
            margin-right: 5px;
        }
        
        .search-box {
            background-color: rgba(255,255,255,0.1);
            border: none;
            color: white;
        }
        
        .search-box:focus {
            background-color: rgba(255,255,255,0.15);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(233, 69, 96, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #d13a54;
            border-color: #d13a54;
        }
        
        .mood-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .mood-card:hover {
            transform: scale(1.05);
        }
        
        .movie-poster {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    @include('layouts.sidebar')
    
    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Active sidebar link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            
            sidebarLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
        });
        
        // Form submission for mood selection
        function selectMood(mood) {
            document.getElementById('selectedMood').value = mood;
            document.getElementById('moodForm').submit();
        }
        
        // Filter form submission
        document.getElementById('filterForm')?.addEventListener('change', function() {
            this.submit();
        });
    </script>
</body>
</html>