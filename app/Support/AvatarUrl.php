<?php

namespace App\Support;

final class AvatarUrl
{
    /**
     * Trả URL avatar an toàn cho client SPA: path /storage/... tương đối (tránh lệch APP_URL).
     * URL ngoài (Google, …) giữ nguyên.
     */
    public static function forClient(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/storage/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return $url;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $out = $path;
        if (is_string($query) && $query !== '') {
            $out .= '?'.$query;
        }
        if (is_string($fragment) && $fragment !== '') {
            $out .= '#'.$fragment;
        }

        return $out;
    }
}
