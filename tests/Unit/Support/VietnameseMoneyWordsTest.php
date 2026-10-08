<?php

namespace Tests\Unit\Support;

use App\Support\VietnameseMoneyWords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VietnameseMoneyWordsTest extends TestCase
{
    /**
     * @return array<string, array{0: float|int, 1: string}>
     */
    public static function cases(): array
    {
        return [
            'zero' => [0, 'Không đồng.'],
            'sample from template' => [160000, 'Một trăm sáu mươi nghìn đồng.'],
            'single digit' => [5, 'Năm đồng.'],
            'ten' => [10, 'Mười đồng.'],
            'fifteen' => [15, 'Mười lăm đồng.'],
            'twenty one' => [21, 'Hai mươi mốt đồng.'],
            'twenty five' => [25, 'Hai mươi lăm đồng.'],
            'lẻ' => [105, 'Một trăm lẻ năm đồng.'],
            'eleven' => [11, 'Mười một đồng.'],
            'thousand with inner zero group' => [1005000, 'Một triệu không trăm lẻ năm nghìn đồng.'],
            'invoice sample' => [5580700, 'Năm triệu năm trăm tám mươi nghìn bảy trăm đồng.'],
            'billion' => [2000000000, 'Hai tỷ đồng.'],
            'billion and change' => [1000000001, 'Một tỷ không trăm lẻ một đồng.'],
            'thousand billion' => [1000000000000, 'Một nghìn tỷ đồng.'],
            'rounds decimals' => [99999.6, 'Một trăm nghìn đồng.'],
            'negative' => [-50000, 'Âm năm mươi nghìn đồng.'],
        ];
    }

    #[DataProvider('cases')]
    public function test_format(float|int $amount, string $expected): void
    {
        $this->assertSame($expected, VietnameseMoneyWords::format($amount));
    }
}
