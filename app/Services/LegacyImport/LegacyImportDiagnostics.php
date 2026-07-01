<?php

namespace App\Services\LegacyImport;

/**
 * Thu thập lỗi / cảnh báo / dòng bỏ qua khi import — hiển thị cho người dùng không chuyên.
 */
class LegacyImportDiagnostics
{
    /** @var list<array{sheet:string,row:int,level:string,code:string,message:string,hint:?string}> */
    private array $items = [];

    public function add(
        string $sheet,
        int $row,
        string $level,
        string $code,
        string $message,
        ?string $hint = null,
    ): void {
        $this->items[] = [
            'sheet' => $sheet,
            'row' => $row,
            'level' => $level,
            'code' => $code,
            'message' => $message,
            'hint' => $hint,
        ];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return list<array<string,mixed>> */
    public function take(int $limit = 200): array
    {
        return array_slice($this->items, 0, max(1, $limit));
    }

    public function countByLevel(string $level): int
    {
        return count(array_filter($this->items, fn ($i) => $i['level'] === $level));
    }
}
