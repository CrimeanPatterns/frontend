<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use Symfony\Component\Routing\Router;

abstract class CreateItineraryFormCest extends BaseTraitCest
{
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '';
    public const EDIT_PATH = '';
    public const TABLE = '';
    public const ID_FIELD = '';
    public const RECORD_LOCATOR = '';
    public const REPOSITORY = '';

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var UsrRepository
     */
    private $userRepository;

    /**
     * @var UseragentRepository
     */
    private $useragentRepository;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->useragentRepository = $I->grabService('doctrine')->getRepository(Useragent::class);
    }

    public function displayForm(\TestSymfonyGuy $I)
    {
        $I->amOnPage(static::ADD_PATH);
        $I->seeResponseCodeIs(200);
        $I->see('Add');
        $I->seeElement('form');
        $I->seeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());
    }

    public function displayFormWithUserAgent(\TestSymfonyGuy $I)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->user->getId(), 'Family', 'Member'));
        /** @var Useragent $user2FamilyMember */
        $user2FamilyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($user2->getId(), 'User2Family', 'Member'));
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getId(), $this->user->getId(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL])
        );
        $I->shareAwTimeline($user2->getId(), null, $this->user->getId());
        $I->shareAwTimeline($user2->getId(), $user2FamilyMember->getId(), $this->user->getId());

        $I->wantToTest("User agent is a connection");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $connection->getId());
        $I->seeOptionIsSelected('Travel Timeline Of', $user2->getFullName());

        $I->wantToTest("User agent is a family member");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $familyMember->getId());
        $I->seeOptionIsSelected('Travel Timeline Of',
            $familyMember->getFullName()
            . ' (' . $this->user->getFullName() . ')'
        );

        $I->wantToTest("User agent is a family member of a connected user");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $user2FamilyMember->getId());
        $I->seeOptionIsSelected('Travel Timeline Of',
            $user2FamilyMember->getFullName()
            . ' (' . $user2->getFullName() . ')'
        );
    }

    /**
     * @dataprovider permissionsProvider
     */
    public function displayFormForDifferentPermissions(\TestSymfonyGuy $I, Example $permissions)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getId(), $this->user->getId(), $permissions['approved'], true, ['AccessLevel' => $permissions['level'], 'TripAccessLevel' => $permissions['tripLevel']])
        );
        $I->shareAwTimeline($user2->getId(), null, $this->user->getId());

        $I->amOnPage(static::ADD_PATH . "?agentId=" . $connection->getId());

        if ($permissions['successExpectancy']) {
            $I->seeResponseCodeIs(200);
        } else {
            $I->seeResponseCodeIs(403);
        }
    }

    public function addToSelf(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $recordLocator = $this->sendForm($I);
        $I->seeResponseCodeIs(302);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->findOneBy(["confirmationNumber" => $recordLocator]);
        $I->assertNotNull($itinerary);
        $I->assertFalse($itinerary->getHidden());
        $I->assertFalse($itinerary->getParsed());
        $I->assertEquals($this->user->getId(), $itinerary->getUser()->getId());
        $I->assertEquals('some notes', $itinerary->getNotes());
        $I->assertFalse($itinerary->getMoved());
        $I->assertEqualsWithDelta(new \DateTime(), $itinerary->getUpdateDate(), 10);
        $I->assertEqualsWithDelta(new \DateTime(), $itinerary->getCreateDate(), 10);
        $I->assertFalse($itinerary->getCancelled());
        $I->assertNull($itinerary->getUseragent());
        $I->assertTrue($itinerary->getModified());
        $this->assertRedirect($I, $itinerary);

        return $itinerary;
    }

    public function addToConnected(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        /** @var Useragent $connection */
        $connection = $this->useragentRepository->find(
            $I->createConnection($user2->getId(), $this->user->getId(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => Useragent::TRIP_ACCESS_FULL_CONTROL])
        );
        $I->shareAwTimeline($user2->getId(), null, $this->user->getId());
        $recordLocator = $this->sendForm($I, $connection, $user2->getId());
        $I->seeResponseCodeIs(302);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertNotNull($itinerary);
        $I->assertEquals($user2->getId(), $itinerary->getUser()->getId());
        $I->assertNull($itinerary->getUseragent());
        $this->assertRedirect($I, $itinerary);
    }

    public function addToFamilyMember(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->user->getId(), "Family", "Member"));
        $recordLocator = $this->sendForm($I, $familyMember);
        $I->seeResponseCodeIs(302);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertNotNull($itinerary);
        $I->assertEquals($this->user->getId(), $itinerary->getUser()->getId());
        $I->assertEquals($familyMember->getId(), $itinerary->getUseragent()->getId());
        $this->assertRedirect($I, $itinerary);
    }

    public function addToFamilyMemberOfAConnected(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        $this->useragentRepository->find(
            $I->createConnection($user2->getId(), $this->user->getId(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => Useragent::TRIP_ACCESS_FULL_CONTROL])
        );
        /** @var Useragent $familyMember */
        $familyMember = $this->useragentRepository->find($I->createFamilyMember($user2->getId(), 'Family', 'Member'));
        $I->shareAwTimeline($user2->getId(), $familyMember->getId(), $this->user->getId());
        $recordLocator = $this->sendForm($I, $familyMember);
        $I->seeResponseCodeIs(302);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertNotNull($itinerary);
        $I->assertEquals($user2->getId(), $itinerary->getUser()->getId());
        $I->assertEquals($familyMember->getId(), $itinerary->getUseragent()->getId());
        $this->assertRedirect($I, $itinerary);
    }

    /**
     * @dataprovider permissionsProvider
     */
    public function addForDifferentPermissions(\TestSymfonyGuy $I, Example $permissions)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        /** @var Useragent $connection */
        $connection = $this->useragentRepository->find(
            $I->createConnection($user2->getId(), $this->user->getId(), $permissions['approved'], true, ['AccessLevel' => $permissions['level'], 'TripAccessLevel' => $permissions['tripLevel']])
        );
        $I->shareAwTimeline($user2->getId(), null, $this->user->getId());
        $I->amOnPage(static::ADD_PATH);
        $found = true;

        try {
            $I->selectOption('Travel Timeline Of', $connection->getFullName());
        } catch (\InvalidArgumentException $e) {
            $found = false;
        }

        if ($found) {
            $message = $connection->getFullName() . " was found";
        } else {
            $message = $connection->getFullName() . " was not found";
        }
        $I->assertEquals($permissions['successExpectancy'], $found, $message);
    }

    abstract public function checkErrors(\TestSymfonyGuy $I);

    /**
     * @return string Record Locator
     */
    protected function sendForm(\TestSymfonyGuy $I, ?Useragent $userAgent = null, ?string $selectorOption = null)
    {
        $I->amOnPage(static::ADD_PATH);

        if (null !== $userAgent) {
            $I->selectOption('Travel Timeline Of', $selectorOption ?? $userAgent->getAgentid()->getId() . '_' . $userAgent->getId());
        }
        $recordLocator = $I->grabRandomString(3);
        $this->doSubmitForm($I, $recordLocator);

        return $recordLocator;
    }

    abstract protected function doSubmitForm(\TestSymfonyGuy $I, string $recordLocator);

    abstract protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary);

    private function permissionsProvider()
    {
        return [
            // Not approved
            ['tripLevel' => Useragent::TRIP_ACCESS_FULL_CONTROL,
                'level' => Useragent::ACCESS_WRITE,
                'approved' => false,
                'successExpectancy' => false,
            ],
            // Read only
            ['tripLevel' => Useragent::TRIP_ACCESS_READ_ONLY,
                'level' => Useragent::ACCESS_WRITE,
                'approved' => true,
                'successExpectancy' => false,
            ],
        ];
    }
}
