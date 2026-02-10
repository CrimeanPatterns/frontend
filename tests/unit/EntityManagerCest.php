<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Badword;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManagerInterface;

class EntityManagerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testRemove(\CodeGuy $I)
    {
        /** @var EntityManagerInterface $em */
        $em = $I->grabService("doctrine.orm.entity_manager");
        $bw = new Badword();
        $bw->setWord(StringUtils::getRandomCode(20));
        codecept_debug("new: " . $bw->getBadwordid() . ': ' . $em->getUnitOfWork()->getEntityState($bw));
        $em->persist($bw);
        codecept_debug("persisted: " . $bw->getBadwordid() . ': ' . $em->getUnitOfWork()->getEntityState($bw));
        $em->flush();
        codecept_debug("flushed: " . $bw->getBadwordid() . ': ' . $em->getUnitOfWork()->getEntityState($bw));
        $em->remove($bw);
        codecept_debug("removed: " . $bw->getBadwordid() . ': ' . $em->getUnitOfWork()->getEntityState($bw));
        $em->flush();
        codecept_debug("flushed: " . $bw->getBadwordid() . ': ' . $em->getUnitOfWork()->getEntityState($bw));
    }
}
