<?php

namespace AwardWallet\Tests\Unit\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Module\Aw;
use Codeception\Module\Mail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Manager\UserManager
 */
class UserManagerWithContainerTest extends BaseContainerTest
{
    /**
     * @var Usr
     */
    protected $user;

    public function _before()
    {
        parent::_before();

        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true);
        $this->user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
    }

    public function _after()
    {
        $this->user = null;

        parent::_after();
    }

    public function testFreeUpgradeCouponFor5Users()
    {
        $inviter = $this->user;
        /** @var UserManager $manager */
        $manager = $this->container->get(UserManager::class);

        for ($i = 0; $i < 5; $i++) {
            $request = new Request();
            $session = new Session(new MockArraySessionStorage());
            $session->set('inviterId', $inviter->getUserid());
            $request->setSession($session);

            $invitee = $this->getFakeNewUser('register_test_' . $i);

            $this->em->persist($invitee);
            $this->em->flush();

            $manager->registerUser($invitee, $request);
        }

        assertEquals(1, $this->em->getRepository(\AwardWallet\MainBundle\Entity\Coupon::class)->getInviteCouponByUser($inviter));
    }

    public function testFakeUsersFromSameIpCheck()
    {
        $this->mockAppBot();
        $this->mockGeoLocation();
        /** @var UserManager $manager */
        $manager = $this->container->get(UserManager::class);
        [$request, $seeEmail, $dontSeeEmail] = $this->provideFakeUserTestEnv();
        // allowed registrations
        $manager->registerUser($fakeUser1 = $this->getFakeNewUser('fakeuser_test1'), $request);
        $manager->registerUser($fakeUser2 = $this->getFakeNewUser('fakeuser_test2'), $request);
        // $dontSeeEmail();

        // register disallowed
        $manager->registerUser($fakeUser3 = $this->getFakeNewUser('fakeuser_test3'), $request);
        // $seeEmail($this->getUserIdsList([$fakeUser1, $fakeUser2, $fakeUser3]));
    }

    public function testFakeUsersWithAccounts()
    {
        $this->mockAppBot();
        $this->mockGeoLocation();
        /** @var UserManager $manager */
        $manager = $this->container->get(UserManager::class);
        [$request, $seeEmail, $dontSeeEmail] = $this->provideFakeUserTestEnv();
        // allowed registrations
        $manager->registerUser($fakeUser1 = $this->getFakeNewUser('fakeuser_test1'), $request);
        $manager->registerUser($fakeUser2 = $this->getFakeNewUser('fakeuser_test2'), $request);
        $this->aw->createAwAccount($fakeUser2->getUserid(), 'testprovider', 'balance.random');
        // $dontSeeEmail();

        // register allowd second fake user has account
        $manager->registerUser($fakeUser3 = $this->getFakeNewUser('fakeuser_test3'), $request);
        // $dontSeeEmail();

        // register disallowed
        $manager->registerUser($fakeUser4 = $this->getFakeNewUser('fakeuser_test4'), $request);
        // $seeEmail($this->getUserIdsList([$fakeUser1, $fakeUser3, $fakeUser4]));
    }

    public function testRegisterWithInvitedEmail()
    {
        $businessId = $this->aw->createBusinessUserWithBookerInfo();
        $staffId = $this->aw->createStaffUserForBusinessUser($businessId);
        $user = $this->getFakeNewUser('user');
        $accountId = $this->aw->createAwAccount($businessId, "testprovider", "balance.random");
        $code = StringUtils::getRandomCode(20);
        $this->db->haveInDatabase("InviteCode", ["UserID" => $businessId, "Code" => $code, "CreationDate" => date("Y-m-d H:i:s"), "Email" => $user->getEmail(), "Source" => "*"]);
        $this->db->haveInDatabase("Invites", ["InviterID" => $businessId, "Email" => $user->getEmail(), "inviteDate" => date("Y-m-d H:i:s"), "Code" => $code, "Approved" => 0]);

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        /** @var UserManager $manager */
        $manager = $this->container->get(UserManager::class);
        $manager->registerUser($user, $request);

        $this->assertEquals($businessId, $this->db->grabFromDatabase("Account", "UserID", ["AccountID" => $accountId]));
    }

    public function testInvitedFamilyMember()
    {
        $inviter = $this->user;
        /** @var UserManager $manager */
        $manager = $this->container->get(UserManager::class);
        $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $timelineShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);

        // add connected user + share timeline
        /** @var Usr $connectedUser */
        $connectedUser = $userRep->find(
            $this->aw->createAwUser('connected-' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [])
        );
        /** @var Useragent $connectedUa */
        $connectedUa = $uaRep->find($this->aw->createConnection($connectedUser->getId(), $inviter->getId(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
        ]));
        $this->aw->createConnection($inviter->getId(), $connectedUser->getId(), true, true, [
            "AccessLevel" => ACCESS_ADMIN,
        ]);
        /** @var Useragent $familyMember */
        $familyMember = $uaRep->find($this->aw->createFamilyMember($inviter->getId(), 'Test', 'Testoff', null, $email = 'test@mail.com'));
        $timelineShareRep->addTimelineShare($connectedUa, $familyMember);

        // invite
        $code = StringUtils::getRandomCode(20);
        $this->db->haveInDatabase("InviteCode", [
            "UserID" => $inviter->getId(),
            "Code" => $code,
            "CreationDate" => date("Y-m-d H:i:s"),
            "Email" => $email,
            "Source" => "*",
        ]);
        $invId = $this->db->haveInDatabase("Invites", [
            "InviterID" => $inviter->getId(),
            "UserAgentID" => $familyMember->getId(),
            "Email" => $email,
            "inviteDate" => date("Y-m-d H:i:s"),
            "Code" => $code,
            "Approved" => 0,
        ]);

        $this->db->seeInDatabase('TimelineShare', [
            'UserAgentID' => $connectedUa->getId(),
            'TimelineOwnerID' => $connectedUa->getClientid()->getId(),
            'FamilyMemberID' => $familyMember->getId(),
            'RecipientUserID' => $connectedUa->getAgentid()->getId(),
        ]);

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('invId', $invId);
        $session->set('inviterId', $inviter->getId());
        $session->set('InviteCode', $code);
        $request->setSession($session);
        $invitee = $this->getFakeNewUser('register_fm');

        $this->em->persist($invitee);
        $this->em->flush();

        $manager->registerUser($invitee, $request);
        $this->db->seeInDatabase('UserAgent', [
            'UserAgentID' => $familyMember->getId(),
            'ClientID' => $invitee->getId(),
        ]);
        $this->db->dontSeeInDatabase('TimelineShare', [
            'UserAgentID' => $connectedUa->getId(),
            'FamilyMemberID' => $familyMember->getId(),
        ]);
    }

    /**
     * @param Usr[] $users
     */
    protected function getUserIdsList(array $users)
    {
        $users = array_map(function ($user) { return $user->getUserid(); }, $users);
        sort($users);

        return implode(', ', $users);
    }

    protected function provideFakeUserTestEnv()
    {
        $expectedMailTo = ConfigValue(CONFIG_ERROR_EMAIL);
        $expectedMailSubject = "Fake IDs should be deleted";

        /** @var Mail $mailModule */
        $mailModule = $this->getModule('Mail');
        $inviter = $this->user;

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('inviterId', $inviter->getUserid());
        $session->set('ref', 4);
        $request->setSession($session);
        $request->server->set('REMOTE_ADDR', implode('.', array_pad([], 4, rand(255, 999))));

        $asserEmailProvider = function ($checkMethod) use ($mailModule, $expectedMailSubject, $expectedMailTo) {
            return function ($body = null) use ($mailModule, $expectedMailSubject, $expectedMailTo, $checkMethod) {
                $mailModule->$checkMethod($expectedMailTo, $expectedMailSubject, $body);
            };
        };

        return [$request, $asserEmailProvider('seeEmailTo'), $asserEmailProvider('dontSeeEmailTo')];
    }

    protected function getFakeNewUser($loginPrefix)
    {
        return (new Usr())
            ->setLogin($login = $loginPrefix . '_' . StringHandler::getRandomString(ord('a'), ord('z'), 14))
            ->setEmail($login . '@aw.com')
            ->setFirstname('Ragnar')
            ->setLastname('Lodbrok');
    }

    private function mockAppBot(): void
    {
        $appBot = $this->createMock(AppBot::class);
        $appBot
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (string $channelName, string $message) {
                $this->assertStringContainsString('fake_ids', $channelName);
                $this->assertStringContainsString('Potentially fake IDs', $message);
            });
        $this->mockService(AppBot::class, $appBot);
    }

    private function mockGeoLocation(): void
    {
        $this->mockServiceWithBuilder(GeoLocation::class)->method('getCountryIdByIp')->willReturn(null);
    }
}
