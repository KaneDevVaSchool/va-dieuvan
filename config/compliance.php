<?php

return [
    // Chỉ sử dụng disk private. Mỗi attachment lưu disk/path để giữ khả năng đọc file cũ.
    'disk' => env('COMPLIANCE_FILESYSTEM_DISK', 'compliance'),
    'timezone' => 'Asia/Ho_Chi_Minh',
];
