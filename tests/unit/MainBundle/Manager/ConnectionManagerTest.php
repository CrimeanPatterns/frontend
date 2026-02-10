<?php

namespace AwardWallet\Tests\Unit\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class ConnectionManagerTest extends BaseUserTest
{
    /**
     * @var ConnectionManager
     */
    private $manager;

    /**
     * @var UsrRepository
     */
    private $userRep;

    /**
     * @var UseragentRepository
     */
    private $uaRep;

    /**
     * @var Usr
     */
    private $business;

    public function _before()
    {
        parent::_before();

        $this->manager = $this->container->get(ConnectionManager::class);
        $this->userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $this->business = $this->userRep->find(
            $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [
                'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
                'Company' => 'Oops',
            ])
        );
    }

    public function _after()
    {
        $this->manager = null;
        $this->userRep = null;
        $this->uaRep = null;
        $this->business = null;

        parent::_after();
    }

    public function testDeleteBusinessConnection()
    {
        /** @var Usr $secondAdmin */
        $secondAdmin = $this->userRep->find(
            $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD)
        );

        /** @var Useragent $firstAdminUa */
        $firstAdminUa = $this->uaRep->find($this->aw->connectUserWithBusiness($this->user->getUserid(), $this->business->getUserid(), ACCESS_ADMIN));
        /** @var Useragent $secondAdminUa */
        $secondAdminUa = $this->uaRep->find($this->aw->connectUserWithBusiness($secondAdmin->getUserid(), $this->business->getUserid(), ACCESS_ADMIN));
        /** @var Useragent $fm */
        $fm = $this->uaRep->find($this->aw->createFamilyMember($this->business->getUserid(), 'John', 'Petrov'));

        $this->assertEquals(2, $this->db->grabCountFromDatabase('UserAgent', ['ClientID' => $this->business->getUserid()]));
        $this->assertTrue($this->manager->denyConnection($firstAdminUa, $this->business));
        $this->assertFalse($this->manager->denyConnection($secondAdminUa, $this->business));
        $this->assertTrue($this->manager->denyConnection($fm, $this->business));
    }

    public function testDeletePersonalConnection()
    {
        // last admin
        /** @var Useragent $ua */
        $ua = $this->uaRep->find($this->aw->createConnection($this->user->getUserid(), $this->business->getUserid()));
        $this->aw->connectUserWithBusiness($this->user->getUserid(), $this->business->getUserid(), ACCESS_ADMIN);
        $this->assertFalse($this->manager->denyConnection($ua, $this->user));

        // connected user
        /** @var Usr $user */
        $user = $this->userRep->find($this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD));
        $ua = $this->uaRep->find($this->aw->createConnection($this->user->getUserid(), $user->getUserid()));
        $this->assertTrue($this->manager->denyConnection($ua, $this->user));

        // family member
        /** @var Useragent $fm */
        $fm = $this->uaRep->find($this->aw->createFamilyMember($this->user->getUserid(), 'John', 'Petrov'));
        $this->assertTrue($this->manager->denyConnection($fm, $this->user));
    }
}
