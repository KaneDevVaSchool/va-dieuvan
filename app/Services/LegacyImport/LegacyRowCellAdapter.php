<?php

namespace App\Services\LegacyImport;

/** @internal Adapter so mappers can keep using cell->getValue() after merge-fill. */
final class LegacyRowCellAdapter
{
    /**
     * @param  array<int, mixed>  $values
     * @return \OpenSpout\Common\Entity\Cell[]
     */
    public static function fromValues(array $values): array
    {
        $cells = [];
        foreach ($values as $idx => $val) {
            $cells[$idx] = new LegacyImportCell($val);
        }

        return $cells;
    }
}

final class LegacyImportCell
{
    public function __construct(private mixed $value) {}

    public function getValue(): mixed
    {
        return $this->value;
    }
}
