<?php

/**
 * Báo cáo bề mặt: route trong routes/api/spa + path Vue router.
 * Usage: php scripts/module-gap-report.php [--out=path.md]
 *
 * Không cần DB; không gọi HTTP.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$out = null;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6) ?: null;
    }
}

$spaDir = $root . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'spa';
$routerFile = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'router' . DIRECTORY_SEPARATOR . 'index.js';

$lines = [];

$lines[] = '# Báo cáo bề mặt API SPA + Vue';
$lines[] = '';
$lines[] = 'Generated: ' . gmdate('Y-m-d H:i:s') . ' UTC';
$lines[] = '';

if (! is_dir($spaDir)) {
    $lines[] = '**Lỗi:** không thấy thư mục `routes/api/spa`.';
} else {
    foreach (glob($spaDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
        $base = basename($file);
        $lines[] = '## SPA routes: `' . $base . '`';
        $lines[] = '';
        $lines[] = '| Method | URI fragment |';
        $lines[] = '|--------|--------------|';
        $content = file_get_contents($file) ?: '';
        if (preg_match_all(
            "/Route::(get|post|put|patch|delete)\(\s*['\"]([^'\"]+)['\"]/i",
            $content,
            $m,
            PREG_SET_ORDER
        )) {
            foreach ($m as $hit) {
                $method = strtoupper($hit[1]);
                $uri = $hit[2];
                $lines[] = '| ' . $method . ' | `' . h($uri) . '` |';
            }
        } else {
            $lines[] = '| — | *(không match được Route::)* |';
        }
        $lines[] = '';
    }
}

$lines[] = '## Vue Router paths (`resources/js/src/router/index.js`)';
$lines[] = '';

if (! is_readable($routerFile)) {
    $lines[] = '**Lỗi:** không đọc được `router/index.js`.';
} else {
    $js = file_get_contents($routerFile) ?: '';
    $lines[] = '| Path | Name |';
    $lines[] = '|------|------|';
    $paired = preg_match_all(
        '/path:\s*["\']([^"\']+)["\']\s*,\s*\R\s*name:\s*["\']([^"\']+)["\']/m',
        $js,
        $vm,
        PREG_SET_ORDER
    );
    if ($paired) {
        foreach ($vm as $hit) {
            $lines[] = '| `' . h($hit[1]) . '` | `' . h($hit[2]) . '` |';
        }
    } else {
        $lines[] = '| — | *(regex không bắt cặp path/name — xem file router)* |';
    }
    $lines[] = '';
}

$lines[] = '## Ghi chú';
$lines[] = '';
$lines[] = '- Đây chỉ là **danh mục kỹ thuật**, không thay thế backlog nghiệp vụ.';
$lines[] = '- Đối chiếu SRS: kiểm tra từng FR đã có route + màn tương ứng chưa.';
$lines[] = '';

$md = implode("\n", $lines);

if ($out !== null) {
    $target = str_starts_with($out, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $out) ? $out : $root . DIRECTORY_SEPARATOR . $out;
    $dir = dirname($target);
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($target, $md);
    fwrite(STDOUT, "Wrote {$target}\n");
} else {
    fwrite(STDOUT, $md);
}

function h(string $s): string
{
    return str_replace('|', '\\|', $s);
}
