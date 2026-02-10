<?php

namespace AwardWallet\Tests\Unit\BusinessTransaction;

/**
 * @group frontend-unit
 * @group billing
 */
class RecommendedPaymentTest extends AbstractTest
{
    public function testOneMember()
    {
        $this->assertEquals(10, $this->manager->getRecommendedPayment($this->business));
    }

    public function test15Members()
    {
        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        for ($n = 0; $n < 14; $n++) {
            $uaRep->find($this->aw->createFamilyMember($this->business->getUserid(), 'Test', 'Testov'));
        }

        $this->assertEquals(60, $this->manager->getRecommendedPayment($this->business));
    }

    public function testDiscount100()
    {
        $this->business->getBusinessInfo()->setDiscount(100);
        $this->assertEquals(0, $this->manager->getRecommendedPayment($this->business));
    }
}
