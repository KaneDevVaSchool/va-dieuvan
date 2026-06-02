<?php

namespace App\Services\Signature;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

/**
 * Heuristic signature detection — GD pixel density in ROI zones.
 * PDF without rasterization typically yields low scores → manual_review.
 */
class SignatureDetectionService
{
    /**
     * @return array{
     *     detected: bool,
     *     score: float,
     *     regions: array<int, array<string, mixed>>,
     *     verification_status: string
     * }
     */
    public function analyze(Attachment $attachment): array
    {
        if (! config('dispatch.signature_detection.enabled', true)) {
            return [
                'detected' => false,
                'score' => 0.0,
                'regions' => [],
                'verification_status' => 'manual_review',
            ];
        }

        $mime = strtolower((string) $attachment->mime_type);
        if (! str_starts_with($mime, 'image/')) {
            return $this->resultForNonRaster($mime);
        }

        $bytes = $this->readBytes($attachment);
        if ($bytes === null) {
            return $this->lowScoreResult('manual_review', 'unreadable_file');
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return $this->lowScoreResult('manual_review', 'gd_decode_failed');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return $this->lowScoreResult('no_signature', 'empty_image');
        }

        $zones = config('dispatch.signature_detection.roi_zones', []);
        $regions = [];
        $scores = [];

        foreach ($zones as $zone) {
            $density = $this->inkDensityInRoi($image, $width, $height, $zone);
            $scores[] = $density;
            $regions[] = [
                'role' => $zone['role'] ?? 'unknown',
                'page' => 1,
                'bbox' => [$zone['x'] ?? 0, $zone['y'] ?? 0, $zone['w'] ?? 0, $zone['h'] ?? 0],
                'confidence' => round($density, 4),
            ];
        }

        imagedestroy($image);

        $score = $scores !== [] ? max($scores) : 0.0;
        $autoPass = (float) config('dispatch.signature_detection.auto_pass_min_score', 0.75);
        $manualMin = (float) config('dispatch.signature_detection.manual_review_min_score', 0.35);

        if ($score >= $autoPass) {
            return [
                'detected' => true,
                'score' => $score,
                'regions' => $regions,
                'verification_status' => 'auto_pass',
            ];
        }

        if ($score >= $manualMin) {
            return [
                'detected' => true,
                'score' => $score,
                'regions' => $regions,
                'verification_status' => 'manual_review',
            ];
        }

        return [
            'detected' => false,
            'score' => $score,
            'regions' => $regions,
            'verification_status' => 'no_signature',
        ];
    }

    /**
     * @param  array<string, float|string>  $zone
     */
    private function inkDensityInRoi(\GdImage $image, int $width, int $height, array $zone): float
    {
        $x0 = (int) floor(((float) ($zone['x'] ?? 0)) * $width);
        $y0 = (int) floor(((float) ($zone['y'] ?? 0)) * $height);
        $w = max(1, (int) floor(((float) ($zone['w'] ?? 0.1)) * $width));
        $h = max(1, (int) floor(((float) ($zone['h'] ?? 0.1)) * $height));
        $x1 = min($width - 1, $x0 + $w);
        $y1 = min($height - 1, $y0 + $h);

        $dark = 0;
        $total = 0;
        for ($y = $y0; $y <= $y1; $y++) {
            for ($x = $x0; $x <= $x1; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                if ($lum < 0.72) {
                    $dark++;
                }
                $total++;
            }
        }

        return $total > 0 ? $dark / $total : 0.0;
    }

    private function readBytes(Attachment $attachment): ?string
    {
        if ($attachment->file_binary !== null && $attachment->file_binary !== '') {
            return $attachment->file_binary;
        }

        if ($attachment->path) {
            $disk = $attachment->disk ?: 'public';

            return Storage::disk($disk)->get($attachment->path);
        }

        return null;
    }

    /**
     * @return array{detected: bool, score: float, regions: array, verification_status: string}
     */
    private function resultForNonRaster(string $mime): array
    {
        if (str_contains($mime, 'pdf')) {
            return $this->lowScoreResult('manual_review', 'pdf_not_rasterized');
        }

        return $this->lowScoreResult('manual_review', 'unsupported_mime');
    }

    /**
     * @return array{detected: bool, score: float, regions: array, verification_status: string}
     */
    private function lowScoreResult(string $status, string $reason): array
    {
        return [
            'detected' => false,
            'score' => 0.0,
            'regions' => [['reason' => $reason]],
            'verification_status' => $status,
        ];
    }
}
