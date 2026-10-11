<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            'apiKeySource' => $this->apiKeySource($request),
            'isConfigured' => $this->hasApiKey($request),
        ]);
    }

    public function settings(Request $request): View
    {
        return view('settings', [
            'currentKey' => $request->session()->get('gemini_api_key', ''),
            'apiKeySource' => $this->apiKeySource($request),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $key = trim((string) ($validated['gemini_api_key'] ?? ''));

        if ($key === '') {
            $request->session()->forget('gemini_api_key');

            return redirect()->route('settings')->with('status', 'API key sesi dihapus.');
        }

        $request->session()->put('gemini_api_key', $key);

        return redirect()->route('settings')->with('status', 'API key tersimpan untuk sesi browser ini.');
    }

    public function clearSettings(Request $request): RedirectResponse
    {
        $request->session()->forget('gemini_api_key');

        return redirect()->route('settings')->with('status', 'API key berhasil dihapus.');
    }

    public function textTools(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'mode' => ['required', 'in:clean,summary'],
        ]);

        try {
            $pdfFile = $request->file('pdf_file');
            $extractedText = $this->extractTextFromPdf($pdfFile->getRealPath());
            $cleanedText = $this->gemini->cleanPdfText($extractedText);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($validated['mode'] === 'clean') {
            return response()->json([
                'message' => 'Teks berhasil dibersihkan.',
                'data' => [
                    'cleaned_text' => $cleanedText,
                    'source_name' => $pdfFile->getClientOriginalName(),
                ],
            ]);
        }

        $summary = $this->gemini->summarizeText($cleanedText, $this->apiKey($request));

        return response()->json([
            'message' => 'Rangkuman berhasil dibuat.',
            'data' => [
                'cleaned_text' => $cleanedText,
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

        $apiKey = $this->apiKey($request);

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

        $citation = $this->gemini->generateCitation($validated['source_text'], $validated['format'], $this->apiKey($request));

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
            $this->apiKey($request)
        );

        return response()->json([
            'message' => 'Jadwal belajar berhasil dibuat.',
            'data' => $plan,
        ]);
    }

    private function apiKey(Request $request): ?string
    {
        $sessionKey = trim((string) $request->session()->get('gemini_api_key', ''));

        if ($sessionKey !== '') {
            return $sessionKey;
        }

        $configKey = trim((string) config('services.gemini.api_key', ''));

        return $configKey !== '' ? $configKey : null;
    }

    private function hasApiKey(Request $request): bool
    {
        return $this->apiKey($request) !== null;
    }

    private function apiKeySource(Request $request): string
    {
        if (trim((string) $request->session()->get('gemini_api_key', '')) !== '') {
            return 'session';
        }

        if (trim((string) config('services.gemini.api_key', '')) !== '') {
            return 'env';
        }

        return 'none';
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