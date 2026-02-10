<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
abstract class CreateItineraryBusinessCest extends BaseTraitCest
{
    use StaffUser;
    use LoggedIn;

    public const ADD_PATH = '';
    public const FORM_NAME = '';
    public const TABLE = '';
    public const ID_FIELD = '';
    public const RECORD_LOCATOR = '';
    public const REPOSITORY = '';

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Usr
     */
    private $business;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->business = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createBusinessUserWithBookerInfo());
        $I->connectUserWithBusiness($this->user->getUserid(), $this->business->getUserid(), Useragent::ACCESS_ADMIN);
        $I->amOnBusiness();
    }

    public function displayForm(\TestSymfonyGuy $I)
    {
        $I->amOnPage(static::ADD_PATH);
        $I->seeResponseCodeIs(200);
        $I->see('Add');
        $I->seeElement('form');
        $I->seeInFormFields("form", [static::FORM_NAME . '[owner]' => ""]);
    }

    public function displayFormWithUserAgent(\TestSymfonyGuy $I)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->business->getUserid(), 'Family', 'Member'));
        /** @var Useragent $user2FamilyMember */
        $user2FamilyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($user2->getUserId(), 'User2Family', 'Member'));
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserId(), $this->business->getUserid(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL])
        );
        $I->shareAwTimeline($user2->getUserId(), null, $this->business->getUserid());
        $I->shareAwTimeline($user2->getUserId(), $user2FamilyMember->getUseragentid(), $this->business->getUserid());

        $I->wantToTest("User agent is a connection");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $connection->getUseragentid());
        $I->seeInFormFields('form', [static::FORM_NAME . '[owner]' => $connection->getUseragentid()]);

        $I->wantToTest("User agent is a family member");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $familyMember->getUseragentid());
        $I->seeInFormFields('form', [static::FORM_NAME . '[owner]' => $familyMember->getUseragentid()]);

        $I->wantToTest("User agent is a family member of a connected user");
        $I->amOnPage(static::ADD_PATH . "?agentId=" . $user2FamilyMember->getUseragentid());
        $I->seeInFormFields('form', [static::FORM_NAME . '[owner]' => $user2FamilyMember->getUseragentid()]);
    }

    /**
     * @dataprovider permissionsProvider
     */
    public function displayFormForDifferentPermissions(\TestSymfonyGuy $I, Example $permissions)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserId(), $this->business->getUserid(), $permissions['approved'], true, ['AccessLevel' => $permissions['level'], 'TripAccessLevel' => $permissions['tripLevel']])
        );
        $I->shareAwTimeline($user2->getUserId(), null, $this->business->getUserid());

        $I->amOnPage(static::ADD_PATH . "?agentId=" . $connection->getUseragentid());

        if ($permissions['successExpectancy']) {
            $I->seeResponseCodeIs(200);
        } else {
            $I->seeResponseCodeIs(403);
        }
    }

    /**
     * @group curldriver
     */
    public function addToBusiness(\TestSymfonyGuy $I)
    {
        $recordLocator = $this->sendForm($I);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Trip $trip */
        $trip = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertEquals($this->business->getUserid(), $trip->getUser()->getUserid());
        $I->assertNull($trip->getUseragentid());
    }

    public function addToConnected(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserid(), $this->business->getUserid(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => Useragent::TRIP_ACCESS_FULL_CONTROL])
        );
        $I->shareAwTimeline($user2->getUserid(), null, $this->business->getUserid());
        $recordLocator = $this->sendForm($I, $connection);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertEquals($user2->getUserid(), $itinerary->getUserid()->getUserid());
        $I->assertNull($itinerary->getUseragentid());
        $this->assertRedirect($I, $itinerary);
    }

    public function addToFamilyMember(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->business->getUserid(), "Family", "Member"));
        $recordLocator = $this->sendForm($I, $familyMember);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertEquals($this->business->getUserid(), $itinerary->getUser()->getUserid());
        $I->assertEquals($familyMember->getUseragentid(), $itinerary->getUseragentid()->getUseragentid());
        $this->assertRedirect($I, $itinerary);
    }

    public function addToFamilyMemberOfAConnected(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserid(), $this->business->getUserid(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => Useragent::TRIP_ACCESS_FULL_CONTROL])
        );
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($user2->getUserid(), 'Family', 'Member'));
        $I->shareAwTimeline($user2->getUserid(), $familyMember->getUseragentid(), $this->business->getUserid());
        $recordLocator = $this->sendForm($I, $familyMember);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        /** @var Itinerary $itinerary */
        $itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($I->grabFromDatabase(static::TABLE, static::ID_FIELD, [static::RECORD_LOCATOR => $recordLocator]));
        $I->assertEquals($user2->getUserid(), $itinerary->getUserid()->getUserid());
        $I->assertEquals($familyMember->getUseragentid(), $itinerary->getUseragentid()->getUseragentid());
        $this->assertRedirect($I, $itinerary);
    }

    /**
     * @dataprovider permissionsProvider
     */
    public function addForDifferentPermissions(\TestSymfonyGuy $I, Example $permissions)
    {
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserid(), $this->business->getUserid(), $permissions['approved'], true, ['AccessLevel' => $permissions['level'], 'TripAccessLevel' => $permissions['tripLevel']])
        );
        $I->shareAwTimeline($user2->getUserid(), null, $this->business->getUserid());
        $I->amOnPage(static::ADD_PATH);
        $recordLocator = $this->sendForm($I, $connection);

        if ($permissions['successExpectancy']) {
            $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        } else {
            $I->dontSeeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        }
    }

    public function addToNonExistent(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(static::ADD_PATH);
        $recordLocator = $I->grabRandomString(3);
        $I->submitForm('form', $this->getFields($recordLocator, '-1'));
        $I->dontSeeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        $I->see("Travel Timeline Of");
    }

    public function addToArbitrary(\TestSymfonyGuy $I)
    {
        $userA = $I->createAwUser();
        $userB = $I->createAwUser();
        $ABConnection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($userA, $userB, true, true, ['TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL])
        );
        $recordLocator = $this->sendForm($I, $ABConnection);
        $I->dontSeeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $recordLocator]);
        $I->see("Travel Timeline Of");
    }

    abstract protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary);

    abstract protected function getFields(string $recordLocator, string $owner);

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

    /**
     * @return string
     */
    private function sendForm(\TestSymfonyGuy $I, ?Useragent $userAgent = null)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage(static::ADD_PATH);
        $recordLocator = $I->grabRandomString(3);
        $I->submitForm('form', $this->getFields($recordLocator, null !== $userAgent ? $userAgent->getUseragentid() : ''));

        return $recordLocator;
    }
}
