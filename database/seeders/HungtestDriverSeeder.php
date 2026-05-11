<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Gán tài khoản hungtest@vaschools.edu.vn làm tài xế (role + bảng drivers).
 * Chạy: php artisan db:seed --class=HungtestDriverSeeder
 */
class HungtestDriverSeeder extends Seeder
{
    public const EMAIL = 'hungtest@vaschools.edu.vn';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Tài xế (hungtest)',
                'password' => Hash::make('password'),
                'phone' => '0900000901',
                'employee_code' => 'TX-HUNGTEST',
                'is_active' => true,
            ]
        );

        $user->syncRoles(['driver']);

        Driver::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $user->name,
                'phone' => $user->phone,
                'employment_status' => 'active',
                'availability_status' => 'available',
            ]
        );
    }
}
