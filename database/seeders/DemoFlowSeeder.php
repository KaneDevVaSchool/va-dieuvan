<?php

namespace Database\Seeders;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\TransportProvider;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Tài khoản demo + tài nguyên tối thiểu để thử luồng yêu cầu / chuyến / hàng.
 * Mật khẩu mặc định: password
 */
class DemoFlowSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $dispatcher = User::updateOrCreate(
            ['email' => 'dispatcher@va.local'],
            [
                'name' => 'Điều vận Demo',
                'password' => $password,
                'phone' => '0900000001',
                'employee_code' => 'DV001',
                'is_active' => true,
            ]
        );

        $internal = User::updateOrCreate(
            ['email' => 'noibo@va.local'],
            [
                'name' => 'Cán bộ nội bộ Demo',
                'password' => $password,
                'phone' => '0900000002',
                'employee_code' => 'NB001',
                'is_active' => true,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@va.local'],
            [
                'name' => 'Admin Demo',
                'password' => $password,
                'phone' => '0900000000',
                'employee_code' => 'AD001',
                'is_active' => true,
            ]
        );

        $dispatcher->syncRoles(['dispatcher']);
        $internal->syncRoles(['internal_user']);
        $admin->syncRoles(['admin']);

        TransportProvider::firstOrCreate(
            ['name' => 'NCC Demo TP.HCM'],
            [
                'type' => 'vendor',
                'contact_name' => 'Liên hệ NCC',
                'contact_phone' => '0280000000',
                'is_active' => true,
            ]
        );

        $driverUser = User::updateOrCreate(
            ['email' => 'taixe@va.local'],
            [
                'name' => 'Tài xế Demo',
                'password' => $password,
                'phone' => '0900000003',
                'employee_code' => 'TX001',
                'is_active' => true,
            ]
        );
        $driverUser->syncRoles(['driver']);

        $driver = Driver::updateOrCreate(
            ['phone' => '0900000003'],
            [
                'user_id' => $driverUser->id,
                'full_name' => 'Tài xế Demo',
                'employment_status' => 'active',
                'availability_status' => 'available',
            ]
        );

        $vehicles = [
            ['license_plate' => '51A-10001', 'type' => 'Xe 7 chỗ', 'seat_count' => 7],
            ['license_plate' => '51A-10015', 'type' => 'Xe 15 chỗ', 'seat_count' => 15],
            ['license_plate' => '51A-10028', 'type' => 'Xe 28 chỗ', 'seat_count' => 28],
            ['license_plate' => '51B-90500', 'type' => 'Van 500kg', 'seat_count' => null, 'payload_kg' => 500],
            ['license_plate' => '51B-91000', 'type' => 'Van 1000kg', 'seat_count' => null, 'payload_kg' => 1000],
        ];

        foreach ($vehicles as $v) {
            $payload = [
                'type' => $v['type'],
                'seat_count' => array_key_exists('seat_count', $v) ? $v['seat_count'] : null,
                'payload_kg' => $v['payload_kg'] ?? null,
                'status' => 'ready',
            ];
            Vehicle::updateOrCreate(
                ['license_plate' => $v['license_plate']],
                $payload
            );
        }

        $bus = Vehicle::where('license_plate', '51A-10015')->first();

        $depart = now()->addDay()->setHour(7)->setMinute(0)->setSecond(0);

        $approvedRequest = DispatchRequest::updateOrCreate(
            ['paper_reference' => 'SEED-DEMO-BUSINESS-VT'],
            [
                'requester_id' => $internal->id,
                'approved_by' => $dispatcher->id,
                'origin' => 'VA Tân Bình',
                'destination' => 'Cơ sở Vũng Tàu',
                'depart_at' => $depart,
                'arrive_by' => $depart->copy()->addHours(10),
                'passenger_count' => 25,
                'notes' => 'Demo: tham chiếu gói TPHCM - Vũng Tàu (1 ngày).',
                'trip_type' => 'business',
                'status' => 'approved',
                'source_channel' => 'portal',
                'is_urgent' => false,
                'paper_status' => 'pending',
            ]
        );

        Trip::updateOrCreate(
            ['dispatch_request_id' => $approvedRequest->id],
            [
                'dispatcher_id' => $dispatcher->id,
                'vehicle_id' => $bus?->id,
                'driver_id' => $driver->id,
                'transport_provider_id' => null,
                'status' => 'assigned',
                'depart_at' => $approvedRequest->depart_at,
                'arrive_by' => $approvedRequest->arrive_by,
            ]
        );

        $cargoRequest = DispatchRequest::updateOrCreate(
            ['paper_reference' => 'SEED-DEMO-CARGO-TB-BT'],
            [
                'requester_id' => $internal->id,
                'origin' => 'VA Tân Bình',
                'destination' => 'VA Bình Thới',
                'depart_at' => now()->addHours(4),
                'arrive_by' => null,
                'passenger_count' => null,
                'notes' => 'Demo: chuyển hàng nội bộ ~3 km.',
                'trip_type' => 'cargo',
                'status' => 'pending',
                'source_channel' => 'portal',
                'is_urgent' => false,
                'paper_status' => 'pending',
            ]
        );

        CargoShipment::updateOrCreate(
            ['tracking_code' => 'DEMO-TB-BT-001'],
            [
                'dispatch_request_id' => $cargoRequest->id,
                'trip_id' => null,
                'sender_name' => 'Kho Tân Bình',
                'receiver_name' => 'Kho Bình Thới',
                'pickup_address' => 'VA Tân Bình',
                'delivery_address' => 'VA Bình Thới',
                'weight_grams' => 15_000,
                'quantity' => 3,
                'sla_due_at' => now()->addHours(3),
                'status' => 'pending',
            ]
        );
    }
}
