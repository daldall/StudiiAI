@extends('layouts.app')

@section('content')
{{-- ========================= HERO SECTION (PARLO 3D PERSPECTIVE) ========================= --}}
<section class="hero-perspective-wrapper">
    <div class="perspective-grid-layer">
        <div class="perspective-grid-mesh"></div>
    </div>

    <div class="hero-content">
        {{-- Status badge --}}
        <div class="hero-badge-pill">
            <span class="{{ $isConfigured ? 'status-dot-emerald' : 'status-dot-amber' }}"></span>
            <span>{{ $isConfigured ? 'Gemini 3.5 Flash Active' : 'Mode Fallback Aktif' }}</span>
            <span class="text-muted ms-1">• v2.0</span>
        </div>

        {{-- Headline --}}
        <h1 class="hero-headline">
            Built for every subject.<br>
            <span class="text-sub">One autonomous study companion.</span>
        </h1>

        {{-- Subtitle --}}
        <p class="hero-subtitle">
            StudiiAI membaca materi, merangkum poin kritis secara mendalam, menghasilkan flashcards &amp; kuis active recall, menyusun sitasi terstandar, dan mendampingi belajarmu secara otonom.
        </p>

        {{-- Action Buttons --}}
        <div class="hero-cta-group">
            <a href="#text-tools" class="btn-parlo-white text-decoration-none">
                Mulai Rangkum PDF <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <button type="button" class="btn-parlo-dark" data-open-chat>
                <i class="bi bi-chat-dots me-1"></i> Tanya Tutor AI
            </button>
        </div>

        {{-- 4 Value Props (Parlo Bottom Row) --}}
        <div class="hero-value-props">
            <div class="value-prop-item">
                <span class="value-prop-num">01</span>
                <h4 class="value-prop-title">PDF Rangkuman</h4>
                <p class="value-prop-desc">Ekstraksi dokumen kuliah dan rangkuman proporsional tanpa info penting yang tertinggal.</p>
            </div>
            <div class="value-prop-item">
                <span class="value-prop-num">02</span>
                <h4 class="value-prop-title">Active Recall</h4>
                <p class="value-prop-desc">Flashcards flip interaktif dan kuis pilihan ganda dengan umpan balik konsep instan.</p>
            </div>
            <div class="value-prop-item">
                <span class="value-prop-num">03</span>
                <h4 class="value-prop-title">Sitasi Ilmiah</h4>
                <p class="value-prop-desc">Format baku APA 7th, MLA 9, IEEE, dan Harvard siap disalin langsung ke makalah.</p>
            </div>
            <div class="value-prop-item">
                <span class="value-prop-num">04</span>
                <h4 class="value-prop-title">Tutor AI 24/7</h4>
                <p class="value-prop-desc">Asisten belajar responsif dengan persona Kakak Tingkat, Sokratik, dan Dosen.</p>
            </div>
        </div>
    </div>
</section>

{{-- ========================= WORKSPACE TOOLKITS ========================= --}}
<div class="workspace-shell">

    {{-- SECTION 1: PDF RANGKUMAN --}}
    <section id="text-tools" class="mb-5">
        <div class="section-headline-group">
            <span class="section-tag">01 / Text Extraction &amp; Summary</span>
            <h2 class="section-title">Pembuat rangkuman PDF</h2>
            <p class="section-desc">Unggah file PDF materi kuliah, lalu sistem akan mengekstrak teks dan membuat rangkuman detail otomatis.</p>
        </div>

        <div class="parlo-card">
            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <form class="tool-form" data-ai-form="text-tools" data-render-target="#text-tools-output" action="{{ route('ai.text-tools') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label" for="pdf_file">File PDF materi kuliah</label>
                            <input class="form-control clean-input" id="pdf_file" name="pdf_file" type="file" accept="application/pdf,.pdf" aria-describedby="pdf_file_help" required>
                            <div id="pdf_file_help" class="form-hint mt-2">Pilih dokumen PDF langsung. Sistem akan membaca materi dan menyaring struktur bab secara cerdas.</div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" class="btn-primary-soft" data-submit-label="Buat Rangkuman">
                                <span class="btn-label">Buat Rangkuman</span>
                                <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="form-hint mt-3">
                            <i class="bi bi-clock-history me-1"></i>Hasil akan otomatis tercatat di riwayat browser.
                        </div>
                    </form>
                </div>

                <div class="col-12 col-xl-7">
                    <div id="text-tools-output" class="result-panel empty-state">
                        <div class="empty-state-inner">
                            <i class="bi bi-layout-text-window-reverse"></i>
                            <h3>Hasil rangkuman</h3>
                            <p>Pilih file PDF di sebelah kiri dan klik tombol untuk menghasilkan intisari materi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 2: FLASHCARDS & KUIS --}}
    <section id="flashcards" class="mb-5">
        <div class="section-headline-group">
            <span class="section-tag">02 / Active Recall Set</span>
            <h2 class="section-title">Pembuat flashcards &amp; kuis otomatis</h2>
            <p class="section-desc">Ubah topik kuliah menjadi kartu belajar interaktif atau kuis pilihan ganda dengan cek jawaban otomatis.</p>
        </div>

        <div class="parlo-card">
            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <form class="tool-form" data-ai-form="flashcards" data-render-target="#flashcards-output" action="{{ route('ai.flashcards') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="topic">Topik atau materi kuliah</label>
                            <textarea class="form-control clean-input" id="topic" name="topic" rows="7" placeholder="Contoh: Algoritma Sorting, Normalisasi Basis Data, Sistem Saraf Tepi..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="flashcard_mode">Format pembelajaran</label>
                            <select class="form-select" id="flashcard_mode" name="mode">
                                <option value="flashcards" selected>Kartu Belajar (Flashcards Interaktif)</option>
                                <option value="quiz">Kuis Pilihan Ganda (Interactive Quiz)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary-soft" data-submit-label="Generate Learning Set">
                            <span class="btn-label">Generate Learning Set</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

                <div class="col-12 col-xl-7">
                    <div id="flashcards-output" class="result-panel empty-state">
                        <div class="empty-state-inner">
                            <i class="bi bi-postcard-heart"></i>
                            <h3>Interactive Learning Set</h3>
                            <p>Klik kartu flashcard untuk membalik jawaban, atau selesaikan kuis untuk uji pemahaman.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 3: GENERATOR SITASI --}}
    <section id="citation" class="mb-5">
        <div class="section-headline-group">
            <span class="section-tag">03 / Academic Reference</span>
            <h2 class="section-title">Generator sitasi &amp; referensi</h2>
            <p class="section-desc">Masukkan informasi buku, abstrak jurnal, atau teks sumber, lalu pilih standar sitasi yang dibutuhkan.</p>
        </div>

        <div class="parlo-card">
            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <form class="tool-form" data-ai-form="citation" data-render-target="#citation-output" action="{{ route('ai.citation') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="source_text">Informasi atau kutipan sumber</label>
                            <textarea class="form-control clean-input" id="source_text" name="source_text" rows="7" placeholder="Tempel judul buku, penulis, tahun, atau abstrak jurnal di sini..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="citation_format">Standar sitasi</label>
                            <select class="form-select" id="citation_format" name="format">
                                <option value="APA 7th" selected>APA 7th Edition</option>
                                <option value="MLA 9">MLA 9th Edition</option>
                                <option value="IEEE">IEEE Reference</option>
                                <option value="Harvard">Harvard Referencing</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary-soft" data-submit-label="Buat Sitasi">
                            <span class="btn-label">Buat Sitasi</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

                <div class="col-12 col-xl-7">
                    <div id="citation-output" class="result-panel empty-state">
                        <div class="empty-state-inner">
                            <i class="bi bi-journal-check"></i>
                            <h3>Sitasi siap salin</h3>
                            <p>Format sitasi yang rapi dan terstandar akan langsung muncul di panel ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 4: STUDY PLANNER --}}
    <section id="planner" class="mb-5">
        <div class="section-headline-group">
            <span class="section-tag">04 / Autonomous Planning</span>
            <h2 class="section-title">Perencana jadwal belajar</h2>
            <p class="section-desc">Masukkan daftar tugas atau ujian, tingkat kesulitan, dan estimasi jam belajar harian yang diinginkan.</p>
        </div>

        <div class="parlo-card">
            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <form class="tool-form" data-ai-form="study-plan" data-render-target="#study-plan-output" action="{{ route('ai.study-plan') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="items_text">Daftar tugas / ujian</label>
                            <textarea class="form-control clean-input" id="items_text" name="items_text" rows="6" placeholder="Contoh:&#10;- UAS Struktur Data, 22 Nov&#10;- Makalah Jaringan Komputer, 25 Nov&#10;- Presentasi Kecerdasan Buatan, 28 Nov" required></textarea>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label" for="difficulty">Tingkat kesulitan</label>
                                <select class="form-select" id="difficulty" name="difficulty">
                                    <option value="Ringan">Ringan</option>
                                    <option value="Sedang" selected>Sedang</option>
                                    <option value="Berat">Berat</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="daily_hours">Waktu per hari (jam)</label>
                                <input type="number" class="form-control" id="daily_hours" name="daily_hours" min="1" max="12" value="3">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary-soft" data-submit-label="Buat Jadwal">
                            <span class="btn-label">Buat Jadwal</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

                <div class="col-12 col-xl-7">
                    <div id="study-plan-output" class="result-panel empty-state">
                        <div class="empty-state-inner">
                            <i class="bi bi-calendar2-week"></i>
                            <h3>Rencana belajar harian</h3>
                            <p>AI akan membagi alur belajar secara bertahap dan terukur berdasarkan tenggat waktu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 5: RIWAYAT HASIL --}}
    <section id="history" class="mb-4">
        <div class="section-headline-group d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <span class="section-tag">05 / Local Storage History</span>
                <h2 class="section-title">Riwayat hasil</h2>
                <p class="section-desc">Buka ulang output terakhir tanpa menjalankan ulang proses dari awal.</p>
            </div>
            <button type="button" class="btn-outline-secondary" data-history-clear>
                <i class="bi bi-trash3 me-1"></i>Hapus riwayat
            </button>
        </div>

        <div class="parlo-card">
            <div id="history-output" class="history-panel" data-history-list></div>
        </div>
    </section>

</div>
@endsection