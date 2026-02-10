<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\Unit\BaseUserTest;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-unit
 */
class UserAgentVoterTest extends BaseUserTest
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * @var Usr
     */
    private $client;

    /**
     * @var Useragent
     */
    private $familyMemberOfClient;

    public function _before()
    {
        parent::_before();
        $this->authChecker = $this->container->get("security.authorization_checker");
        $this->client = $this->container->get('doctrine')->getRepository(Usr::class)->find(
            $this->aw->createAwUser()
        );
        $this->familyMemberOfClient = $this->container->get('doctrine')->getRepository(Useragent::class)->find(
            $this->aw->createFamilyMember($this->client->getUserid(), "Bill", "Gilbert")
        );
    }

    /**
     * @dataProvider editAccountsDataProvider
     * @param bool $directApproved
     * @param int $directAccessLevel
     * @param bool $haveBackConn
     * @param bool $backApproved
     * @param int $backAccessLevel
     * @param bool $granted
     */
    public function testEditAccounts($directApproved, $directAccessLevel, $haveBackConn, $backApproved, $backAccessLevel, $granted)
    {
        $directConn = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find(
            $this->aw->createConnection($this->client->getUserid(), $this->user->getUserid(), $directApproved, null, ['AccessLevel' => $directAccessLevel])
        );

        if ($haveBackConn) {
            $this->aw->createConnection($this->user->getUserid(), $this->client->getUserid(), $backApproved, null, ['AccessLevel' => $backAccessLevel]);
        }

        $this->assertEquals($granted, $this->authChecker->isGranted('EDIT_ACCOUNTS', $directConn));
        $this->assertEquals($granted, $this->authChecker->isGranted('EDIT_ACCOUNTS', $this->familyMemberOfClient));
    }

    public function editAccountsDataProvider()
    {
        return [
            // directApproved,      directAccessLevel,      haveBackConn,       backApproved,       backAccessLevel,        granted
            [false,               ACCESS_WRITE,           false,              false,              ACCESS_WRITE,           false],
            [true,                ACCESS_WRITE,           false,              false,              ACCESS_WRITE,           false],
            [true,                ACCESS_WRITE,           true,               false,              ACCESS_WRITE,           false],
            [true,                ACCESS_WRITE,           true,               true,               ACCESS_WRITE,           true],
            [true,                ACCESS_READ_ALL,        true,               true,               ACCESS_WRITE,           false],
        ];
    }
}
