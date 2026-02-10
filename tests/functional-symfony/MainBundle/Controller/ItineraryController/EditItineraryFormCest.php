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
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
abstract class EditItineraryFormCest extends BaseTraitCest
{
    use StaffUser;
    use LoggedIn;

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
    protected $userRepository;

    /**
     * @var UseragentRepository
     */
    protected $useragentRepository;

    /**
     * @var Itinerary
     */
    protected $itinerary;

    /**
     * @var string
     */
    protected $testRecordLocator;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->useragentRepository = $I->grabService('doctrine')->getRepository(Useragent::class);
        $this->testRecordLocator = $I->grabRandomString(25);
        $itineraryId = $this->createItineraryRow($I);
        $this->itinerary = $I->grabService('doctrine')->getRepository(static::REPOSITORY)->find($itineraryId);
    }

    public function displayForm(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->seeResponseCodeIs(200);
        $I->see('Edit');
        $I->seeElement('form');
        $I->seeInFormFields('form', $this->getFields());
        $I->seeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());
    }

    public function changeOwner(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        /** @var Usr $user2 */
        $user2 = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['FirstName' => 'User2']));
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->user->getUserid(), 'Family', 'Member'));
        /** @var Useragent $user2FamilyMember */
        $user2FamilyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($user2->getUserId(), 'User2Family', 'Member'));
        /** @var Useragent $connection */
        $connection = $I->grabService('doctrine')->getRepository(Useragent::class)->find(
            $I->createConnection($user2->getUserId(), $this->user->getUserid(), true, true, ['AccessLevel' => ACCESS_WRITE, 'TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL])
        );
        $I->shareAwTimeline($user2->getUserId(), null, $this->user->getUserid());
        $I->shareAwTimeline($user2->getUserId(), $user2FamilyMember->getUseragentid(), $this->user->getUserid());

        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->canSeeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());
        $I->selectOption('Travel Timeline Of', $user2->getFullName());
        $I->submitForm('form', []);
        $this->assertRedirect($I, $this->itinerary);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $this->testRecordLocator, 'UserID' => $user2->getUserId(), 'UserAgentID' => null]);

        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->canSeeOptionIsSelected('Travel Timeline Of', $user2->getFullName());
        $I->selectOption('Travel Timeline Of', $familyMember->getFullName()
            . ' (' . $this->user->getFullName() . ')'
        );
        $I->submitForm('form', []);
        $this->assertRedirect($I, $this->itinerary);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $this->testRecordLocator, 'UserID' => $this->user->getUserid(), 'UserAgentID' => $familyMember->getUseragentid()]);

        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->canSeeOptionIsSelected('Travel Timeline Of', $familyMember->getFullName()
            . ' (' . $this->user->getFullName() . ')'
        );
        $I->selectOption('Travel Timeline Of', $user2FamilyMember->getFullName()
            . ' (' . $user2->getFullName() . ')'
        );
        $I->submitForm('form', []);
        $this->assertRedirect($I, $this->itinerary);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $this->testRecordLocator, 'UserID' => $user2->getUserid(), 'UserAgentID' => $user2FamilyMember->getUseragentid()]);

        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->canSeeOptionIsSelected('Travel Timeline Of', $user2FamilyMember->getFullName()
            . ' (' . $user2->getFullName() . ')'
        );
        $I->selectOption('Travel Timeline Of', $this->user->getFullName());
        $I->submitForm('form', []);
        $this->assertRedirect($I, $this->itinerary);
        $I->seeInDatabase(static::TABLE, [static::RECORD_LOCATOR => $this->testRecordLocator, 'UserID' => $this->user->getUserid(), 'UserAgentID' => null]);
    }

    public function moveAndOverwrite(\TestSymfonyGuy $I)
    {
        /** @var Useragent $familyMember */
        $familyMember = $I->grabService('doctrine')->getRepository(Useragent::class)->find($I->createFamilyMember($this->user->getUserid(), 'Family', 'Member'));
        $fmItineraryId = $this->createItineraryRow($I);
        $accountId = $I->createAwAccount($this->user->getUserid(), "testprovider", "some");
        $I->executeQuery("update " . static::TABLE . " 
            set UserAgentID = " . $familyMember->getUseragentid() . ", AccountID = $accountId
            where " . static::TABLE . "ID = {$fmItineraryId}");
        $I->executeQuery("update " . static::TABLE . " 
            set AccountID = $accountId
            where " . static::TABLE . "ID = {$this->itinerary->getId()}");

        $I->stopFollowingRedirects();
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->selectOption('Travel Timeline Of', $familyMember->getFullName()
            . ' (' . $this->user->getFullName() . ')'
        );
        $I->submitForm('form', []);
        $this->assertRedirect($I, $this->itinerary);
        $I->assertEquals($familyMember->getUseragentid(), $I->grabFromDatabase(static::TABLE, "UserAgentID", [static::TABLE . 'ID' => $this->itinerary->getId()]));
        $I->dontSeeInDatabase(static::TABLE, [static::ID_FIELD => $fmItineraryId]);
    }

    public function editSegment(\TestSymfonyGuy $I)
    {
        $I->stopFollowingRedirects();
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $newRecordLocator = $I->grabRandomString(25);
        $newFields = $this->getNewFields($newRecordLocator);
        $I->submitForm('form', $newFields);
        $this->assertRedirect($I, $this->itinerary);
        $I->amOnPage($this->getEditPath($this->itinerary->getId()));
        $I->seeInFormFields('form', $newFields);
        $I->seeInDatabase(static::TABLE, [
            static::ID_FIELD => $this->itinerary->getId(),
            static::RECORD_LOCATOR => $newRecordLocator,
            'UserId' => $this->user->getUserid(),
            'UserAgentId' => null,
            'notes' => 'some other notes',
        ]);
        $I->seeOptionIsSelected('Travel Timeline Of', $this->user->getFullName());

        return $newRecordLocator;
    }

    abstract protected function createItineraryRow(\TestSymfonyGuy $I);

    abstract protected function getFields();

    abstract protected function getNewFields(string $recordLocator);

    protected function getEditPath(int $id)
    {
        return str_replace('{id}', $id, static::EDIT_PATH);
    }

    abstract protected function assertRedirect(\TestSymfonyGuy $I, Itinerary $itinerary);
}
