<?php

/**
 * Nhóm vai trò hiển thị trên UI gán quyền (API thêm field `category` vào mỗi role).
 * Key: mã role (bảng roles.name). Giá trị: nhãn nhóm (Anh hoặc Việt tuỳ sản phẩm).
 */
return [
    'default_label' => 'Other',

    'labels' => [
        'superadmin' => 'System',
        'admin' => 'System',
        'dispatcher' => 'Operations',
        'driver' => 'Operations',
        'accountant' => 'Finance',
        'internal_user' => 'Internal',
    ],
];
