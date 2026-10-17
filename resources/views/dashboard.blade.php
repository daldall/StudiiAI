@extends('layouts.app')

@section('content')
{{-- ========================= HERO SECTION ========================= --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xxl-8">
        <div class="hero-card card border-0 h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-5">
                    <div class="flex-grow-1">
                        <div class="text-uppercase fw-bold mb-3" style="font-size:0.7rem;letter-spacing:0.2em;color:var(--color-accent);opacity:0.8">Academic workflow</div>
                        <h1 class="fw-bold mb-3" style="font-size:clamp(1.6rem,3vw,2.4rem);line-height:1.2;color:var(--color-text)">
                            Belajar lebih cerdas<br>
                            <span style="background:var(--grad-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">dengan AI Study Buddy.</span>
                        </h1>
                        <p class="mb-0" style="color:var(--color-text-muted);max-width:44rem;font-size:0.95rem;line-height:1.7">Unggah materi untuk diringkas otomatis, buat flashcards &amp; kuis, generate sitasi, rencanakan jadwal belajar, dan tanya langsung ke Tutor AI — semuanya dalam satu tempat.</p>
                    </div>
                    <div class="status-pill {{ $isConfigured ? 'status-ready' : 'status-warning' }} flex-shrink-0">
                        <span class="status-dot"></span>
                        <span>{{ $isConfigured ? 'Gemini siap' : 'Mode fallback' }}</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-stars"></i></div>
                            <div>
                                <div class="stat-label">AI summary</div>
                                <div class="stat-value">Poin ringkas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-postcard-heart"></i></div>
                            <div>
                                <div class="stat-label">Learning set</div>
                                <div class="stat-value">Flashcards & kuis</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-chat-dots"></i></div>
                            <div>
                                <div class="stat-label">Tutor AI</div>
                                <div class="stat-value">Chat real-time</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="#text-tools" class="btn-primary-soft text-decoration-none">
                        <i class="bi bi-upload"></i> Unggah PDF
                    </a>
                    <button type="button" class="btn-outline-secondary" data-open-chat>
                        <i class="bi bi-chat-dots me-1"></i> Tanya Tutor AI
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-4">
        <div class="card border-0 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1" style="color:var(--color-text)">Alur penggunaan</h2>
                <p class="mb-4" style="font-size:0.84rem;color:var(--color-text-muted)">3 langkah, hasil langsung terasa.</p>
                <div class="workflow-list">
                    <div class="workflow-item">
                        <div class="workflow-step">1</div>
                        <div>
                            <div class="fw-semibold" style="color:var(--color-text)">Unggah materi kuliah</div>
                            <div style="color:var(--color-text-muted);font-size:0.85rem">Ambil PDF atau teks topik dari laptop tanpa menyalin manual.</div>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">2</div>
                        <div>
                            <div class="fw-semibold" style="color:var(--color-text)">Pilih output AI</div>
                            <div style="color:var(--color-text-muted);font-size:0.85rem">Rangkuman, flashcards, kuis, sitasi, atau jadwal belajar.</div>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">3</div>
                        <div>
                            <div class="fw-semibold" style="color:var(--color-text)">Salin atau diskusikan</div>
                            <div style="color:var(--color-text-muted);font-size:0.85rem">Copy output ke clipboard atau lanjutkan diskusi ke Tutor AI.</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3" style="border-top:1px solid var(--color-border);font-size:0.8rem;color:var(--color-text-faint)">
                    <i class="bi bi-shield-check me-1" style="color:var(--color-accent)"></i>Riwayat tersimpan di browser ini.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================= HISTORY ========================= --}}
<div id="history" class="card toolkit-card border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="section-kicker mb-1">History</div>
                <h2 class="h3 fw-bold mb-1" style="color:var(--color-text)">Riwayat hasil</h2>
                <p class="mb-0" style="color:var(--color-text-muted);font-size:0.9rem">Buka ulang output terakhir tanpa menjalankan ulang proses dari awal.</p>
            </div>
            <button type="button" class="btn-outline-secondary" data-history-clear>
                <i class="bi bi-trash3 me-1"></i>Hapus riwayat
            </button>
        </div>
        <div id="history-output" class="history-panel mt-4" data-history-list></div>
    </div>
</div>

{{-- ========================= PDF SUMMARY ========================= --}}
<div id="text-tools" class="card toolkit-card border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="mb-4">
            <div class="section-kicker mb-1">Text cleanup</div>
            <h2 class="h3 fw-bold mb-1" style="color:var(--color-text)">Pembuat rangkuman PDF</h2>
            <p class="mb-0" style="color:var(--color-text-muted);font-size:0.9rem">Unggah file PDF, lalu sistem akan mengekstrak teks dan membuat rangkuman detail otomatis.</p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <form class="tool-form" data-ai-form="text-tools" data-render-target="#text-tools-output" action="{{ route('ai.text-tools') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="pdf_file">File PDF kuliah</label>
                        <input class="form-control clean-input" id="pdf_file" name="pdf_file" type="file" accept="application/pdf,.pdf" aria-describedby="pdf_file_help">
                        <div id="pdf_file_help" class="form-hint mt-2">Pilih file PDF langsung. Jika PDF hasil scan, teksnya mungkin perlu OCR dulu.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn-primary-soft" data-submit-label="Buat Rangkuman">
                            <span class="btn-label">Buat Rangkuman</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="form-hint mt-3"><i class="bi bi-clock-history me-1" style="color:var(--color-accent)"></i>Hasil tersimpan di riwayat dan bisa dibaca ulang kapan saja.</div>
                </form>
            </div>

            <div class="col-12 col-xl-6">
                <div id="text-tools-output" class="result-panel empty-state h-100">
                    <div class="empty-state-inner">
                        <i class="bi bi-layout-text-window-reverse"></i>
                        <h3 class="h5 mb-2">Hasil akan muncul di sini</h3>
                        <p class="text-muted mb-0">Output rangkuman akan tampil tanpa reload halaman.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================= FLASHCARDS & QUIZ ========================= --}}
<div id="flashcards" class="card toolkit-card border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="mb-4">
            <div class="section-kicker mb-1">Active recall</div>
            <h2 class="h3 fw-bold mb-1" style="color:var(--color-text)">Pembuat flashcards & kuis otomatis</h2>
            <p class="mb-0" style="color:var(--color-text-muted);font-size:0.9rem">Ubah topik kuliah menjadi kartu belajar interaktif atau kuis pilihan ganda dengan cek jawaban otomatis.</p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <form class="tool-form" data-ai-form="flashcards" data-render-target="#flashcards-output" action="{{ route('ai.flashcards') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="topic">Topik / materi kuliah</label>
                        <textarea class="form-control clean-input" id="topic" name="topic" rows="8" placeholder="Contoh: Sistem operasi, metode numerik, teori organisasi..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="flashcard_mode">Jenis output</label>
                        <select class="form-select" id="flashcard_mode" name="mode">
                            <option value="flashcards">Kartu belajar (Flashcards)</option>
                            <option value="quiz">Kuis pilihan ganda</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-soft" data-submit-label="Generate Learning Set">
                        <span class="btn-label">Generate Learning Set</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </form>
            </div>

            <div class="col-12 col-xl-7">
                <div id="flashcards-output" class="result-panel empty-state h-100">
                    <div class="empty-state-inner">
                        <i class="bi bi-postcard-heart"></i>
                        <h3 class="h5 mb-2">Flashcards atau kuis akan tampil di sini</h3>
                        <p class="text-muted mb-0">Klik kartu untuk membalik, atau jawab kuis untuk melihat umpan balik otomatis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================= CITATION ========================= --}}
<div class="row g-4 mb-4" id="citation">
    <div class="col-12 col-xl-5">
        <div class="card toolkit-card border-0 h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="section-kicker mb-1">Reference</div>
                <h2 class="h3 fw-bold mb-1" style="color:var(--color-text)">Generator sitasi & referensi</h2>
                <p class="mb-4" style="color:var(--color-text-muted);font-size:0.9rem">Masukkan judul buku, abstrak, atau teks sumber, lalu pilih format yang dibutuhkan.</p>

                <form class="tool-form" data-ai-form="citation" data-render-target="#citation-output" action="{{ route('ai.citation') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="source_text">Teks sumber</label>
                        <textarea class="form-control clean-input" id="source_text" name="source_text" rows="8" placeholder="Tempel informasi buku, artikel, atau abstrak di sini..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="citation_format">Format sitasi</label>
                        <select class="form-select" id="citation_format" name="format">
                            <option value="APA 7th">APA 7th</option>
                            <option value="MLA 9">MLA 9</option>
                            <option value="IEEE">IEEE</option>
                            <option value="Harvard">Harvard</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary-soft" data-submit-label="Buat Sitasi">
                        <span class="btn-label">Buat Sitasi</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div id="citation-output" class="result-panel empty-state h-100">
            <div class="empty-state-inner">
                <i class="bi bi-clipboard-check"></i>
                <h3 class="h5 mb-2">Sitasi siap copy</h3>
                <p class="text-muted mb-0">Hasil sitasi yang rapi akan muncul di panel ini dan dapat disalin langsung.</p>
            </div>
        </div>
    </div>
</div>

{{-- ========================= STUDY PLANNER ========================= --}}
<div id="planner" class="card toolkit-card border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="mb-4">
            <div class="section-kicker mb-1">Planning</div>
            <h2 class="h3 fw-bold mb-1" style="color:var(--color-text)">Perencana jadwal belajar</h2>
            <p class="mb-0" style="color:var(--color-text-muted);font-size:0.9rem">Masukkan daftar tugas atau ujian, tingkat kesulitan, dan estimasi jam belajar harian.</p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <form class="tool-form" data-ai-form="study-plan" data-render-target="#study-plan-output" action="{{ route('ai.study-plan') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="items_text">Daftar tugas / ujian</label>
                        <textarea class="form-control clean-input" id="items_text" name="items_text" rows="8" placeholder="Contoh:&#10;- UAS Basis Data, 20 Nov&#10;- Tugas AI, 24 Nov&#10;- Presentasi statistik, 28 Nov"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="difficulty">Tingkat kesulitan</label>
                            <select class="form-select" id="difficulty" name="difficulty">
                                <option value="Ringan">Ringan</option>
                                <option value="Sedang" selected>Sedang</option>
                                <option value="Berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="daily_hours">Jam belajar per hari</label>
                            <input type="number" class="form-control" id="daily_hours" name="daily_hours" min="1" max="12" value="3">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary-soft mt-3" data-submit-label="Buat Jadwal">
                        <span class="btn-label">Buat Jadwal</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </form>
            </div>

            <div class="col-12 col-xl-7">
                <div id="study-plan-output" class="result-panel empty-state h-100">
                    <div class="empty-state-inner">
                        <i class="bi bi-calendar2-week"></i>
                        <h3 class="h5 mb-2">Rencana belajar akan tampil di sini</h3>
                        <p class="text-muted mb-0">AI akan membagi fokus harian yang lebih realistis berdasarkan deadline dan kesulitan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection