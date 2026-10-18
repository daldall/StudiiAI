<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'StudiiAI — Autonomous Academic Study Companion' }}</title>
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

{{-- ========================= PARLO TOPBAR ========================= --}}
<header class="app-topbar">
    <div class="topbar-container">
        {{-- Brand / Logo --}}
        <a class="topbar-brand" href="{{ route('dashboard') }}">
            <div class="brand-icon-parlo">
                <i class="bi bi-slash-lg" style="transform: rotate(45deg); font-size: 0.9rem;"></i>
            </div>
            <span class="brand-name">StudiiAI</span>
        </a>

        {{-- Center Navigation Links --}}
        <nav class="d-none d-lg-flex">
            <ul class="topbar-nav">
                <li><a class="topbar-link" href="#text-tools">PDF Rangkuman</a></li>
                <li><a class="topbar-link" href="#flashcards">Flashcards & Kuis</a></li>
                <li><a class="topbar-link" href="#citation">Sitasi</a></li>
                <li><a class="topbar-link" href="#planner">Study Planner</a></li>
                <li><a class="topbar-link" href="#history">Riwayat</a></li>
            </ul>
        </nav>

        {{-- Right Side Actions --}}
        <div class="topbar-actions">
            <button type="button" class="btn-parlo-dark d-none d-sm-inline-flex" data-open-chat title="Buka Tutor AI">
                <span class="status-dot-emerald me-1"></span>
                Tutor AI
            </button>
            <a href="#text-tools" class="btn-parlo-white">
                Mulai Belajar <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <button class="btn-icon-topbar d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>
    </div>
</header>

{{-- ========================= MAIN PAGE CONTENT ========================= --}}
<main id="main-content">
    @if (session('status'))
        <div class="topbar-container pt-3">
            <div class="alert alert-success border-0 mb-0 py-2 px-3 small" role="alert" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25) !important; color: #34d399; border-radius: 999px;">
                <i class="bi bi-check2-circle me-1"></i>{{ session('status') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="topbar-container pt-3">
            <div class="alert alert-danger border-0 mb-0 py-2 px-3 small" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25) !important; color: #f87171; border-radius: 12px;">
                <div class="fw-semibold mb-1">Ada input yang perlu diperbaiki:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

{{-- ========================= MOBILE OFFCANVAS ========================= --}}
<div class="offcanvas offcanvas-start parlo-offcanvas" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header p-3">
        <div class="d-flex align-items-center gap-2" id="mobileNavLabel">
            <div class="brand-icon-parlo">
                <i class="bi bi-slash-lg" style="transform: rotate(45deg); font-size: 0.9rem;"></i>
            </div>
            <span class="brand-name">StudiiAI</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-2 p-3">
        <a class="mobile-nav-link" href="#text-tools" data-bs-dismiss="offcanvas">
            <i class="bi bi-file-earmark-arrow-up me-2 text-muted"></i>PDF Rangkuman
        </a>
        <a class="mobile-nav-link" href="#flashcards" data-bs-dismiss="offcanvas">
            <i class="bi bi-stack me-2 text-muted"></i>Flashcards & Kuis
        </a>
        <a class="mobile-nav-link" href="#citation" data-bs-dismiss="offcanvas">
            <i class="bi bi-journal-text me-2 text-muted"></i>Sitasi
        </a>
        <a class="mobile-nav-link" href="#planner" data-bs-dismiss="offcanvas">
            <i class="bi bi-calendar2-week me-2 text-muted"></i>Study Planner
        </a>
        <a class="mobile-nav-link" href="#history" data-bs-dismiss="offcanvas">
            <i class="bi bi-clock-history me-2 text-muted"></i>Riwayat
        </a>
        <a class="mobile-nav-link" href="#ai-chat-drawer" data-bs-dismiss="offcanvas" data-open-chat>
            <i class="bi bi-chat-dots me-2 text-muted"></i>Tutor AI
        </a>
        <div class="mt-auto pt-3 border-top" style="border-color: rgba(255,255,255,0.08) !important;">
            <div class="small text-muted mb-2">Academic Study Companion</div>
            <button type="button" class="btn-parlo-white w-100" data-open-chat data-bs-dismiss="offcanvas">
                Tanya Tutor AI
            </button>
        </div>
    </div>
</div>

{{-- ========================= FLOATING AI TUTOR CHATBOT ========================= --}}
<div id="ai-chat-widget" class="ai-chat-widget">
    <!-- Floating Launcher Button -->
    <button type="button" class="chat-launcher-btn" data-toggle-chat aria-label="Buka Chat Tutor AI">
        <i class="bi bi-chat-dots-fill"></i>
        <span>Tutor AI</span>
        <span class="chat-launcher-dot"></span>
    </button>

    <!-- Floating Chat Window / Drawer -->
    <div id="ai-chat-drawer" class="chat-drawer d-none" aria-hidden="true">
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
                <button type="button" class="btn-icon" data-chat-clear title="Hapus riwayat chat">
                    <i class="bi bi-trash3"></i>
                </button>
                <button type="button" class="btn-icon" data-chat-close title="Tutup">
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
            <small class="text-muted ms-2" style="font-size:0.75rem">Tutor sedang berpikir...</small>
        </div>

        <!-- Input Box -->
        <form class="chat-input-form" data-chat-form action="{{ route('ai.chat') }}">
            <textarea class="form-control chat-input" data-chat-input rows="1" placeholder="Tanyakan apa saja seputar pelajaranmu..." required></textarea>
            <button type="submit" class="chat-send-btn" data-chat-send aria-label="Kirim pesan">
                <i class="bi bi-send-fill" style="font-size:0.85rem"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>