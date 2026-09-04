<?php

/**
 * Tài khoản Google được cấp trước khi đăng nhập lần đầu.
 *
 * OAuth tìm user theo email → gắn google_id / avatar; role và hồ sơ tài xế giữ nguyên.
 * Chạy: php artisan db:seed --class=BootstrapUsersSeeder (sau RbacSeeder).
 */
return [
    [
        'email' => 'kietht@hcm.vaschools.edu.vn',
        'role' => 'driver',
        'name' => 'Trần Tuấn Kiệt',
    ],
    [
        'email' => 'sangnh@hcm.vaschools.edu.vn',
        'role' => 'driver',
        'name' => 'Nguyễn Hữu Sang',
    ],
    [
        'email' => 'purchasing@vaschools.edu.vn',
        'role' => 'admin',
        'name' => 'Purchasing',
    ],
    [
        'email' => 'hiennn@vaschools.edu.vn',
        'role' => 'superadmin',
        'name' => 'Nguyễn Ngọc Hiển',
    ],
];
