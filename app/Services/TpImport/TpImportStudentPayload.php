<?php

namespace App\Services\TpImport;

use App\Models\TpStudent;
use Carbon\Carbon;

class TpImportStudentPayload
{
    /** @var list<string> */
    public const METADATA_KEYS = [
        'gender',
        'date_of_birth',
        'father_name',
        'father_phone',
        'mother_name',
        'mother_phone',
        'pickup_point',
        'note',
    ];

    /**
     * @param  array<string, mixed>  $data  Mapped / fixed row data (flat keys).
     * @return array<string, mixed>  Attributes for TpStudent::create / updateOrCreate.
     */
    public function fromImportRow(array $data, ?TpStudent $existing = null): array
    {
        $payload = [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'grade' => $this->nullableString($data['grade'] ?? null),
            'class_name' => $this->nullableString($data['class_name'] ?? null),
            'parent_name' => $this->nullableString($data['parent_name'] ?? null),
            'parent_phone' => $this->nullableString($data['parent_phone'] ?? null),
            'address' => $this->nullableString($data['address'] ?? null),
            'source' => 'excel_import',
        ];

        $metadata = $this->buildMetadata($data);
        if ($metadata !== []) {
            if ($existing) {
                $metadata = array_merge($existing->metadata ?? [], $metadata);
            }
            $payload['metadata'] = array_filter(
                $metadata,
                fn ($value) => $value !== null && $value !== ''
            );
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildMetadata(array $data): array
    {
        $metadata = [];

        foreach (self::METADATA_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $raw = $data[$key];
            if ($raw === null || $raw === '') {
                continue;
            }

            if ($key === 'gender') {
                $normalized = self::normalizeGender($raw);
                if ($normalized !== null) {
                    $metadata['gender'] = $normalized;
                }

                continue;
            }

            if ($key === 'date_of_birth') {
                $normalized = self::normalizeDateOfBirth($raw);
                if ($normalized !== null) {
                    $metadata['date_of_birth'] = $normalized;
                }

                continue;
            }

            $metadata[$key] = is_string($raw) ? trim($raw) : $raw;
        }

        return $metadata;
    }

    public static function normalizeGender(mixed $value): ?string
    {
        $s = mb_strtolower(trim((string) $value));
        if ($s === '') {
            return null;
        }

        return match (true) {
            in_array($s, ['male', 'm', 'nam', 'boy'], true) => 'male',
            in_array($s, ['female', 'f', 'nữ', 'nu', 'girl', 'nữ'], true) => 'female',
            in_array($s, ['other', 'khác', 'khac'], true) => 'other',
            default => null,
        };
    }

    public static function normalizeDateOfBirth(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        try {
            return Carbon::parse($s)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
