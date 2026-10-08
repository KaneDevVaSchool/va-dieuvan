<?php

namespace Tests\Unit\Support;

use App\Services\Checklist\ChecklistResultCalculator;
use PHPUnit\Framework\TestCase;

class ChecklistResultCalculatorTest extends TestCase
{
    public function test_any_critical_ng_fails(): void
    {
        $r = ChecklistResultCalculator::calculate([
            ['severity' => 'critical', 'result' => 'ok'],
            ['severity' => 'critical', 'result' => 'ng'],
            ['severity' => 'non_critical', 'result' => 'ng'],
        ]);

        $this->assertSame('fail', $r['result']);
        $this->assertSame(1, $r['critical_ng']);
        $this->assertSame(1, $r['non_critical_ng']);
    }

    public function test_non_critical_ng_and_na_still_pass(): void
    {
        $r = ChecklistResultCalculator::calculate([
            ['severity' => 'critical', 'result' => 'ok'],
            ['severity' => 'critical', 'result' => 'na'],
            ['severity' => 'non_critical', 'result' => 'ng'],
            ['severity' => 'non_critical', 'result' => null],
        ]);

        $this->assertSame('pass', $r['result']);
        $this->assertSame(0, $r['critical_ng']);
        $this->assertSame(1, $r['unanswered']);
    }
}
