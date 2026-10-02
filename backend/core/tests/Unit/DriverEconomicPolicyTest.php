<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Driver;
use App\Models\Wallet;
use App\Services\DriverEconomicPolicyService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class DriverEconomicPolicyTest extends TestCase
{
    public function test_min_recharge_validation_rejects_under_8(): void
    {
        $this->expectException(ValidationException::class);
        DriverEconomicPolicyService::validateRechargeAmount(7.99);
    }

    public function test_min_recharge_validation_accepts_8_or_more(): void
    {
        // No debe lanzar excepción
        DriverEconomicPolicyService::validateRechargeAmount(8.00);
        DriverEconomicPolicyService::validateRechargeAmount(15.00);
        DriverEconomicPolicyService::validateRechargeAmount(50.00);
        $this->assertTrue(true);
    }

    public function test_economic_policy_constants_and_defaults(): void
    {
        $this->assertSame('PROMOTIONAL_BALANCE', DriverEconomicPolicyService::STATE_PROMOTIONAL_BALANCE);
        $this->assertSame('ACTIVE_BALANCE', DriverEconomicPolicyService::STATE_ACTIVE_BALANCE);
        $this->assertSame('INSUFFICIENT_BALANCE', DriverEconomicPolicyService::STATE_INSUFFICIENT_BALANCE);
        $this->assertSame('BLOCKED_FROM_ORDERS', DriverEconomicPolicyService::STATE_BLOCKED_FROM_ORDERS);

        $this->assertSame('PROMOTIONAL_CREDIT', DriverEconomicPolicyService::TRX_PROMOTIONAL_CREDIT);
        $this->assertSame('RECHARGE', DriverEconomicPolicyService::TRX_RECHARGE);
        $this->assertSame('CONSUMPTION', DriverEconomicPolicyService::TRX_CONSUMPTION);
    }
}
