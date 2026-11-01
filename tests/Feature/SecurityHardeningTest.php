<?php

namespace Tests\Feature;

use App\Models\QrLocation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_attached_to_web_responses(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');

        $this->get(route('complaint.scan'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_admin_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'manager@example.test',
            'role' => 'manager',
            'password' => 'correct-password-123',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), [
                'email' => 'manager@example.test',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login.store'), [
            'email' => 'manager@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_ticket_evidence_requires_matching_token_or_authenticated_role(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('work-orders/before/evidence.png', 'private-image');

        $ticket = Ticket::create([
            'reporter_name' => 'Pelapor Aman',
            'reporter_phone' => '081234567890',
            'public_token' => str_repeat('s', 64),
            'ticket_number' => 'AC-SECURE-MEDIA',
            'ticket_type' => 'facility',
            'title' => 'Uji media privat',
            'issue_description' => 'Memastikan bukti pekerjaan tidak menjadi berkas publik.',
            'priority_level' => 'regular',
            'status' => 'pending_verification',
            'target_sla_at' => now()->addDay(),
        ]);
        $attachment = $ticket->attachments()->create([
            'kind' => 'before',
            'file_path' => 'work-orders/before/evidence.png',
            'mime_type' => 'image/png',
            'file_size' => 13,
        ]);
        $otherTicket = Ticket::create([
            'reporter_name' => 'Pelapor Lain',
            'reporter_phone' => '081200000001',
            'public_token' => str_repeat('o', 64),
            'ticket_number' => 'AC-OTHER-MEDIA',
            'ticket_type' => 'facility',
            'title' => 'Tiket lain',
            'issue_description' => 'Token tiket lain tidak boleh membuka media ini.',
            'priority_level' => 'regular',
            'target_sla_at' => now()->addDay(),
        ]);

        $this->get(route('complaint.media', [$ticket->public_token, $attachment]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('complaint.media', [$otherTicket->public_token, $attachment]))
            ->assertNotFound();
        $this->get(route('admin.media.show', $attachment))
            ->assertRedirect(route('login'));

        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->get(route('admin.media.show', $attachment))->assertOk();
    }

    public function test_public_ticket_submission_is_rate_limited(): void
    {
        $location = QrLocation::create([
            'code' => 'SEC-RATE-01',
            'name' => 'Lokasi Uji Rate Limit',
            'type' => 'location',
            'tower_name' => 'Leonie',
            'is_active' => true,
        ]);
        $payload = [
            'reporter_name' => 'Pelapor Rate Limit',
            'reporter_phone' => '081234567890',
            'title' => 'Laporan pengujian keamanan',
            'issue_description' => 'Deskripsi laporan valid untuk pengujian pembatasan permintaan.',
            'priority_level' => 'regular',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->post(route('complaint.store', $location), $payload)->assertRedirectContains('/status/');
        }

        $this->post(route('complaint.store', $location), $payload)->assertTooManyRequests();
    }
}
