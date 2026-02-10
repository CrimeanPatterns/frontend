<?php

namespace AwardWallet\Tests\Unit\Entity;

use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class UserTest extends BaseContainerTest
{
    public function testGetConnectionWith()
    {
        $booker = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find(Aw::BOOKER_ID);
        $admin = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(["login" => "siteadmin"]);
        $connection = $booker->getConnectionWith($admin);
        $this->assertNotEmpty($connection);
        $this->assertEquals($booker->getUserid(), $connection->getAgentid()->getUserid());
        $this->assertEquals($admin->getUserid(), $connection->getClientid()->getUserid());
    }
}
