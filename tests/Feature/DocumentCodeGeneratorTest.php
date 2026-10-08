<?php

namespace Tests\Feature;

use App\Support\DocumentCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DocumentCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_increase_per_day_and_reset_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 03:00:00', 'UTC'));

        $this->assertSame('DX-260930-1', DocumentCodeGenerator::next('DX'));
        $this->assertSame('DX-260930-2', DocumentCodeGenerator::next('DX'));

        Carbon::setTestNow(Carbon::parse('2026-10-01 03:00:00', 'UTC'));
        $this->assertSame('DX-261001-1', DocumentCodeGenerator::next('DX'));
    }

    public function test_counters_are_separate_per_prefix(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 03:00:00', 'UTC'));

        $this->assertSame('DX-260930-1', DocumentCodeGenerator::next('DX'));
        $this->assertSame('TT-260930-1', DocumentCodeGenerator::next('TT'));
        $this->assertSame('DX-260930-2', DocumentCodeGenerator::next('DX'));
    }

    public function test_date_uses_vietnam_timezone(): void
    {
        // 18:30 UTC ngày 30/09 = 01:30 ngày 01/10 giờ Việt Nam.
        Carbon::setTestNow(Carbon::parse('2026-09-30 18:30:00', 'UTC'));

        $this->assertSame('CL-261001-1', DocumentCodeGenerator::next('CL'));
    }
}
