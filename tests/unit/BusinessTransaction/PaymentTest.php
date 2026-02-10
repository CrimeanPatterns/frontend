<?php

namespace AwardWallet\Tests\Unit\BusinessTransaction;

/**
 * @group frontend-unit
 * @group billing
 */
class PaymentTest extends AbstractTest
{
    public function testPayment()
    {
        $this->info->setBalance(30)->setDiscount(10);

        $this->assertTrue($this->manager->addPayment($this->business, 100, false));
        $this->assertNotEmpty($lastTransaction = $this->getLastTransaction());
        $this->assertEquals(100, $lastTransaction->getAmount());
        $this->assertEquals(130, $lastTransaction->getBalance());
        $this->assertEquals(130, $this->info->getBalance());
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->info->getUser()->getUserid(),
            'Balance' => 130,
        ]);
    }
}
