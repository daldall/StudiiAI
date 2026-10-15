@extends('layouts.app')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 col-xxl-8">
        <div class="hero-card card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold letter-spacing">Academic workflow</div>
                        <h1 class="display-6 fw-semibold mb-2">Dashboard akademik yang tenang, cepat, dan siap pakai.</h1>
                        <p class="text-muted mb-0 max-w-prose">Unggah materi untuk diringkas, lalu lanjutkan ke flashcards, kuis, sitasi, jadwal belajar, dan riwayat hasil yang bisa dibuka ulang.</p>
                    </div>
                    <div class="status-pill {{ $isConfigured ? 'status-ready' : 'status-warning' }}">
                        <span class="status-dot"></span>
                        <span>{{ $isConfigured ? 'Gemini siap digunakan' : 'Mode fallback aktif' }}</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-stars"></i></div>
                            <div>
                                <div class="stat-label">AI summary</div>
                                <div class="stat-value">Poin ringkas</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-postcard-heart"></i></div>
                            <div>
                                <div class="stat-label">Learning set</div>
                                <div class="stat-value">Flashcards & kuis</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <div class="stat-label">Riwayat</div>
                                <div class="stat-value">Bisa dibaca ulang</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 small text-muted">Riwayat hasil disimpan di browser ini dan bisa dibuka ulang kapan saja.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold mb-3">Alur penggunaan</h2>
                <div class="workflow-list">
                    <div class="workflow-item">
                        <div class="workflow-step">1</div>
                        <div>
                            <div class="fw-semibold">Unggah materi kuliah</div>
                            <div class="text-muted small">Ambil PDF atau teks topik dari laptop tanpa menyalin manual.</div>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">2</div>
                        <div>
                            <div class="fw-semibold">Pilih output</div>
                            <div class="text-muted small">Rangkuman, flashcards, kuis, sitasi, atau jadwal belajar.</div>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">3</div>
                        <div>
                            <div class="fw-semibold">Salin hasilnya</div>
                            <div class="text-muted small">Setiap output bisa di-copy langsung ke clipboard.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="history" class="card toolkit-card border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="section-kicker">History</div>
                <h2 class="h3 fw-semibold mb-2">Riwayat hasil</h2>
                <p class="text-muted mb-0">Buka ulang output terakhir tanpa menjalankan ulang proses dari awal.</p>
            </div>
            <button type="button" class="btn btn-outline-secondary" data-history-clear>Hapus riwayat</button>
        </div>

        <div id="history-output" class="history-panel mt-4" data-history-list></div>
    </div>
</div>

<div id="text-tools" class="card toolkit-card border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="section-kicker">Text cleanup</div>
                <h2 class="h3 fw-semibold mb-2">Pembuat rangkuman PDF</h2>
                <p class="text-muted mb-0">Unggah file PDF, lalu sistem akan mengekstrak teks dan membuat rangkuman detail otomatis.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <form class="tool-form" data-ai-form="text-tools" data-render-target="#text-tools-output" action="{{ route('ai.text-tools') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="pdf_file">File PDF kuliah</label>
                        <input class="form-control clean-input" id="pdf_file" name="pdf_file" type="file" accept="application/pdf,.pdf" aria-describedby="pdf_file_help">
                        <div id="pdf_file_help" class="form-hint mt-2">Pilih file PDF langsung. Kalau PDF hasil scan, teksnya mungkin perlu OCR dulu.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary-soft" data-submit-label="Buat Rangkuman">
                            <span class="btn-label">Buat Rangkuman</span>
                            <span class="btn-spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="form-hint mt-3">Hasil rangkuman akan tersimpan di riwayat sehingga bisa dibaca ulang kapan saja.</div>
                </form>
            </div>

            <div class="col-12 col-xl-6">
                <div id="text-tools-output" class="result-panel empty-state h-100">
                    <div class="empty-state-inner">
                        <i class="bi bi-layout-text-window-reverse"></i>
                        <h3 class="h5 mb-2">Hasil akan muncul di sini</h3>
                        <p class="text-muted mb-0">Output pembersihan dan rangkuman akan tampil tanpa reload halaman.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="flashcards" class="card toolkit-card border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="section-kicker">Active recall</div>
                <h2 class="h3 fw-semibold mb-2">Pembuat flashcards & kuis otomatis</h2>
                <p class="text-muted mb-0">Ubah topik kuliah menjadi kartu belajar interaktif atau kuis pilihan ganda dengan cek jawaban otomatis.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <form class="tool-form" data-ai-form="flashcards" data-render-target="#flashcards-output" action="{{ route('ai.flashcards') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="topic">Topik / materi kuliah</label>
                        <textarea class="form-control clean-input" id="topic" name="topic" rows="8" placeholder="Contoh: Sistem operasi, metode numerik, teori organisasi..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="flashcard_mode">Jenis output</label>
                        <select class="form-select" id="flashcard_mode" name="mode">
                            <option value="flashcards">Kartu belajar (Flashcards)</option>
                            <option value="quiz">Kuis pilihan ganda</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary-soft" data-submit-label="Generate Learning Set">
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

<div class="row g-4 mb-4" id="citation">
    <div class="col-12 col-xl-5">
        <div class="card toolkit-card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="section-kicker">Reference</div>
                <h2 class="h3 fw-semibold mb-2">Generator sitasi & referensi</h2>
                <p class="text-muted mb-4">Masukkan judul buku, abstrak, atau teks sumber, lalu pilih format yang dibutuhkan.</p>

                <form class="tool-form" data-ai-form="citation" data-render-target="#citation-output" action="{{ route('ai.citation') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="source_text">Teks sumber</label>
                        <textarea class="form-control clean-input" id="source_text" name="source_text" rows="8" placeholder="Tempel informasi buku, artikel, atau abstrak di sini..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="citation_format">Format sitasi</label>
                        <select class="form-select" id="citation_format" name="format">
                            <option value="APA 7th">APA 7th</option>
                            <option value="MLA 9">MLA 9</option>
                            <option value="IEEE">IEEE</option>
                            <option value="Harvard">Harvard</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary-soft" data-submit-label="Buat Sitasi">
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

<div id="planner" class="card toolkit-card border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="section-kicker">Planning</div>
                <h2 class="h3 fw-semibold mb-2">Perencana jadwal belajar</h2>
                <p class="text-muted mb-0">Masukkan daftar tugas atau ujian, tingkat kesulitan, dan estimasi jam belajar harian.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <form class="tool-form" data-ai-form="study-plan" data-render-target="#study-plan-output" action="{{ route('ai.study-plan') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="items_text">Daftar tugas / ujian</label>
                        <textarea class="form-control clean-input" id="items_text" name="items_text" rows="8" placeholder="Contoh:\n- UAS Basis Data, 20 Nov\n- Tugas AI, 24 Nov\n- Presentasi statistik, 28 Nov"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="difficulty">Tingkat kesulitan</label>
                            <select class="form-select" id="difficulty" name="difficulty">
                                <option value="Ringan">Ringan</option>
                                <option value="Sedang" selected>Sedang</option>
                                <option value="Berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="daily_hours">Jam belajar per hari</label>
                            <input type="number" class="form-control" id="daily_hours" name="daily_hours" min="1" max="12" value="3">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-soft mt-3" data-submit-label="Buat Jadwal">
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