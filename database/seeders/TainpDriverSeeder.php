<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Gán tài khoản tainp@vaschools.edu.vn làm tài xế (role + bảng drivers).
 * Chạy: php artisan db:seed --class=TainpDriverSeeder
 */
class TainpDriverSeeder extends Seeder
{
    public const EMAIL = 'tainp@vaschools.edu.vn';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Tài xế (tainp)',
                'password' => Hash::make('password'),
                'phone' => '0900000900',
                'employee_code' => 'TX-TAINP',
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
