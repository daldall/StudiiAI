<?php

namespace Tests\Feature;

use Tests\TestCase;

class AcademicToolkitTest extends TestCase
{
    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Riwayat hasil');
        $response->assertDontSee('Bersihkan Format PDF');
        $response->assertDontSee('Lihat teks asli yang sudah dibersihkan');
        $response->assertDontSee('Pengaturan API Key');
    }

    public function test_settings_route_is_removed(): void
    {
        $response = $this->get('/settings');

        $response->assertStatus(404);
    }

    public function test_flashcards_generation(): void
    {
        $response = $this->postJson('/ai/flashcards', [
            'topic' => 'Kecerdasan Buatan dan Jaringan Saraf',
            'mode' => 'flashcards',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'cards',
                'mode',
            ],
        ]);
    }

    public function test_quiz_generation(): void
    {
        $response = $this->postJson('/ai/flashcards', [
            'topic' => 'Sistem Operasi dan Manajemen Memori',
            'mode' => 'quiz',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'questions',
                'mode',
            ],
        ]);
    }

    public function test_citation_generation(): void
    {
        $response = $this->postJson('/ai/citation', [
            'source_text' => 'Pressman, R. S. (2014). Software Engineering: A Practitioner Approach. McGraw-Hill.',
            'format' => 'APA 7th',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'citation',
                'format',
            ],
        ]);
    }

    public function test_study_plan_generation(): void
    {
        $response = $this->postJson('/ai/study-plan', [
            'items_text' => "Belajar ERD\nNormalisasi Database\nQuery SQL Lanjut",
            'difficulty' => 'Sedang',
            'daily_hours' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'summary',
                'days',
            ],
        ]);
    }
}

