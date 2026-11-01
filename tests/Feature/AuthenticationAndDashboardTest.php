<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappStatusNotification;
use App\Models\QrLocation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketInternalNotifier;
use App\Services\WhatsappNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_uses_brand_assets_and_links_to_qr_scanner(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Lapor kerusakan cukup')
            ->assertSee('Scan QR sekarang')
            ->assertSee(route('complaint.scan'), false)
            ->assertSee('images/brand/apartcare-logo-horizontal.svg', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('images/brand/apartcare-logo-utama.png', false)
            ->assertDontSee('Minta demo');
    }

    public function test_manager_is_sent_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'password' => 'password']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_resident_account_is_rejected_from_admin_portal(): void
    {
        $user = User::factory()->create(['role' => 'resident', 'password' => 'password']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_resident_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'resident']);
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_qr_opens_public_report_form_without_login(): void
    {
        $location = QrLocation::create([
            'code' => 'LEO-LIFT-A',
            'name' => 'Lift Penumpang A',
            'type' => 'facility',
            'tower_name' => 'Leonie',
            'floor_number' => 1,
            'is_active' => true,
        ]);

        $this->get(route('complaint.create', $location))
            ->assertOk()
            ->assertSee('Lift Penumpang A')
            ->assertSee('tidak perlu masuk');
    }

    public function test_scan_page_offers_camera_and_manual_code(): void
    {
        $this->get(route('complaint.scan'))
            ->assertOk()
            ->assertSee('Nyalakan kamera')
            ->assertSee('Masukkan kode label')
            ->assertSee('html5-qrcode.min.js');
    }

    public function test_manual_code_redirects_to_the_matching_report_form(): void
    {
        $location = QrLocation::create([
            'code' => 'LEO-COR-12E',
            'name' => 'Koridor Timur Lantai 12',
            'type' => 'location',
            'tower_name' => 'Leonie',
            'floor_number' => 12,
            'is_active' => true,
        ]);

        $this->post(route('complaint.code'), ['location_code' => 'leo-cor-12e'])
            ->assertRedirect(route('complaint.create', $location));
    }

    public function test_guest_can_submit_ticket_from_qr_location(): void
    {
        $location = QrLocation::create([
            'code' => 'LEO-PUMP-01',
            'name' => 'Pompa Booster 01',
            'type' => 'asset',
            'tower_name' => 'Leonie',
            'floor_number' => 1,
            'is_active' => true,
        ]);

        $response = $this->post(route('complaint.store', $location), [
            'reporter_name' => 'Budi Santoso',
            'reporter_phone' => '081234567890',
            'title' => 'Tekanan air menurun',
            'issue_description' => 'Tekanan air shower menurun sejak pagi hari.',
            'priority_level' => 'regular',
        ]);

        $response->assertRedirectContains('/status/');

        $this->assertDatabaseHas('tickets', [
            'reporter_user_id' => null,
            'qr_location_id' => $location->id,
            'reporter_name' => 'Budi Santoso',
            'title' => 'Tekanan air menurun',
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('ticket_status_logs', [
            'to_status' => 'submitted',
        ]);
    }

    public function test_whatsapp_notification_is_logged_when_integration_is_disabled(): void
    {
        config(['services.whatsapp.enabled' => false]);
        $location = QrLocation::create([
            'code' => 'WA-TEST-01',
            'name' => 'Lift Uji WhatsApp',
            'type' => 'facility',
            'tower_name' => 'Leonie',
            'is_active' => true,
        ]);
        $ticket = Ticket::create([
            'qr_location_id' => $location->id,
            'reporter_name' => 'Pelapor WhatsApp',
            'reporter_phone' => '081234567890',
            'public_token' => str_repeat('w', 64),
            'ticket_number' => 'AC-WA-TEST',
            'ticket_type' => 'facility',
            'title' => 'Lift',
            'issue_description' => 'Pengujian antrean notifikasi WhatsApp.',
            'priority_level' => 'regular',
            'target_sla_at' => now()->addDay(),
        ]);

        (new SendWhatsappStatusNotification($ticket->id, 'created'))->handle(app(WhatsappNotifier::class));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'ticket_id' => $ticket->id,
            'event' => 'created',
            'status' => 'skipped',
        ]);
    }

    public function test_admin_ticket_routes_use_uuid_instead_of_numeric_id(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $ticket = Ticket::create([
            'reporter_name' => 'Pelapor UUID',
            'reporter_phone' => '081234567890',
            'public_token' => str_repeat('u', 64),
            'ticket_number' => 'AC-UUID-TEST',
            'ticket_type' => 'facility',
            'title' => 'Uji alamat tiket',
            'issue_description' => 'Memastikan alamat admin tidak mengekspos ID berurutan.',
            'priority_level' => 'regular',
            'status' => 'submitted',
            'target_sla_at' => now()->addDay(),
        ]);

        $url = route('admin.tickets.show', $ticket);

        $this->assertNotEmpty($ticket->uuid);
        $this->assertStringEndsWith('/admin/tickets/'.$ticket->uuid, $url);
        $this->assertNotSame((string) $ticket->id, basename(parse_url($url, PHP_URL_PATH)));
        $this->actingAs($manager)->get($url)->assertOk();
    }

    public function test_whatsapp_message_uses_readable_status_and_bold_labels(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.url' => 'http://whatsapp.test',
            'services.whatsapp.token' => 'test-token',
        ]);
        Http::fake(['http://whatsapp.test/send' => Http::response(['sent' => true], 200)]);
        $ticket = Ticket::create([
            'reporter_name' => 'Pelapor WhatsApp',
            'reporter_phone' => '087731131703',
            'public_token' => str_repeat('m', 64),
            'ticket_number' => 'AC-MESSAGE-TEST',
            'ticket_type' => 'facility',
            'title' => 'Lift',
            'issue_description' => 'Pengujian isi pesan WhatsApp.',
            'priority_level' => 'high',
            'status' => 'pending_verification',
            'target_sla_at' => now()->addDay(),
        ]);

        app(WhatsappNotifier::class)->send($ticket, 'completed');

        Http::assertSent(function ($request): bool {
            $message = $request['message'];

            return str_contains($message, '*Perbaikan sudah selesai*')
                && str_contains($message, '*Nomor tiket:* AC-MESSAGE-TEST')
                && str_contains($message, '*Status saat ini:* Menunggu konfirmasi Anda')
                && ! str_contains($message, 'pending_verification');
        });
    }

    public function test_manager_can_create_a_persistent_qr_label_with_uuid(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->post(route('admin.qr.store'), [
            'code' => 'TWR-A-GENSET-01',
            'name' => 'Genset Utama Tower A',
            'type' => 'asset',
            'tower_name' => 'Tower A',
            'floor_number' => 1,
            'zone' => 'Ruang Utilitas',
            'description' => 'Label pada panel genset utama.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.qr.index'));

        $location = QrLocation::where('code', 'TWR-A-GENSET-01')->firstOrFail();

        $this->assertNotEmpty($location->uuid);
        $this->assertDatabaseHas('qr_locations', [
            'uuid' => $location->uuid,
            'code' => 'TWR-A-GENSET-01',
            'name' => 'Genset Utama Tower A',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.qr.svg', $location))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');
    }

    public function test_manager_can_create_a_technician_account(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Teknisi Operasional',
            'email' => 'teknisi@example.test',
            'phone_number' => '081234567890',
            'role' => 'technician',
            'password' => 'rahasia-aman-123',
            'password_confirmation' => 'rahasia-aman-123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'teknisi@example.test',
            'phone_number' => '081234567890',
            'role' => 'technician',
            'is_active' => true,
        ]);
    }

    public function test_admin_user_routes_use_public_hash_instead_of_numeric_id(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician']);

        $url = route('admin.users.edit', $technician);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $technician->public_id);
        $this->assertStringEndsWith('/admin/users/'.$technician->public_id.'/edit', $url);
        $this->assertNotSame((string) $technician->id, basename(dirname(parse_url($url, PHP_URL_PATH))));
        $this->actingAs($manager)->get($url)->assertOk();
        $this->actingAs($manager)->get('/admin/users/'.$technician->id.'/edit')->assertNotFound();
    }

    public function test_manager_can_explicitly_change_a_user_password(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create([
            'role' => 'technician',
            'password' => 'password-lama-123',
        ]);

        $this->actingAs($manager)->put(route('admin.users.update', $technician), [
            'name' => $technician->name,
            'email' => $technician->email,
            'phone_number' => '081234567891',
            'role' => 'technician',
            'is_active' => '1',
            'change_password' => '1',
            'password' => 'password-baru-456',
            'password_confirmation' => 'password-baru-456',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('password-baru-456', $technician->fresh()->password));
    }

    public function test_qr_location_seeder_is_stable_and_complete(): void
    {
        $this->seed();

        $this->assertDatabaseCount('qr_locations', 8);
        $this->assertDatabaseHas('qr_locations', [
            'uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c01',
            'code' => 'LEO-LIFT-A',
            'reference_image_path' => 'images/locations/lift.svg',
        ]);
        $this->assertDatabaseCount('sla_policies', 3);
        $this->assertDatabaseCount('towers', 5);
        $this->assertDatabaseHas('users', [
            'email' => 'teknisi@apartcare.test',
            'role' => 'technician',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_dispatch_a_dynamic_ticket_to_a_technician(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $location = QrLocation::create([
            'code' => 'TEST-DISPATCH-01',
            'name' => 'Panel Uji Disposisi',
            'type' => 'asset',
            'tower_name' => 'Leonie',
            'is_active' => true,
        ]);
        $ticket = Ticket::create([
            'qr_location_id' => $location->id,
            'reporter_name' => 'Pelapor Uji',
            'reporter_phone' => '081200000000',
            'public_token' => str_repeat('a', 64),
            'ticket_number' => 'AC-TEST-DISPATCH',
            'ticket_type' => 'asset',
            'title' => 'Panel mengeluarkan suara',
            'issue_description' => 'Terdengar suara dengung tidak biasa dari panel.',
            'priority_level' => 'high',
            'status' => 'submitted',
            'target_sla_at' => now()->addHours(8),
        ]);

        $this->actingAs($manager)->post(route('admin.tickets.dispatch', $ticket), [
            'technician_user_id' => $technician->id,
            'dispatch_notes' => 'Periksa sambungan dan dokumentasikan kondisi panel.',
        ])->assertRedirect();

        $this->assertDatabaseHas('work_orders', [
            'ticket_id' => $ticket->id,
            'technician_user_id' => $technician->id,
            'tro_user_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'verified']);
        $this->assertDatabaseHas('ticket_status_logs', ['ticket_id' => $ticket->id, 'to_status' => 'verified']);
    }

    public function test_assigned_technician_can_start_and_complete_work_order(): void
    {
        Storage::fake('local');
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $location = QrLocation::create([
            'code' => 'TEST-WORK-01',
            'name' => 'Pompa Uji',
            'type' => 'asset',
            'tower_name' => 'Leonie',
            'is_active' => true,
        ]);
        $ticket = Ticket::create([
            'qr_location_id' => $location->id,
            'reporter_name' => 'Pelapor Uji',
            'reporter_phone' => '081200000000',
            'public_token' => str_repeat('b', 64),
            'ticket_number' => 'AC-TEST-WORK',
            'ticket_type' => 'asset',
            'title' => 'Pompa tidak stabil',
            'issue_description' => 'Tekanan pompa berubah-ubah saat digunakan.',
            'priority_level' => 'high',
            'status' => 'verified',
            'target_sla_at' => now()->addHours(8),
        ]);
        $ticket->workOrder()->create([
            'tro_user_id' => $manager->id,
            'technician_user_id' => $technician->id,
            'dispatched_at' => now(),
        ]);

        $this->actingAs($technician)->post(route('admin.tickets.start', $ticket), [
            'before_photo' => UploadedFile::fake()->createWithContent('before.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
        ])->assertRedirect();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'in_progress']);

        $this->actingAs($technician)->post(route('admin.tickets.complete', $ticket), [
            'after_photo' => UploadedFile::fake()->createWithContent('after.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
            'technician_notes' => 'Sambungan dikencangkan dan tekanan pompa kembali stabil.',
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'pending_verification']);
        $this->assertDatabaseCount('ticket_attachments', 2);

        $this->actingAs($manager)->post(route('admin.tickets.verify', $ticket), [
            'decision' => 'approve',
            'verification_notes' => 'Hasil pekerjaan sesuai dan tekanan sudah stabil.',
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'closed']);
        $this->assertDatabaseHas('sla_logs', ['ticket_id' => $ticket->id, 'breach_status' => 'compliant']);
    }

    public function test_technician_has_read_only_access_to_qr_labels(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);
        $location = QrLocation::create([
            'code' => 'READ-ONLY-QR',
            'name' => 'QR Akses Teknisi',
            'type' => 'facility',
            'tower_name' => 'Tower A',
            'is_active' => true,
        ]);

        $this->actingAs($technician)->get(route('admin.qr.index'))
            ->assertOk()
            ->assertSee('Akses lihat saja')
            ->assertDontSee('Tambah label');
        $this->actingAs($technician)->get(route('admin.qr.edit', $location))->assertForbidden();
        $this->actingAs($technician)->patch(route('admin.qr.toggle', $location))->assertForbidden();
    }

    public function test_internal_notification_is_clickable_and_marked_as_read(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $ticket = Ticket::create([
            'reporter_name' => 'Pelapor Notifikasi',
            'reporter_phone' => '081200000099',
            'public_token' => str_repeat('n', 64),
            'ticket_number' => 'AC-NOTIF-TEST',
            'ticket_type' => 'facility',
            'title' => 'Uji notifikasi',
            'issue_description' => 'Menguji notifikasi internal yang dapat dibaca.',
            'priority_level' => 'high',
            'status' => 'rework',
            'target_sla_at' => now()->addHours(8),
        ]);
        $ticket->workOrder()->create([
            'tro_user_id' => $manager->id,
            'technician_user_id' => $technician->id,
            'dispatched_at' => now(),
        ]);

        app(TicketInternalNotifier::class)->send($ticket, 'rework', $manager);
        $notification = $technician->unreadNotifications()->firstOrFail();

        $this->actingAs($technician)->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('notifications.0.ticket_number', 'AC-NOTIF-TEST');
        $this->actingAs($technician)->postJson(route('admin.notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('url', route('admin.tickets.show', $ticket));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_dashboard_realtime_feed_excludes_closed_tickets(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        foreach (['submitted' => 'AC-ACTIVE-DASH', 'closed' => 'AC-CLOSED-DASH'] as $status => $number) {
            Ticket::create([
                'reporter_name' => 'Pelapor Dashboard',
                'reporter_phone' => '081200000088',
                'public_token' => str_repeat($status === 'closed' ? 'c' : 'd', 64),
                'ticket_number' => $number,
                'ticket_type' => 'facility',
                'title' => 'Uji Dashboard',
                'issue_description' => 'Memastikan dashboard hanya menampilkan tiket aktif.',
                'priority_level' => 'regular',
                'status' => $status,
                'target_sla_at' => now()->addDay(),
            ]);
        }

        $this->actingAs($manager)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('AC-ACTIVE-DASH')
            ->assertDontSee('AC-CLOSED-DASH');
        $this->actingAs($manager)->getJson(route('admin.dashboard.feed'))
            ->assertOk()
            ->assertSee('AC-ACTIVE-DASH')
            ->assertDontSee('AC-CLOSED-DASH');
    }

    public function test_busy_technician_is_blocked_for_high_priority_but_allowed_for_regular_ticket(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $busyTicket = Ticket::create([
            'reporter_name' => 'Pelapor Beban', 'reporter_phone' => '081211111111',
            'public_token' => str_repeat('b', 64), 'ticket_number' => 'AC-BUSY-001',
            'ticket_type' => 'facility', 'title' => 'Tugas berjalan',
            'issue_description' => 'Tiket aktif yang sedang ditangani teknisi.',
            'priority_level' => 'regular', 'status' => 'in_progress', 'target_sla_at' => now()->addDay(),
        ]);
        $busyTicket->workOrder()->create([
            'tro_user_id' => $manager->id, 'technician_user_id' => $technician->id,
            'dispatched_at' => now(), 'started_at' => now(),
        ]);
        $highTicket = Ticket::create([
            'reporter_name' => 'Pelapor Prioritas', 'reporter_phone' => '081222222222',
            'public_token' => str_repeat('h', 64), 'ticket_number' => 'AC-HIGH-002',
            'ticket_type' => 'facility', 'title' => 'Gangguan prioritas tinggi',
            'issue_description' => 'Tiket tinggi hanya untuk teknisi yang tersedia.',
            'priority_level' => 'high', 'status' => 'submitted', 'target_sla_at' => now()->addHours(8),
        ]);

        $this->actingAs($manager)->get(route('admin.tickets.show', $highTicket))
            ->assertOk()
            ->assertSee('1 tiket aktif · tidak tersedia');
        $this->actingAs($manager)->post(route('admin.tickets.dispatch', $highTicket), [
            'technician_user_id' => $technician->id,
            'dispatch_notes' => 'Tangani gangguan prioritas tinggi.',
        ])->assertSessionHasErrors('technician_user_id');
        $this->assertDatabaseMissing('work_orders', ['ticket_id' => $highTicket->id]);

        $regularTicket = Ticket::create([
            'reporter_name' => 'Pelapor Reguler', 'reporter_phone' => '081233333333',
            'public_token' => str_repeat('r', 64), 'ticket_number' => 'AC-REGULAR-003',
            'ticket_type' => 'facility', 'title' => 'Perbaikan ringan',
            'issue_description' => 'Tiket reguler boleh dikerjakan secara paralel.',
            'priority_level' => 'regular', 'status' => 'submitted', 'target_sla_at' => now()->addDay(),
        ]);
        $this->actingAs($manager)->post(route('admin.tickets.dispatch', $regularTicket), [
            'technician_user_id' => $technician->id,
            'dispatch_notes' => 'Kerjakan setelah pemeriksaan tugas utama.',
        ])->assertRedirect();
        $this->assertDatabaseHas('work_orders', [
            'ticket_id' => $regularTicket->id,
            'technician_user_id' => $technician->id,
        ]);
    }

    public function test_manager_can_reassign_an_in_progress_ticket_to_available_technician(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $previous = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $replacement = User::factory()->create(['role' => 'technician', 'is_active' => true]);
        $ticket = Ticket::create([
            'reporter_name' => 'Pelapor Pergantian', 'reporter_phone' => '081244444444',
            'public_token' => str_repeat('g', 64), 'ticket_number' => 'AC-REASSIGN-001',
            'ticket_type' => 'facility', 'title' => 'Teknisi tidak tersedia',
            'issue_description' => 'Pekerjaan perlu dialihkan kepada teknisi pengganti.',
            'priority_level' => 'high', 'status' => 'in_progress', 'target_sla_at' => now()->addHours(8),
        ]);
        $ticket->workOrder()->create([
            'tro_user_id' => $manager->id,
            'technician_user_id' => $previous->id,
            'before_photo_path' => 'work-orders/before/old.jpg',
            'dispatched_at' => now()->subHour(),
            'started_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($manager)->post(route('admin.tickets.reassign', $ticket), [
            'technician_user_id' => $replacement->id,
            'reassignment_reason' => 'Teknisi awal sedang cuti dan tidak dapat melanjutkan pekerjaan.',
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'verified']);
        $this->assertDatabaseHas('work_orders', [
            'ticket_id' => $ticket->id,
            'technician_user_id' => $replacement->id,
            'started_at' => null,
            'before_photo_path' => null,
        ]);
        $this->assertDatabaseHas('ticket_status_logs', [
            'ticket_id' => $ticket->id,
            'from_status' => 'in_progress',
            'to_status' => 'verified',
        ]);
        $this->assertSame(1, $previous->unreadNotifications()->count());
        $this->assertSame(1, $replacement->unreadNotifications()->count());
    }
}
