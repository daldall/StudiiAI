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

<header class="app-header">
    <div class="app-header-inner">
        <div class="d-flex align-items-center gap-3">
            <button class="icon-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation">
                <i class="bi bi-list"></i>
            </button>

            <a class="brand-link" href="{{ route('dashboard') }}">
                <div class="brand-mark">S</div>
                <div>
                    <div class="brand-title">StudiiAI</div>
                    <div class="brand-subtitle">Study companion</div>
                </div>
            </a>
        </div>

        <div class="header-search d-none d-md-flex" role="search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" placeholder="Cari tools, riwayat, atau rangkuman..." aria-label="Search tools">
        </div>

        <div class="d-flex align-items-center gap-2">
            <a class="icon-button" href="#history" aria-label="Open history" title="Riwayat">
                <i class="bi bi-clock-history"></i>
            </a>
            <button type="button" class="icon-button" data-toggle-chat title="Tutor AI">
                <i class="bi bi-chat-dots"></i>
            </button>
            <div class="profile-chip">
                <span class="profile-dot"></span>
                <span class="d-none d-sm-inline">Mahasiswa</span>
            </div>
        </div>
    </div>
</header>

<div class="app-shell">
    <aside class="app-sidebar d-none d-lg-flex">
        <div class="sidebar-section">
            <div class="sidebar-heading">Menu utama</div>
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
                <a class="nav-link" href="#ai-chat-drawer" data-open-chat>
                    <i class="bi bi-chat-dots me-2"></i>Tutor AI
                </a>
            </nav>
        </div>

        <div class="sidebar-card">
            <div class="sidebar-heading">Fitur tersedia</div>
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
            <div class="brand-title" id="mobileSidebarLabel">StudiiAI</div>
            <div class="brand-subtitle">Study companion</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
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
            <a class="nav-link" href="#ai-chat-drawer" data-bs-dismiss="offcanvas" data-open-chat>
                <i class="bi bi-chat-dots me-2"></i>Tutor AI
            </a>
        </nav>

        <div class="sidebar-card">
            <div class="sidebar-heading">Fitur tersedia</div>
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

<!-- Floating AI Tutor Chatbot -->
<div id="ai-chat-widget" class="ai-chat-widget">
    <!-- Floating Launcher Button -->
    <button type="button" class="chat-launcher-btn shadow" data-toggle-chat aria-label="Buka Chat Tutor AI">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="chat-launcher-text">Tutor AI</span>
        <span class="chat-launcher-dot"></span>
    </button>

    <!-- Floating Chat Window / Drawer -->
    <div id="ai-chat-drawer" class="chat-drawer shadow-lg d-none" aria-hidden="true">
        <!-- Header -->
        <div class="chat-drawer-header">
            <div class="d-flex align-items-center gap-2">
                <div class="chat-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <div class="chat-title">Tutor AI (Study Buddy)</div>
                    <div class="chat-subtitle"><span class="chat-online-dot"></span> Siap membantumu belajar</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-icon text-muted" data-chat-clear title="Hapus riwayat chat">
                    <i class="bi bi-trash3"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-muted" data-chat-close title="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Persona & Context Bar -->
        <div class="chat-toolbar">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-1 w-100">
                    <span class="chat-toolbar-label">Gaya:</span>
                    <select class="form-select form-select-sm chat-persona-select" data-chat-persona>
                        <option value="tutor" selected>Kakak Tingkat (Ramah & Santai)</option>
                        <option value="socratic">Tutor Sokratik (Pemandu Bertahap)</option>
                        <option value="dosen">Dosen Pembimbing (Kritis & Formal)</option>
                    </select>
                </div>
            </div>
            <div id="chat-context-badge" class="chat-context-badge d-none mt-2">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-truncate small"><i class="bi bi-paperclip me-1"></i><span class="chat-context-title">Materi aktif</span></span>
                    <button type="button" class="btn-close-context" data-chat-remove-context title="Lepas konteks">&times;</button>
                </div>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div class="chat-messages" data-chat-messages>
            <!-- Default Welcome Message -->
            <div class="chat-bubble chat-bubble-bot">
                <div class="chat-bubble-content">
                    Halo! Aku <strong>Tutor AI</strong> siap nemenin kamu belajar atau ngerjain tugas. Mau diskusi materi apa hari ini?
                </div>
                <div class="chat-prompt-suggestions mt-2">
                    <button type="button" class="chat-prompt-chip" data-chat-suggest="Jelaskan materi sulit ini dengan analogi kehidupan sehari-hari yang gampang dibayangin: ">
                        💡 Jelaskan dengan analogi
                    </button>
                    <button type="button" class="chat-prompt-chip" data-chat-suggest="Bantu saya membuat kerangka (outline) tugas/makalah tentang: ">
                        📝 Buat kerangka tugas
                    </button>
                    <button type="button" class="chat-prompt-chip" data-chat-suggest="Coba berikan saya 3 pertanyaan latihan untuk menguji pemahaman saya tentang: ">
                        ❓ Uji pemahaman materi
                    </button>
                    <button type="button" class="chat-prompt-chip" data-chat-suggest="Beri contoh studi kasus nyata dalam perkuliahan atau industri untuk topik: ">
                        🎯 Contoh kasus nyata
                    </button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div class="chat-typing d-none" data-chat-typing>
            <span></span><span></span><span></span>
            <small class="text-muted ms-2">Tutor sedang berpikir...</small>
        </div>

        <!-- Input Box -->
        <form class="chat-input-form" data-chat-form action="{{ route('ai.chat') }}">
            <textarea class="form-control chat-input" data-chat-input rows="1" placeholder="Tanyakan apa saja seputar pelajaranmu..." required></textarea>
            <button type="submit" class="btn btn-primary-soft chat-send-btn" data-chat-send aria-label="Kirim pesan">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>