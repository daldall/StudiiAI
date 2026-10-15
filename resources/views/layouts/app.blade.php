<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'StudiiAI') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            {!! file_get_contents(resource_path('css/app.css')) !!}
        </style>
        @php($academyVersion = file_exists(public_path('js/academy.js')) ? filemtime(public_path('js/academy.js')) : time())
        <script src="{{ asset('js/academy.js') }}?v={{ $academyVersion }}" defer></script>
    @endif
</head>
<body class="app-body">
<a class="skip-link" href="#main-content">Skip to main content</a>

<header class="app-header border-bottom">
    <div class="app-header-inner">
        <div class="d-flex align-items-center gap-3">
            <button class="icon-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation">
                <i class="bi bi-list"></i>
            </button>

            <a class="brand-link" href="{{ route('dashboard') }}">
                <div class="brand-mark">A</div>
                <div>
                    <div class="brand-title">Academy</div>
                    <div class="brand-subtitle">Study companion</div>
                </div>
            </a>
        </div>

        <div class="header-search d-none d-md-flex" role="search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control border-0" placeholder="Cari tools, riwayat, atau rangkuman" aria-label="Search tools">
        </div>

        <div class="d-flex align-items-center gap-2">
            <a class="icon-button" href="#history" aria-label="Open history">
                <i class="bi bi-clock-history"></i>
            </a>
            <div class="profile-chip">
                <span class="profile-dot"></span>
                <span class="d-none d-sm-inline">Mahasiswa</span>
            </div>
        </div>
    </div>
</header>

<div class="app-shell">
    <aside class="app-sidebar border-end d-none d-lg-flex">
        <div class="sidebar-section">
            <div class="sidebar-heading">Tools</div>
            <nav class="nav flex-column gap-1 sidebar-nav">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid-1x2 me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="#text-tools">
                    <i class="bi bi-file-earmark-arrow-up me-2"></i>PDF Rangkuman
                </a>
                <a class="nav-link" href="#flashcards">
                    <i class="bi bi-stack me-2"></i>Flashcards & Kuis
                </a>
                <a class="nav-link" href="#citation">
                    <i class="bi bi-journal-text me-2"></i>Sitasi
                </a>
                <a class="nav-link" href="#planner">
                    <i class="bi bi-calendar2-week me-2"></i>Study Planner
                </a>
                <a class="nav-link" href="#history">
                    <i class="bi bi-clock-history me-2"></i>Riwayat
                </a>
            </nav>
        </div>

        <div class="sidebar-section sidebar-card">
            <div class="sidebar-heading">Quick stats</div>
            <div class="sidebar-stat">
                <span>AI summary</span>
                <strong>Detail & terarah</strong>
            </div>
            <div class="sidebar-stat">
                <span>Flashcards</span>
                <strong>Interaktif</strong>
            </div>
            <div class="sidebar-stat">
                <span>Study plan</span>
                <strong>Harian realistis</strong>
            </div>
        </div>
    </aside>

    <main class="app-content" id="main-content">
        <div class="page-shell">
            <div class="container-fluid page-container">
            @if (session('status'))
                <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check2-circle me-1"></i>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                    <div class="fw-semibold mb-1">Ada input yang perlu diperbaiki.</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
            </div>
        </div>
    </main>
</div>

<div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <div>
            <div class="brand-title" id="mobileSidebarLabel">Academy</div>
            <div class="brand-subtitle">Study companion</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-4">
        <nav class="nav flex-column gap-1 sidebar-nav">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2 me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="#text-tools" data-bs-dismiss="offcanvas">
                <i class="bi bi-file-earmark-arrow-up me-2"></i>PDF Rangkuman
            </a>
            <a class="nav-link" href="#flashcards" data-bs-dismiss="offcanvas">
                <i class="bi bi-stack me-2"></i>Flashcards & Kuis
            </a>
            <a class="nav-link" href="#citation" data-bs-dismiss="offcanvas">
                <i class="bi bi-journal-text me-2"></i>Sitasi
            </a>
            <a class="nav-link" href="#planner" data-bs-dismiss="offcanvas">
                <i class="bi bi-calendar2-week me-2"></i>Study Planner
            </a>
            <a class="nav-link" href="#history" data-bs-dismiss="offcanvas">
                <i class="bi bi-clock-history me-2"></i>Riwayat
            </a>
        </nav>

        <div class="sidebar-card">
            <div class="sidebar-heading">Quick stats</div>
            <div class="sidebar-stat">
                <span>AI summary</span>
                <strong>Detail & terarah</strong>
            </div>
            <div class="sidebar-stat">
                <span>Flashcards</span>
                <strong>Interaktif</strong>
            </div>
            <div class="sidebar-stat">
                <span>Study plan</span>
                <strong>Harian realistis</strong>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>