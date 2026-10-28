<?php

namespace Tests\Feature;

use App\Models\QrLocation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

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
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'teknisi@example.test',
            'phone_number' => '081234567890',
            'role' => 'technician',
            'is_active' => true,
        ]);
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
        Storage::fake('public');
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
}
