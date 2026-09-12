<?php

namespace Tests\Unit;

use App\Services\DeliveryFinancialLedger;
use PHPUnit\Framework\TestCase;

class DriverCommissionTierTest extends TestCase
{
    /** @dataProvider tierCases */
    public function test_accumulated_deliveries_determine_a_non_resetting_tier(
        int $jobs,
        string $expectedTier,
        float $expectedPercent,
        int $nextNeeded
    ): void {
        $tier = DeliveryFinancialLedger::commissionTierForCompletedJobs($jobs, 10);

        $this->assertSame($expectedTier, $tier['tier_name']);
        $this->assertSame($expectedPercent, $tier['effective_percent']);
        $this->assertSame($nextNeeded, $tier['next_tier_needed']);
        $this->assertSame($jobs, $tier['total_completed_jobs']);
    }

    public static function tierCases(): array
    {
        return [
            'initial start' => [0, 'Inicial', 10.0, 10],
            'initial end' => [9, 'Inicial', 10.0, 1],
            'bronze start' => [10, 'Bronce', 7.0, 10],
            'bronze end' => [19, 'Bronce', 7.0, 1],
            'silver start' => [20, 'Plata', 5.0, 10],
            'silver end' => [29, 'Plata', 5.0, 1],
            'preferred start' => [30, 'Preferente', 0.0, 0],
            'preferred remains' => [75, 'Preferente', 0.0, 0],
        ];
    }

    public function test_percentage_discount_never_drops_below_five_percent(): void
    {
        $tier = DeliveryFinancialLedger::commissionTierForCompletedJobs(20, 6);

        $this->assertSame(5.0, $tier['effective_percent']);
    }
}
