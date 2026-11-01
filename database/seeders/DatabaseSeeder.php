<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\QrLocation;
use App\Models\SlaPolicy;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isProduction() && in_array(env('ADMIN_PASSWORD'), [null, '', 'password'], true)) {
            throw new \RuntimeException('ADMIN_PASSWORD yang kuat wajib diisi sebelum menjalankan seeder di production.');
        }

        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@apartcare.test')], [
            'name' => env('ADMIN_NAME', 'System Administrator'),
            'password' => env('ADMIN_PASSWORD', 'password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $technicianPassword = env('TECHNICIAN_PASSWORD', app()->isProduction() ? null : 'teknisi-demo-123');
        if (filled($technicianPassword)) {
            User::updateOrCreate(['email' => env('TECHNICIAN_EMAIL', 'teknisi@apartcare.test')], [
                'name' => env('TECHNICIAN_NAME', 'Teknisi Demo'),
                'phone_number' => env('TECHNICIAN_PHONE', '081234567891'),
                'password' => $technicianPassword,
                'role' => 'technician',
                'is_active' => true,
            ]);
        }

        $building = Building::updateOrCreate(['code' => 'SH-BSD'], [
            'name' => 'Sky House BSD City',
            'address' => 'Jl. BSD Raya Utama, Sampora, Cisauk, Kabupaten Tangerang, Banten',
            'is_active' => true,
        ]);

        $towers = collect([
            ['code' => 'LEO', 'name' => 'Leonie', 'total_floors' => 30],
            ['code' => 'JER', 'name' => 'Jervois', 'total_floors' => 30],
            ['code' => 'DUX', 'name' => 'Duxton', 'total_floors' => 30],
            ['code' => 'BAR', 'name' => 'Barangaroo', 'total_floors' => 30],
            ['code' => 'BRI', 'name' => 'Bristol', 'total_floors' => 30],
        ])->mapWithKeys(function (array $tower) use ($building) {
            $record = Tower::updateOrCreate([
                'building_id' => $building->id,
                'code' => $tower['code'],
            ], $tower + ['is_active' => true]);

            return [$tower['code'] => $record];
        });

        foreach ([
            ['priority_level' => 'emergency', 'name' => 'Darurat keselamatan', 'response_minutes' => 15, 'resolution_minutes' => 120],
            ['priority_level' => 'high', 'name' => 'Gangguan aktivitas utama', 'response_minutes' => 60, 'resolution_minutes' => 480],
            ['priority_level' => 'regular', 'name' => 'Gangguan reguler', 'response_minutes' => 240, 'resolution_minutes' => 1440],
        ] as $policy) {
            SlaPolicy::updateOrCreate(['priority_level' => $policy['priority_level']], $policy + [
                'escalation_role' => 'manager',
                'is_active' => true,
            ]);
        }

        $locations = [
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c01', 'code' => 'LEO-LIFT-A', 'name' => 'Lift Penumpang A', 'type' => 'facility', 'tower' => 'LEO', 'floor_number' => 1, 'zone' => 'Lobby utama', 'description' => 'Lift sisi kiri pada bank lift utama Tower Leonie.', 'image' => 'lift.svg', 'notes' => 'Tempel di panel luar lift, sisi kanan tombol panggil.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c02', 'code' => 'LEO-LIFT-B', 'name' => 'Lift Penumpang B', 'type' => 'facility', 'tower' => 'LEO', 'floor_number' => 1, 'zone' => 'Lobby utama', 'description' => 'Lift sisi kanan pada bank lift utama Tower Leonie.', 'image' => 'lift.svg', 'notes' => 'Tempel di panel luar lift, sisi kiri tombol panggil.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c03', 'code' => 'LEO-PUMP-01', 'name' => 'Pompa Booster 01', 'type' => 'asset', 'tower' => 'LEO', 'floor_number' => 0, 'zone' => 'Ruang pompa basement', 'description' => 'Pompa distribusi air bersih utama nomor 01.', 'image' => 'pump.svg', 'notes' => 'Tempel pada pelindung panel pompa, tidak pada bagian bergetar.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c04', 'code' => 'LEO-MDP-01', 'name' => 'Panel Listrik Utama', 'type' => 'asset', 'tower' => 'LEO', 'floor_number' => 0, 'zone' => 'Ruang MEP basement', 'description' => 'Main distribution panel Tower Leonie.', 'image' => 'panel.svg', 'notes' => 'Tempel pada pintu panel bagian luar, jauh dari label peringatan.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c05', 'code' => 'LEO-COR-12E', 'name' => 'Koridor Timur Lantai 12', 'type' => 'location', 'tower' => 'LEO', 'floor_number' => 12, 'zone' => 'Dekat lift servis', 'description' => 'Titik laporan untuk lampu, plafon, dinding, dan kebersihan koridor timur.', 'image' => 'corridor.svg', 'notes' => 'Tempel pada dinding dekat nomor lantai, tinggi sekitar 150 cm.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c06', 'code' => 'LEO-FH-12E', 'name' => 'Fire Hydrant Lantai 12', 'type' => 'asset', 'tower' => 'LEO', 'floor_number' => 12, 'zone' => 'Koridor timur', 'description' => 'Kotak hidran kebakaran pada koridor timur.', 'image' => 'hydrant.svg', 'notes' => 'Tempel di sudut kanan atas pintu kotak hidran.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c07', 'code' => 'PUBLIC-GYM-01', 'name' => 'Pusat Kebugaran', 'type' => 'facility', 'tower' => 'LEO', 'floor_number' => 1, 'zone' => 'Clubhouse', 'description' => 'Area peralatan kebugaran bersama dekat kolam renang.', 'image' => 'gym.svg', 'notes' => 'Tempel di papan informasi pintu masuk gym.'],
            ['uuid' => '6e796ab8-69a7-4b87-889a-f2fa4eb72c08', 'code' => 'PUBLIC-POOL-01', 'name' => 'Kolam Renang Utama', 'type' => 'facility', 'tower' => 'LEO', 'floor_number' => 1, 'zone' => 'Taman utama', 'description' => 'Area kolam renang utama dan fasilitas di sekelilingnya.', 'image' => 'pool.svg', 'notes' => 'Tempel pada papan tata tertib dekat akses masuk.'],
        ];

        foreach ($locations as $location) {
            $tower = $towers[$location['tower']];
            QrLocation::updateOrCreate(['code' => $location['code']], [
                'tower_id' => $tower->id,
                'uuid' => $location['uuid'],
                'name' => $location['name'],
                'type' => $location['type'],
                'tower_name' => $tower->name,
                'floor_number' => $location['floor_number'],
                'zone' => $location['zone'],
                'description' => $location['description'],
                'reference_image_path' => 'images/locations/'.$location['image'],
                'installation_notes' => $location['notes'],
                'is_active' => true,
            ]);
        }
    }
}
