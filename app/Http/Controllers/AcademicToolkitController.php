<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Process\Process;

class AcademicToolkitController extends Controller
{
    public function __construct(private readonly GeminiService $gemini)
    {
    }

    public function index(Request $request): View
    {
        return view('dashboard', [
            'isConfigured' => $this->hasApiKey(),
        ]);
    }

    public function textTools(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $pdfFile = $request->file('pdf_file');

        try {
            $text = $this->extractTextFromPdf($pdfFile->getRealPath());
            $summary = $this->gemini->summarizeText($text, $this->apiKey());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Rangkuman berhasil dibuat.',
            'data' => [
                'summary' => $summary,
                'source_name' => $pdfFile->getClientOriginalName(),
            ],
        ]);
    }

    public function flashcards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'min:5'],
            'mode' => ['required', 'in:flashcards,quiz'],
        ]);

        $apiKey = $this->apiKey();

        if ($validated['mode'] === 'quiz') {
            $quiz = $this->gemini->generateQuiz($validated['topic'], $apiKey);

            return response()->json([
                'message' => 'Kuis berhasil dibuat.',
                'data' => [
                    'mode' => 'quiz',
                    'questions' => $quiz,
                ],
            ]);
        }

        $cards = $this->gemini->generateFlashcards($validated['topic'], $apiKey);

        return response()->json([
            'message' => 'Flashcards berhasil dibuat.',
            'data' => [
                'mode' => 'flashcards',
                'cards' => $cards,
            ],
        ]);
    }

    public function citation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_text' => ['required', 'string', 'min:10'],
            'format' => ['required', 'in:APA 7th,MLA 9,IEEE,Harvard'],
        ]);

        $citation = $this->gemini->generateCitation($validated['source_text'], $validated['format'], $this->apiKey());

        return response()->json([
            'message' => 'Sitasi berhasil dibuat.',
            'data' => [
                'format' => $validated['format'],
                'citation' => $citation,
            ],
        ]);
    }

    public function studyPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items_text' => ['required', 'string', 'min:10'],
            'difficulty' => ['required', 'in:Ringan,Sedang,Berat'],
            'daily_hours' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $plan = $this->gemini->generateStudyPlan(
            $validated['items_text'],
            $validated['difficulty'],
            (int) $validated['daily_hours'],
            $this->apiKey()
        );

        return response()->json([
            'message' => 'Jadwal belajar berhasil dibuat.',
            'data' => $plan,
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,model,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'persona' => ['nullable', 'in:tutor,socratic,dosen'],
            'context' => ['nullable', 'string', 'max:20000'],
        ]);

        $history = (array) ($validated['history'] ?? []);
        $history[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        $reply = $this->gemini->chat(
            $history,
            $validated['persona'] ?? 'tutor',
            $validated['context'] ?? null,
            $this->chatApiKey()
        );

        return response()->json([
            'message' => 'Jawaban berhasil dibuat.',
            'data' => [
                'reply' => $reply,
                'persona' => $validated['persona'] ?? 'tutor',
            ],
        ]);
    }

    private function apiKey(): ?string
    {
        $configKey = trim((string) config('services.gemini.api_key', ''));

        return $configKey !== '' ? $configKey : null;
    }

    private function chatApiKey(): ?string
    {
        $chatKey = trim((string) config('services.gemini.chat_api_key', ''));

        if ($chatKey !== '') {
            return $chatKey;
        }

        return $this->apiKey();
    }

    private function hasApiKey(): bool
    {
        return $this->apiKey() !== null;
    }

    private function extractTextFromPdf(string $pdfPath): string
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            '-enc',
            'UTF-8',
            '-q',
            $pdfPath,
            '-',
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Tidak bisa membaca PDF. Pastikan file valid dan tidak dilindungi password.');
        }

        $text = trim($process->getOutput());

        if ($text === '') {
            throw new RuntimeException('Teks PDF kosong. Jika file hasil scan, perlu OCR terlebih dahulu.');
        }

        return $text;
    }
}