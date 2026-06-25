<?php

namespace Tests\Feature;

use App\Services\Trips\TripStatusTransitionValidator;
use Tests\TestCase;

class TripStatusTransitionValidatorTest extends TestCase
{
    public function test_allows_assigned_to_in_progress(): void
    {
        app(TripStatusTransitionValidator::class)->assertCanTransition('assigned', 'in_progress');
        $this->assertTrue(true);
    }

    public function test_blocks_assigned_to_completed(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(TripStatusTransitionValidator::class)->assertCanTransition('assigned', 'completed');
    }

    public function test_allows_approved_to_in_progress(): void
    {
        app(TripStatusTransitionValidator::class)->assertCanTransition('approved', 'in_progress');
        $this->assertTrue(true);
    }

    public function test_allows_driver_confirmed_to_in_progress(): void
    {
        app(TripStatusTransitionValidator::class)->assertCanTransition('driver_confirmed', 'in_progress');
        $this->assertTrue(true);
    }
}
