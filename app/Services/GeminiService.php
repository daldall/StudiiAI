<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiService
{
    /**
     * Batas karakter materi yang dikirim ke model (cukup besar untuk modul/bab panjang).
     */
    private const MAX_INPUT_CHARS = 120000;

    private const SYSTEM_PERSONA = <<<TXT
Kamu adalah tutor akademik yang sabar, akurat, dan berpengalaman membimbing mahasiswa S1 di Indonesia.
Prinsip kerja:
1. AKURAT: jika input berupa materi/catatan, hanya gunakan informasi dari materi itu. Jangan mengarang fakta, angka, nama, atau rumus. Jika materi tidak cukup, katakan terbatas pada isi materi.
2. TOPIK SINGKAT: jika input hanya berupa nama topik pendek tanpa materi, gunakan pengetahuan akademik umum yang baku dan benar (definisi standar, konsep inti, rumus yang lazim). Jangan mengarang; jika tidak yakin, pilih hal yang paling mapan.
3. MUDAH DIPAHAMI: pakai bahasa Indonesia sehari-hari yang rapi. Istilah teknis tetap ditulis (boleh bahasa Inggris) lalu dijelaskan singkat.
4. LENGKAP & BERGUNA UNTUK BELAJAR/UJIAN: cakup seluruh bagian penting dari awal sampai akhir (jangan hanya bagian awal). Utamakan konsep inti, hubungan sebab-akibat, langkah/proses, rumus, perbandingan, dan contoh konkret.
5. TERSTRUKTUR: urutkan dari gambaran besar ke detail. Tiap poin harus berdiri sendiri dan tidak mengulang poin lain.
6. Jawab HANYA dengan JSON valid sesuai format yang diminta, tanpa markdown atau teks tambahan. Isi semua field yang diminta dengan konten bermakna, jangan dikosongkan.
TXT;

    // ---------------------------------------------------------------------
    // PDF cleaning
    // ---------------------------------------------------------------------

    public function cleanPdfText(string $text): string
    {
        $text = preg_replace("/\r\n?|\r/", "\n", trim($text)) ?? trim($text);

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        $cleanedParagraphs = [];

        foreach ($paragraphs as $paragraph) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', trim($paragraph)) ?: [])));
            $buffer = [];

            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                if (preg_match('/^(?:[-*•]|\d+[.)])\s+/', $line)) {
                    $buffer[] = preg_replace('/\s+/', ' ', $line);
                    continue;
                }

                if ($buffer === []) {
                    $buffer[] = $line;
                    continue;
                }

                $lastIndex = array_key_last($buffer);
                $previous = $buffer[$lastIndex] ?? '';

                if (str_ends_with($previous, '-')) {
                    $buffer[$lastIndex] = substr($previous, 0, -1) . $line;
                    continue;
                }

                $buffer[$lastIndex] = $previous . ' ' . $line;
            }

            $joined = trim(preg_replace('/\s+/', ' ', implode(' ', $buffer)) ?? implode(' ', $buffer));

            if ($joined !== '') {
                $joined = preg_replace('/\s+([,.;:!?])/', '$1', $joined) ?? $joined;
                $cleanedParagraphs[] = $joined;
            }
        }

        return implode("\n\n", $cleanedParagraphs);
    }

    // ---------------------------------------------------------------------
    // Summary
    // ---------------------------------------------------------------------

    public function summarizeText(string $text, ?string $apiKey = null): array
    {
        $preparedText = $this->limitInput($this->prepareSummaryText($text));
        $fallback = $this->fallbackSummary($preparedText);

        if (!$apiKey) {
            return $fallback;
        }

        // Jumlah poin menyesuaikan panjang materi agar materi panjang tidak diringkas terlalu tipis.
        $tier = $this->sizeTier($preparedText, 150, 1200);
        $bulletRange = ['3-5', '6-8', '8-12'][$tier];
        $termRange = ['3-5', '5-8', '8-10'][$tier];
        $questionRange = ['3-4', '4-5', '5-6'][$tier];
        $summarySentences = ['4-6', '6-8', '8-10'][$tier];

        $prompt = <<<PROMPT
Buat rangkuman belajar yang LENGKAP dari materi di bawah untuk mahasiswa yang baru pertama kali membacanya.

Format JSON:
{
  "title": "judul singkat 3-6 kata yang mewakili topik (bukan salinan baris pertama)",
  "plain_summary": "gambaran besar {$summarySentences} kalimat: apa topiknya, kenapa penting, bagaimana alurnya, dan kesimpulan utamanya",
  "bullets": [
    {"point": "inti poin (maks 1 kalimat pendek)", "explanation": "penjelasan 2-4 kalimat: arti, alasan/cara kerja, dan contoh kecil bila membantu"}
  ],
  "key_terms": [
    {"term": "istilah penting", "definition": "definisi singkat 1-2 kalimat"}
  ],
  "review_questions": ["pertanyaan latihan singkat untuk menguji pemahaman"]
}

Aturan:
- Buat {$bulletRange} poin. Urutkan sesuai alur materi dan pastikan SELURUH bagian materi (awal, tengah, akhir) terwakili secara proporsional.
- Tiap poin harus menjawab: apa itu, kenapa/bagaimana, dan (jika ada di materi) contoh atau rumusnya.
- Buat {$termRange} key_terms, hanya istilah yang benar-benar ada di materi.
- Buat {$questionRange} review_questions yang menguji pemahaman (bukan sekadar hafalan judul).
- Pertahankan rumus, angka, nama tokoh, tanggal, dan definisi penting persis seperti di materi.
- Jangan menyalin kalimat panjang dari sumber; jelaskan dengan kata sendiri.
- Variasikan pembuka kalimat, jangan memakai pola yang sama berulang.
- Abaikan nomor halaman, header/footer, dan sisa format PDF.

Materi:
{$preparedText}
PROMPT;

        $response = $this->callGemini($apiKey, $prompt, $this->summarySchema(), 0.3);

        return is_array($response) ? $this->normalizeSummaryResponse($response, $fallback) : $fallback;
    }

    // ---------------------------------------------------------------------
    // Flashcards
    // ---------------------------------------------------------------------

    public function generateFlashcards(string $topic, ?string $apiKey = null): array
    {
        $topic = $this->limitInput($topic);
        $fallback = $this->fallbackFlashcards($topic);

        if (!$apiKey) {
            return $fallback;
        }

        $tier = $this->sizeTier($topic, 40, 600);
        $cardRange = ['8-10', '10-14', '14-20'][$tier];

        $prompt = <<<PROMPT
Buat flashcards LENGKAP untuk belajar dan persiapan ujian dari input berikut.
Input bisa berupa nama topik singkat ATAU materi/catatan panjang. Jika berupa materi, ambil isi kartu dari materi itu saja dan cakup seluruh bagian pentingnya. Jika hanya nama topik, gunakan pengetahuan akademik yang baku dan benar.

Format JSON:
{
  "cards": [
    {"front": "pertanyaan/istilah spesifik", "back": "jawaban ringkas dan lengkap (1-3 kalimat)"}
  ]
}

Aturan:
- Buat {$cardRange} kartu.
- Campurkan jenis kartu: definisi, alasan "mengapa", proses/langkah, perbandingan dua konsep, rumus, dan contoh penerapan.
- Satu kartu = satu ide. Sisi depan harus spesifik dan bisa dijawab tanpa konteks tambahan.
- Sisi belakang berisi jawaban yang bisa langsung dihafal; sertakan alasan singkat atau contoh bila membantu mengingat.
- Jangan membuat kartu duplikat atau pertanyaan yang jawabannya hanya "ya/tidak".
- Bahasa Indonesia natural; istilah teknis boleh tetap dalam bahasa aslinya.

Input:
{$topic}
PROMPT;

        $response = $this->callGemini($apiKey, $prompt, $this->flashcardSchema(), 0.5);
        $cards = $this->normalizeFlashcards($response['cards'] ?? []);

        return $cards !== [] ? $cards : $fallback;
    }

    // ---------------------------------------------------------------------
    // Quiz
    // ---------------------------------------------------------------------

    public function generateQuiz(string $topic, ?string $apiKey = null): array
    {
        $topic = $this->limitInput($topic);
        $fallback = $this->fallbackQuiz($topic);

        if (!$apiKey) {
            return $fallback;
        }

        $tier = $this->sizeTier($topic, 40, 600);
        $questionRange = ['6-8', '8-10', '10-12'][$tier];

        $prompt = <<<PROMPT
Buat kuis pilihan ganda berkualitas ujian kampus dari input berikut.
Input bisa berupa topik singkat ATAU materi panjang. Jika berupa materi, soal harus berdasarkan materi itu dan tersebar merata di seluruh bagiannya. Jika hanya nama topik, gunakan pengetahuan akademik yang baku dan benar.

Format JSON:
{
  "questions": [
    {
      "question": "pertanyaan",
      "options": ["pilihan 1", "pilihan 2", "pilihan 3", "pilihan 4"],
      "answer_index": 0,
      "explanation": "mengapa jawaban benar itu benar dan mengapa pengecoh salah (2-4 kalimat)"
    }
  ]
}

Aturan:
- Buat {$questionRange} soal dengan tingkat berbeda: sekitar 30% mudah (ingatan), 40% sedang (pemahaman), 30% sulit (penerapan/analisis kasus singkat).
- Tepat 4 pilihan yang semuanya berbeda, tepat 1 jawaban benar. answer_index adalah posisi jawaban benar (mulai dari 0). Pengecoh harus masuk akal (kesalahpahaman umum), bukan jawaban konyol.
- Panjang pilihan seragam; jangan pakai "semua benar" atau "semua salah".
- Hindari pertanyaan ambigu dan hindari mengulang konsep yang sama.
- Pilihan ditulis tanpa awalan "A.", "B.", dst.
- Penjelasan harus mengajarkan konsepnya, bukan hanya menyebut jawaban.

Input:
{$topic}
PROMPT;

        $response = $this->callGemini($apiKey, $prompt, $this->quizSchema(), 0.4);
        $questions = $this->normalizeQuiz($response['questions'] ?? []);

        return $questions !== [] ? $questions : $fallback;
    }

    // ---------------------------------------------------------------------
    // Citation
    // ---------------------------------------------------------------------

    public function generateCitation(string $sourceText, string $format, ?string $apiKey = null): string
    {
        $sourceText = $this->limitInput($sourceText, 8000);
        $fallback = $this->fallbackCitation($sourceText, $format);

        if (!$apiKey) {
            return $fallback;
        }

        $prompt = <<<PROMPT
Buat sitasi/daftar pustaka format {$format} dari informasi sumber berikut.

Format JSON:
{
  "citation": "sitasi siap salin"
}

Aturan:
- Ikuti aturan penulisan {$format} edisi terbaru (urutan unsur, tanda baca, huruf miring ditulis dengan *tanda bintang*, kapitalisasi judul, format nama penulis, dan penanganan banyak penulis).
- Kenali jenis sumber (buku, artikel jurnal, situs web, laporan, skripsi, dll.) lalu pakai pola yang sesuai untuk jenis itu.
- JANGAN mengarang penulis, tahun, penerbit, volume, halaman, DOI, atau URL yang tidak ada di sumber.
- Jika ada unsur wajib yang tidak tersedia, tulis placeholder seperti [Tahun], [Penerbit], atau [Nama Penulis] agar mudah dilengkapi.
- Jika sumber berupa URL saja, gunakan hanya info yang bisa disimpulkan dari URL dan beri placeholder untuk sisanya.
- Jika sumber berbahasa Indonesia, gunakan istilah Indonesia untuk kata penghubung (mis. "Diakses dari") bila sesuai dengan format.
- Hanya satu sitasi, tanpa penjelasan tambahan.

Informasi sumber:
{$sourceText}
PROMPT;

        $response = $this->callGemini($apiKey, $prompt, $this->citationSchema(), 0.1);
        $citation = is_array($response) ? trim((string) ($response['citation'] ?? '')) : '';

        return $citation !== '' ? $citation : $fallback;
    }

    // ---------------------------------------------------------------------
    // Study plan
    // ---------------------------------------------------------------------

    public function generateStudyPlan(string $itemsText, string $difficulty, int $dailyHours, ?string $apiKey = null): array
    {
        $itemsText = $this->limitInput($itemsText, 8000);
        $dailyHours = max(1, min(12, $dailyHours));
        $fallback = $this->fallbackStudyPlan($itemsText, $difficulty, $dailyHours);

        if (!$apiKey) {
            return $fallback;
        }

        $today = now()->format('Y-m-d');

        $prompt = <<<PROMPT
Buat jadwal belajar harian yang realistis dan lengkap untuk mahasiswa berdasarkan daftar tugas/ujian berikut.
Tanggal hari ini: {$today}.

Format JSON:
{
  "summary": "ringkasan strategi 2-3 kalimat",
  "days": [
    {
      "day": "Hari 1",
      "focus": "fokus belajar hari itu",
      "tasks": ["tugas konkret yang bisa dicentang"],
      "estimated_hours": 3
    }
  ]
}

Aturan:
- Tingkat kesulitan: {$difficulty}. Waktu belajar: sekitar {$dailyHours} jam per hari (estimated_hours tidak boleh melebihi {$dailyHours}).
- Jika ada tenggat/tanggal ujian di daftar, hitung sisa hari dari tanggal hari ini, susun mundur dari tenggat itu, dan prioritaskan yang lebih dekat atau lebih sulit. Jika tenggat sangat dekat, padatkan jadwal secara realistis.
- Pastikan SEMUA item di daftar mendapat jatah waktu.
- Alur: pahami konsep -> latihan soal -> evaluasi kesalahan -> ulangan terakhir. Sisakan hari terakhir untuk review ringan.
- Tiap tugas harus spesifik dan terukur, dengan estimasi durasi di akhir kalimat (contoh: "Kerjakan 10 soal bab 3 lalu catat yang salah (± 45 menit)"), bukan "belajar bab 3".
- Sertakan jeda/istirahat singkat pada hari yang padat dan teknik belajar yang cocok (active recall, spaced repetition, pomodoro).
- Maksimal 14 hari, 3-5 tugas per hari.

Daftar tugas/ujian:
{$itemsText}
PROMPT;

        $response = $this->callGemini($apiKey, $prompt, $this->studyPlanSchema(), 0.4);
        $plan = $this->normalizeStudyPlan($response, $dailyHours);

        return $plan ?? $fallback;
    }

    // ---------------------------------------------------------------------
    // Gemini transport
    // ---------------------------------------------------------------------

    private function callGemini(string $apiKey, string $prompt, ?array $schema = null, float $temperature = 0.3): ?array
    {
        // Dua percobaan HTTP + pemrosesan bisa melebihi batas default 30 detik.
        @set_time_limit(240);

        $model = (string) (config('services.gemini.model') ?: 'gemini-2.5-flash');

        // thinkingConfig hanya dikirim untuk keluarga 2.5 agar model lain tidak menolak request.
        $supportsThinking = str_contains($model, 'gemini-2.5');

        // Percobaan 2 hanya dipakai bila jawaban terpotong/JSON tidak valid:
        // batas token lebih besar dan thinking dikurangi agar token tersisa untuk jawaban.
        $attempts = [
            ['maxOutputTokens' => 16384, 'thinkingBudget' => 2048],
            ['maxOutputTokens' => 32768, 'thinkingBudget' => 512],
        ];

        foreach ($attempts as $attempt) {
            $generationConfig = [
                'temperature' => $temperature,
                'topP' => 0.9,
                'maxOutputTokens' => $attempt['maxOutputTokens'],
                'responseMimeType' => 'application/json',
            ];

            if ($supportsThinking) {
                $generationConfig['thinkingConfig'] = ['thinkingBudget' => $attempt['thinkingBudget']];
            }

            if ($schema !== null) {
                $generationConfig['responseSchema'] = $schema;
            }

            $payload = [
                'systemInstruction' => [
                    'parts' => [['text' => self::SYSTEM_PERSONA]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ];

            try {
                $response = Http::timeout(75)
                    ->retry(2, 800, function ($exception) {
                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException) {
                            return in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
                        }

                        return false;
                    }, throw: false)
                    ->acceptJson()
                    ->asJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

                if (!$response->successful()) {
                    Log::warning('Gemini request gagal', [
                        'status' => $response->status(),
                        'body' => Str::limit($response->body(), 300),
                    ]);

                    return null;
                }

                $json = $response->json();
                $finishReason = (string) data_get($json, 'candidates.0.finishReason', '');
                $text = $this->extractResponseText($json);
                $decoded = $text !== '' ? $this->decodeJsonResponse($text) : null;

                if ($decoded !== null) {
                    return $decoded;
                }

                Log::warning('Gemini mengembalikan JSON tidak valid atau kosong', [
                    'finishReason' => $finishReason,
                    'attempt_max_tokens' => $attempt['maxOutputTokens'],
                ]);

                // Diblokir oleh filter: mengulang tidak akan membantu.
                if (in_array($finishReason, ['SAFETY', 'RECITATION', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'SPII'], true)) {
                    return null;
                }
            } catch (Throwable $e) {
                Log::warning('Gemini exception: ' . $e->getMessage());

                return null;
            }
        }

        return null;
    }

    /**
     * Gabungkan semua bagian teks jawaban (mengabaikan bagian "thought" bila ada).
     */
    private function extractResponseText(mixed $json): string
    {
        $parts = data_get($json, 'candidates.0.content.parts', []);
        $text = '';

        foreach ((array) $parts as $part) {
            if (!is_array($part) || !empty($part['thought'])) {
                continue;
            }

            $text .= (string) ($part['text'] ?? '');
        }

        return trim($text);
    }

    private function decodeJsonResponse(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Response schemas (memaksa struktur JSON yang konsisten)
    // ---------------------------------------------------------------------

    private function summarySchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'plain_summary' => ['type' => 'STRING'],
                'bullets' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'point' => ['type' => 'STRING'],
                            'explanation' => ['type' => 'STRING'],
                        ],
                        'required' => ['point', 'explanation'],
                    ],
                ],
                'key_terms' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'term' => ['type' => 'STRING'],
                            'definition' => ['type' => 'STRING'],
                        ],
                        'required' => ['term', 'definition'],
                    ],
                ],
                'review_questions' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => ['title', 'plain_summary', 'bullets'],
        ];
    }

    private function flashcardSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'cards' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'front' => ['type' => 'STRING'],
                            'back' => ['type' => 'STRING'],
                        ],
                        'required' => ['front', 'back'],
                    ],
                ],
            ],
            'required' => ['cards'],
        ];
    }

    private function quizSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'questions' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'question' => ['type' => 'STRING'],
                            'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'answer_index' => ['type' => 'INTEGER'],
                            'explanation' => ['type' => 'STRING'],
                        ],
                        'required' => ['question', 'options', 'answer_index', 'explanation'],
                    ],
                ],
            ],
            'required' => ['questions'],
        ];
    }

    private function citationSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => ['citation' => ['type' => 'STRING']],
            'required' => ['citation'],
        ];
    }

    private function studyPlanSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'summary' => ['type' => 'STRING'],
                'days' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'day' => ['type' => 'STRING'],
                            'focus' => ['type' => 'STRING'],
                            'tasks' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'estimated_hours' => ['type' => 'NUMBER'],
                        ],
                        'required' => ['day', 'focus', 'tasks', 'estimated_hours'],
                    ],
                ],
            ],
            'required' => ['summary', 'days'],
        ];
    }

    // ---------------------------------------------------------------------
    // Normalizers / validators
    // ---------------------------------------------------------------------

    private function normalizeSummaryResponse(array $response, array $fallback): array
    {
        $title = trim((string) ($response['title'] ?? $fallback['title'] ?? 'Ringkasan'));
        $plainSummary = trim((string) ($response['plain_summary'] ?? ''));
        $bullets = is_array($response['bullets'] ?? null) ? $response['bullets'] : [];

        $normalized = [];

        foreach ($bullets as $bullet) {
            if (is_array($bullet)) {
                $point = $this->cleanInline((string) ($bullet['point'] ?? ''));
                $explanation = $this->cleanInline((string) ($bullet['explanation'] ?? ''));
            } else {
                $point = $this->cleanInline((string) $bullet);
                $explanation = '';
            }

            if ($point === '') {
                continue;
            }

            $normalized[] = [
                'point' => $point,
                'explanation' => $explanation !== '' ? $explanation : 'Poin ini merupakan bagian inti dari materi.',
            ];

            if (count($normalized) >= 12) {
                break;
            }
        }

        if ($normalized === []) {
            return $fallback;
        }

        $keyTerms = [];
        foreach ((array) ($response['key_terms'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $term = $this->cleanInline((string) ($item['term'] ?? ''));
            $definition = $this->cleanInline((string) ($item['definition'] ?? ''));

            if ($term !== '' && $definition !== '') {
                $keyTerms[] = ['term' => $term, 'definition' => $definition];
            }
        }

        $questions = array_values(array_filter(array_map(
            fn ($q) => $this->cleanInline((string) $q),
            (array) ($response['review_questions'] ?? [])
        )));

        return [
            'title' => $title !== '' ? $title : ($fallback['title'] ?? 'Ringkasan'),
            'plain_summary' => $plainSummary !== '' ? $plainSummary : $this->composePlainSummary($normalized),
            'bullets' => $normalized,
            'key_terms' => array_slice($keyTerms, 0, 10),
            'review_questions' => array_slice($questions, 0, 6),
        ];
    }

    private function normalizeFlashcards(mixed $cards): array
    {
        if (!is_array($cards)) {
            return [];
        }

        $result = [];
        $seen = [];

        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $front = $this->cleanInline((string) ($card['front'] ?? ''));
            $back = $this->cleanInline((string) ($card['back'] ?? ''));
            $key = mb_strtolower($front);

            if ($front === '' || $back === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = ['front' => $front, 'back' => $back];
        }

        return array_slice($result, 0, 20);
    }

    private function normalizeQuiz(mixed $questions): array
    {
        if (!is_array($questions)) {
            return [];
        }

        $result = [];
        $seen = [];

        foreach ($questions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = $this->cleanInline((string) ($item['question'] ?? ''));

            // Bersihkan pilihan TANPA menggeser indeks, supaya answer_index tetap menunjuk jawaban yang benar.
            $rawOptions = array_map(
                fn ($option) => trim(preg_replace('/^[A-Da-d][.)]\s+/', '', $this->cleanInline((string) $option)) ?? ''),
                array_values((array) ($item['options'] ?? []))
            );
            $answerIndex = (int) ($item['answer_index'] ?? -1);

            if ($question === '' || $answerIndex < 0 || !isset($rawOptions[$answerIndex]) || $rawOptions[$answerIndex] === '') {
                continue;
            }

            $questionKey = mb_strtolower($question);
            if (isset($seen[$questionKey])) {
                continue;
            }

            $correct = $rawOptions[$answerIndex];
            $options = array_values(array_unique(array_filter($rawOptions, fn ($option) => $option !== '')));

            if (count($options) < 2) {
                continue;
            }

            // Acak urutan pilihan agar jawaban benar tidak selalu di posisi yang sama.
            shuffle($options);
            $newIndex = array_search($correct, $options, true);

            if ($newIndex === false) {
                continue;
            }

            $explanation = $this->cleanInline((string) ($item['explanation'] ?? ''));

            $seen[$questionKey] = true;
            $result[] = [
                'question' => $question,
                'options' => $options,
                'answer_index' => (int) $newIndex,
                'explanation' => $explanation !== '' ? $explanation : 'Jawaban yang tepat adalah: ' . $correct . '.',
            ];
        }

        return array_slice($result, 0, 12);
    }

    private function normalizeStudyPlan(?array $plan, int $dailyHours): ?array
    {
        if (!is_array($plan) || !is_array($plan['days'] ?? null)) {
            return null;
        }

        $days = [];

        foreach ($plan['days'] as $index => $day) {
            if (!is_array($day)) {
                continue;
            }

            $tasks = array_values(array_filter(array_map(
                fn ($task) => $this->cleanInline((string) $task),
                (array) ($day['tasks'] ?? [])
            )));

            if ($tasks === []) {
                continue;
            }

            $hours = (float) ($day['estimated_hours'] ?? $dailyHours);
            $hours = max(0.5, min((float) $dailyHours, $hours));

            $days[] = [
                'day' => $this->cleanInline((string) ($day['day'] ?? '')) ?: 'Hari ' . ($index + 1),
                'focus' => $this->cleanInline((string) ($day['focus'] ?? '')),
                'tasks' => $tasks,
                'estimated_hours' => $hours == floor($hours) ? (int) $hours : $hours,
            ];
        }

        if ($days === []) {
            return null;
        }

        return [
            'summary' => $this->cleanInline((string) ($plan['summary'] ?? '')) ?: 'Jadwal belajar disusun berdasarkan daftar tugas Anda.',
            'days' => array_slice($days, 0, 14),
        ];
    }

    private function cleanInline(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return preg_replace('/\s+([,.;:!?])/', '$1', $value) ?? $value;
    }

    private function limitInput(string $text, int $max = self::MAX_INPUT_CHARS): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);

        // Potong di batas paragraf/kalimat terdekat agar tidak berhenti di tengah kata.
        $position = max((int) mb_strrpos($cut, "\n\n"), (int) mb_strrpos($cut, '. '));

        if ($position > (int) ($max * 0.9)) {
            $cut = mb_substr($cut, 0, $position + 1);
        }

        return trim($cut);
    }

    /**
     * Tentukan skala input: 0 = pendek, 1 = sedang, 2 = panjang (berdasarkan jumlah kata).
     */
    private function sizeTier(string $text, int $shortMaxWords, int $mediumMaxWords): int
    {
        $words = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        if ($words < $shortMaxWords) {
            return 0;
        }

        return $words < $mediumMaxWords ? 1 : 2;
    }

    // ---------------------------------------------------------------------
    // Text preparation + fallbacks (dipakai saat tanpa API key / API gagal)
    // ---------------------------------------------------------------------

    private function fallbackSummary(string $text): array
    {
        $sentences = $this->extractMeaningfulSentences($text);
        $bullets = [];

        foreach ($sentences as $sentence) {
            if (count($bullets) >= 8) {
                break;
            }

            $bullet = $this->compressSentence($sentence);
            if ($bullet !== '') {
                $bullets[] = $bullet;
            }
        }

        if ($bullets === []) {
            $bullets = [
                ['point' => 'Isi materi belum cukup jelas', 'explanation' => 'Coba tempel teks yang lebih lengkap atau lebih rapi dari PDF.'],
            ];
        } else {
            $templates = [
                'Artinya, bagian ini menjelaskan inti konsep secara langsung supaya pembaca cepat paham.',
                'Maksudnya, poin ini memberi konteks dasar sebelum masuk ke penjelasan yang lebih rinci.',
                'Bagian ini penting karena menjadi dasar untuk memahami contoh, langkah, atau aturan berikutnya.',
                'Contohnya, isi kalimat ini bisa dipakai sebagai pegangan saat membaca ulang materi.',
                'Kalau disederhanakan, poin ini membantu kamu melihat hubungan antarbagian materi dengan lebih jelas.',
            ];

            $bullets = array_map(static fn (string $bullet, int $index): array => [
                'point' => $bullet,
                'explanation' => $templates[$index % count($templates)],
            ], $bullets, array_keys($bullets));
        }

        return [
            'title' => $this->extractTitle($text),
            'plain_summary' => $this->composePlainSummary($bullets),
            'bullets' => array_slice($bullets, 0, 6),
            'key_terms' => [],
            'review_questions' => [],
        ];
    }

    private function prepareSummaryText(string $text): string
    {
        $text = preg_replace("/\r\n?|\r/", "\n", trim($text)) ?? trim($text);
        $lines = preg_split('/\R+/', $text) ?: [];
        $filtered = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:page\s*\d+|hal\.?\s*\d+|\d+\s*$|[-*_]{3,})$/iu', $line)) {
                continue;
            }

            if (preg_match('/^(?:\d{1,2}[\/:.-]\d{1,2}[\/:.-]\d{2,4}|\d{1,3})$/', $line)) {
                continue;
            }

            if (mb_strlen($line) <= 2) {
                continue;
            }

            $filtered[] = $line;
        }

        return implode("\n", $this->deduplicateSequentialLines($filtered));
    }

    private function extractMeaningfulSentences(string $text): array
    {
        $chunks = preg_split('/(?<=[.!?])\s+|\n+/', trim($text)) ?: [];
        $sentences = [];

        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/\s+/', ' ', $chunk) ?? $chunk);

            if ($chunk === '' || mb_strlen($chunk) < 20) {
                continue;
            }

            if ($this->looksLikeHeading($chunk)) {
                continue;
            }

            if ($this->isDuplicateSentence($sentences, $chunk)) {
                continue;
            }

            $sentences[] = $chunk;
        }

        return $sentences;
    }

    private function compressSentence(string $sentence): string
    {
        $sentence = trim(preg_replace('/\s+/', ' ', $sentence) ?? $sentence);
        $sentence = preg_replace('/\s+([,.;:!?])/', '$1', $sentence) ?? $sentence;
        $sentence = preg_replace('/^(?:[-*•]\s*)+/', '', $sentence) ?? $sentence;

        if (mb_strlen($sentence) <= 28) {
            return $sentence;
        }

        $parts = preg_split('/[,;:]/', $sentence) ?: [$sentence];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        $candidate = $parts[0] ?? $sentence;
        if (mb_strlen($candidate) > 220) {
            $candidate = Str::limit($candidate, 180, '');
        }

        return rtrim($candidate, '.');
    }

    private function composePlainSummary(array $bullets): string
    {
        $points = array_values(array_filter(array_map(static function (array $bullet): string {
            $point = trim((string) ($bullet['point'] ?? ''));
            $explanation = trim((string) ($bullet['explanation'] ?? ''));

            if ($point === '') {
                return '';
            }

            return $explanation !== '' ? $point . '. ' . $explanation : $point;
        }, $bullets)));

        if ($points === []) {
            return 'Materi ini belum cukup jelas untuk dirangkum dengan baik.';
        }

        $sentences = [
            $points[0],
            $points[1] ?? 'Setiap bagian penting dijelaskan supaya alurnya mudah diikuti.',
            $points[2] ?? 'Isi materi juga memuat poin-poin yang perlu diingat untuk belajar atau ujian.',
        ];

        if (count($points) > 3) {
            $sentences[] = 'Poin lainnya dirapikan agar lebih mudah dibaca dan dipelajari satu per satu.';
        }

        return implode(' ', $sentences);
    }

    private function extractTitle(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return 'Ringkasan';
        }

        $firstLine = collect(preg_split('/\R+/', $text) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->first();

        if (!is_string($firstLine) || $firstLine === '') {
            return 'Ringkasan';
        }

        $firstLine = preg_replace('/\s+/', ' ', $firstLine) ?? $firstLine;

        if (mb_strlen($firstLine) > 60) {
            $firstLine = Str::limit($firstLine, 60, '');
        }

        return 'Ringkasan ' . $firstLine;
    }

    private function looksLikeHeading(string $text): bool
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        return count($words) <= 4 && !str_contains($text, '.') && !str_contains($text, ',');
    }

    private function deduplicateSequentialLines(array $lines): array
    {
        $result = [];
        $previousNormalized = null;

        foreach ($lines as $line) {
            $normalized = mb_strtolower(trim($line));

            if ($normalized === '' || $normalized === $previousNormalized) {
                continue;
            }

            $result[] = $line;
            $previousNormalized = $normalized;
        }

        return $result;
    }

    private function isDuplicateSentence(array $sentences, string $candidate): bool
    {
        $candidateNormalized = mb_strtolower(trim($candidate));

        foreach ($sentences as $sentence) {
            if (mb_strtolower(trim($sentence)) === $candidateNormalized) {
                return true;
            }
        }

        return false;
    }

    private function fallbackFlashcards(string $topic): array
    {
        $topic = Str::limit(trim(preg_replace('/\s+/', ' ', $topic) ?? $topic), 80, '');

        return [
            [
                'front' => 'Apa definisi inti dari ' . $topic . '?',
                'back' => 'Jelaskan ' . $topic . ' dengan fokus pada konsep utama, tujuan, dan penggunaan praktis.',
            ],
            [
                'front' => 'Apa komponen penting dalam ' . $topic . '?',
                'back' => 'Uraikan elemen, proses, atau variabel yang paling berpengaruh.',
            ],
            [
                'front' => 'Apa contoh penerapan ' . $topic . '?',
                'back' => 'Berikan contoh singkat yang sering muncul di perkuliahan atau studi kasus.',
            ],
            [
                'front' => 'Apa kesalahan umum saat mempelajari ' . $topic . '?',
                'back' => 'Hindari hafalan tanpa memahami alur, konteks, dan istilah kunci.',
            ],
        ];
    }

    private function fallbackQuiz(string $topic): array
    {
        $topic = Str::limit(trim(preg_replace('/\s+/', ' ', $topic) ?? $topic), 80, '');

        return [
            [
                'question' => 'Apa tujuan utama mempelajari ' . $topic . '?',
                'options' => [
                    'Memahami konsep inti dan penerapannya',
                    'Menghafal istilah tanpa konteks',
                    'Menghindari latihan soal',
                    'Fokus pada satu definisi saja',
                ],
                'answer_index' => 0,
                'explanation' => 'Belajar efektif menekankan konsep inti dan penerapan.',
            ],
            [
                'question' => 'Strategi belajar yang paling aman untuk ' . $topic . ' adalah...',
                'options' => [
                    'Meringkas, latihan soal, lalu evaluasi',
                    'Membaca sekali lalu berhenti',
                    'Menghafal tanpa memahami',
                    'Menunda sampai malam ujian',
                ],
                'answer_index' => 0,
                'explanation' => 'Ringkasan dan latihan membuat pemahaman lebih tahan lama.',
            ],
        ];
    }

    private function fallbackCitation(string $sourceText, string $format): string
    {
        $sourceText = trim(Str::of($sourceText)->squish()->toString());

        return sprintf('%s citation draft: %s', $format, Str::limit($sourceText, 180));
    }

    private function fallbackStudyPlan(string $itemsText, string $difficulty, int $dailyHours): array
    {
        $items = array_values(array_filter(array_map('trim', preg_split('/\R+/', trim($itemsText)) ?: [])));

        if ($items === []) {
            $items = ['Tugas belajar utama'];
        }

        $days = [];
        $totalDays = max(4, min(8, count($items) + 2));

        for ($day = 1; $day <= $totalDays; $day++) {
            $focusItem = $items[min($day - 1, count($items) - 1)];
            $days[] = [
                'day' => 'Hari ' . $day,
                'focus' => $difficulty . ' - ' . $focusItem,
                'tasks' => [
                    'Baca ulang materi inti secara perlahan',
                    'Tulis ulang poin penting dengan bahasa sendiri',
                    'Kerjakan latihan atau contoh kecil yang relevan',
                    'Catat bagian yang masih membingungkan untuk ditinjau lagi',
                ],
                'estimated_hours' => $dailyHours,
            ];
        }

        return [
            'summary' => 'Jadwal fallback disusun berdasarkan daftar tugas yang tersedia.',
            'days' => $days,
        ];
    }
}