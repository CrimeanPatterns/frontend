<?php

namespace AwardWallet\Tests\Unit\BusinessTransaction;

/**
 * @group frontend-unit
 * @group billing
 */
class BillTest extends AbstractTest
{
    public function testTrial()
    {
        $this->info->setTrialEndDate(new \DateTime("+1 day"))->setBalance(0);
        $this->assertTrue($this->info->isTrial());
        $this->assertEquals(0, $this->manager->billMonth($this->business));

        $this->assertEmpty($this->getLastTransaction());
        $this->assertEquals(0, $this->info->getBalance());

        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'PaidUntilDate' => null,
        ]);
    }

    public function testTrialEnd()
    {
        $this->info->setTrialEndDate(new \DateTime("2030-01-15"))->setBalance(0);

        $this->assertTrue($this->info->isTrial());
        $this->assertEquals(0, $this->manager->billMonth($this->business));
        $this->assertEquals(0, $this->info->getBalance());

        $this->manager->addPayment($this->business, 10, false);
        $this->assertFalse($this->info->isTrial());
        $this->assertEquals(10, $this->info->getBalance());
        $this->assertEmpty($this->db->grabFromDatabase("BusinessInfo", "PaidUntilDate", ["UserID" => $this->business->getUserid()]));

        $this->manager->billMonth($this->business);
        $this->assertEquals(6, $this->info->getBalance());
        $this->assertNotEmpty($this->db->grabFromDatabase("BusinessInfo", "PaidUntilDate", ["UserID" => $this->business->getUserid()]));
    }

    public function testBalanceIsZero()
    {
        $this->info->setTrialEndDate(null)->setBalance(0);
        $this->assertFalse($this->info->isTrial());
        $this->assertEquals(0, $this->manager->billMonth($this->business));
        $this->assertEmpty($lastTransaction = $this->getLastTransaction());
    }

    public function testBalanceIsNotEnough()
    {
        $this->info->setBalance(0.2);
        $this->assertEquals(0, $this->manager->billMonth($this->business));
    }

    public function testDiscountHundred()
    {
        $this->info->setDiscount(100)->setBalance(0.2);
        $this->assertEquals(1, $this->manager->billMonth($this->business));
        $this->assertNotEmpty($lastTransaction = $this->getLastTransaction());
        $this->assertEquals(0, $lastTransaction->getAmount());
        $this->assertEquals(0.2, $this->info->getBalance());
        $this->assertEquals($this->info->getBalance(), $lastTransaction->getBalance());
    }

    public function testDiscountTen()
    {
        $this->info->setBalance(10)->setDiscount(20);
        $this->assertFalse($this->info->isTrial());
        $this->assertEquals(1, $this->manager->billMonth($this->business));
        $this->assertNotEmpty($lastTransaction = $this->getLastTransaction());
        $this->assertEquals(3.2, $lastTransaction->getAmount());
        $this->assertEquals(6.8, $lastTransaction->getBalance());
        $this->assertEquals(6.8, $this->info->getBalance());
    }

    public function testDiscountZero()
    {
        $this->info->setBalance(10)->setDiscount(0);
        $this->assertFalse($this->info->isTrial());
        $this->assertEquals(1, $this->manager->billMonth($this->business));
        $this->assertNotEmpty($lastTransaction = $this->getLastTransaction());
        $this->assertEquals(4, $lastTransaction->getAmount());
        $this->assertEquals(6, $lastTransaction->getBalance());
        $this->assertEquals(6, $this->info->getBalance());
    }

    public function testPartialMonth()
    {
        $this->info->setBalance(10)->setDiscount(0);
        $this->assertFalse($this->info->isTrial());
        $this->assertEquals(1, $this->manager->billMonth($this->business, new \DateTime("2030-01-10")));
        $this->assertNotEmpty($lastTransaction = $this->getLastTransaction());
        $this->assertEquals(2.84, $lastTransaction->getAmount());
        $this->assertEquals(7.16, $lastTransaction->getBalance());
        $this->assertEquals(7.16, $this->info->getBalance());
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'PaidUntilDate' => '2030-01-31',
        ]);

        $this->assertEquals(0, $this->manager->billMonth($this->business, new \DateTime("2030-01-12")));
        $this->assertEquals(7.16, $this->info->getBalance());
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'PaidUntilDate' => '2030-01-31',
        ]);
    }
}
