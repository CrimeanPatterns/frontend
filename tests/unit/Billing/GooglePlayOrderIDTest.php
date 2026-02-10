<?php

namespace AwardWallet\Tests\Unit\Billing;

use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 * @group billing
 * @group mobile
 * @group mobile/billing
 */
class GooglePlayOrderIDTest extends BaseTest
{
    /**
     * @dataProvider baseOrders
     */
    public function testGetBaseOrderId($nextOrderId, $baseOrderId)
    {
        $this->assertEquals($baseOrderId, Provider::getBaseOrderId($nextOrderId));
    }

    public function baseOrders()
    {
        return [
            ['', null],
            ['abc', null],
            ['GPA.1329-2554-4671-39291', 'GPA.1329-2554-4671-39291'],
            ['GPA.5432-3421-0099-11111..2', 'GPA.5432-3421-0099-11111'],
            ['GPA.1329-2554-4671-39291..0', 'GPA.1329-2554-4671-39291'],
            ['GPA.1329-2554-4671-39291..9', 'GPA.1329-2554-4671-39291'],
            ['GPA.1329-2554-4671', null],
        ];
    }

    /**
     * @dataProvider ordersHistory
     */
    public function testOrdersHistory($lastOrderId, $history)
    {
        $this->assertEquals($history, Provider::getOrdersHistory($lastOrderId));
    }

    public function ordersHistory()
    {
        return [
            ['', null],
            ['abc', null],
            ['GPA.1329-2554-4671-39291', ['GPA.1329-2554-4671-39291']],
            ['GPA.1329-2554-4671-39291..0', ['GPA.1329-2554-4671-39291', 'GPA.1329-2554-4671-39291..0']],
            ['GPA.1329-2554-4671-39291..1', ['GPA.1329-2554-4671-39291', 'GPA.1329-2554-4671-39291..0', 'GPA.1329-2554-4671-39291..1']],
            ['GPA.1329-2554-4671-39291..2', ['GPA.1329-2554-4671-39291', 'GPA.1329-2554-4671-39291..0', 'GPA.1329-2554-4671-39291..1', 'GPA.1329-2554-4671-39291..2']],
        ];
    }
}
