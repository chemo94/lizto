<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Services\OfferDispatchService;
use PHPUnit\Framework\TestCase;

class OfferDispatchTest extends TestCase
{
    /**
     * Test auto-acceptance logic conditions.
     */
    public function test_should_auto_accept_when_conditions_are_met(): void
    {
        $driver = new Driver([
            'auto_accept_enabled'      => true,
            'auto_accept_min_earning'  => 8.00,
            'auto_accept_max_distance' => 5.0,
        ]);

        // Earning S/ 10.00, distance 3.5 km -> Meets criteria
        $this->assertTrue(OfferDispatchService::shouldAutoAccept($driver, 10.00, 3.5));

        // Earning S/ 7.50 (< 8.00) -> Does not meet criteria
        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driver, 7.50, 3.5));

        // Distance 6.2 km (> 5.0) -> Does not meet criteria
        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driver, 12.00, 6.2));
    }

    /**
     * Test auto-acceptance disabled condition.
     */
    public function test_should_not_auto_accept_when_disabled(): void
    {
        $driver = new Driver([
            'auto_accept_enabled'      => false,
            'auto_accept_min_earning'  => 5.00,
            'auto_accept_max_distance' => 10.0,
        ]);

        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driver, 15.00, 2.0));
    }

    /**
     * Test constant 15-second response window.
     */
    public function test_offer_response_window_is_fifteen_seconds(): void
    {
        $this->assertEquals(15, OfferDispatchService::RESPONSE_WINDOW_SECONDS);
    }
}
