<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\Listener;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\ItineraryCheckError;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Listener\ItinerariesListener;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @group frontend-unit
 */
class ItinerariesListenerTest extends BaseContainerTest
{
    /** @var ItinerariesListener */
    private $itinerariesListener;

    private $userId;

    public function _before()
    {
        parent::_before();
        $this->userId = $this->aw->createAwUser($login = 'login' . StringUtils::getRandomCode(8), null, []);

        /** @var ContainerBuilder $containerBuilder */
        $repositories = [
            $this->em->getRepository(\AwardWallet\MainBundle\Entity\Trip::class),
            $this->em->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class),
            $this->em->getRepository(\AwardWallet\MainBundle\Entity\Rental::class),
            $this->em->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class),
        ];

        $this->itinerariesListener = new ItinerariesListener(
            new NullLogger(),
            $this->em,
            $repositories
        );
    }

    public function _after()
    {
        unset($this->itinerariesListener);

        parent::_after();
    }

    public function testWithoutCheckits()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], false, false);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        // if with itineraries -> should see SHOULD_BE_NO_ITINERARIES, so... dontSeeInDatabase
        $this->db->dontSeeInDatabase('ItineraryCheckError',
            ['ProviderID' => $account->getProviderid()->getProviderid()]);
    }

    public function testWithBadState()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], false, false, ACCOUNT_INVALID_PASSWORD);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        // if with itineraries -> should see SHOULD_BE_NO_ITINERARIES, so... dontSeeInDatabase
        $this->db->dontSeeInDatabase('ItineraryCheckError',
            ['ProviderID' => $account->getProviderid()->getProviderid()]);
    }

    public function testMobileExtensionUpdate()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], false, false, ACCOUNT_INVALID_PASSWORD, AccountUpdatedEvent::UPDATE_METHOD_EXTENSION);
        $accUpdEvent->getCheckAccountResponse()->getUserdata()->setSource(UpdaterEngineInterface::SOURCE_MOBILE);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        // if with itineraries -> should see SHOULD_BE_NO_ITINERARIES, so... dontSeeInDatabase
        $this->db->dontSeeInDatabase('ItineraryCheckError',
            ['ProviderID' => $account->getProviderid()->getProviderid()]);
    }

    public function testShouldBeNoItineraries()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], false);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);

        $this->db->seeInDatabase('ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::SHOULD_BE_NO_ITINERARIES,
            'ErrorMessage' => '',
        ]);
    }

    public function testShouldBeNoItinerariesProviderWithoutFlag()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 0]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], true);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);

        $this->db->seeInDatabase('ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::SHOULD_BE_NO_ITINERARIES,
            'ErrorMessage' => 'NoItineraries = true but CanCheckNoItineraries for provider is false',
        ]);
    }

    public function testNoUpdateEmptyItineraries()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "AccountID" => $accountId,
            "Parsed" => 1,
            "RecordLocator" => "RECLOC",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            'FlightNumber' => '666',
            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 13:00')),
            'DepCode' => 'LAX',
            'DepName' => 'LAX',
            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 15:00')),
            'ArrCode' => 'BUF',
            'ArrName' => 'BUF',
            'AirlineName' => 'Delta',
        ]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], false);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        $this->db->seeInDatabase('ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::NO_UPDATE,
        ]);
    }

    public function testNoUpdateNoItineraries()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "AccountID" => $accountId,
            "Parsed" => 1,
            "RecordLocator" => "RECLOC",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            'FlightNumber' => '666',
            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 13:00')),
            'DepCode' => 'LAX',
            'DepName' => 'LAX',
            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 15:00')),
            'ArrCode' => 'BUF',
            'ArrName' => 'BUF',
            'AirlineName' => 'Delta',
        ]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], true);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        $this->db->seeInDatabase('ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::NO_UPDATE,
        ]);
    }

    public function testDuplicateErrorItineraries()
    {
        $accountId = $this->createAccount(['CanCheckItinerary' => 1]);
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "AccountID" => $accountId,
            "Parsed" => 1,
            "RecordLocator" => "RECLOC",
        ]);
        $this->aw->createTripSegment([
            "TripID" => $tripId,
            'FlightNumber' => '666',
            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 13:00')),
            'DepCode' => 'LAX',
            'DepName' => 'LAX',
            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 15:00')),
            'ArrCode' => 'BUF',
            'ArrName' => 'BUF',
            'AirlineName' => 'Delta',
        ]);

        $accUpdEvent = $this->buildAccountUpdatedEvent($account, [], true);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        $this->db->seeInDatabase('ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::NO_UPDATE,
        ]);
        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
        $this->db->seeNumRecords(1, 'ItineraryCheckError', [
            'ProviderID' => $account->getProviderid()->getProviderid(),
            'ErrorType' => ItineraryCheckError::NO_UPDATE,
        ]);
    }

    //    public function testNoUpdateItinerariesUpdDate()
    //    {
    //        $accountId = $this->createAccount(['CanCheckItinerary' => 1, 'CanCheckNoItineraries' => 1]);
    //        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findOneBy(['accountid' => $accountId]);
    //        $tripId = $this->db->haveInDatabase("Trip", [
    //            "UserID" => $this->userId,
    //            "AccountID" => $accountId,
    //            "Parsed" => 1,
    //            "RecordLocator" => "RECLOC",
    //            'UpdateDate' => date("Y-m-d H:i:s", strtotime('-2 days 15:00'))
    //        ]);
    //        $this->aw->createTripSegment([
    //            "TripID" => $tripId,
    //            'FlightNumber' => '666',
    //            'DepDate' => date("Y-m-d H:i:s", strtotime('tomorrow 13:00')),
    //            'DepCode' => 'LAX',
    //            'DepName' => 'LAX',
    //            'ArrDate' => date("Y-m-d H:i:s", strtotime('tomorrow 15:00')),
    //            'ArrCode' => 'BUF',
    //            'ArrName' => 'BUF',
    //            'AirlineName' => 'Delta'
    //        ]);
    //
    //        $accUpdEvent = $this->buildAccountUpdatedEvent($account, ['someItinerary'=>'Parsed'], false);
    //        $this->itinerariesListener->onAccountUpdated($accUpdEvent);
    //        $this->db->seeInDatabase('ItineraryCheckError', [
    //            'ProviderID' => $account->getProviderid()->getProviderid(),
    //            'ErrorType' => ItineraryCheckError::NO_UPDATE
    //        ]);
    //    }

    private function createAccount($provFields = [])
    {
        $providerId = $this->aw->createAwProvider('testProvider' . StringUtils::getRandomCode(8),
            StringUtils::getRandomCode(8), $provFields);

        return $this->aw->createAwAccount($this->userId, $providerId, 'loginfakeuser');
    }

    private function buildAccountUpdatedEvent(
        Account $account,
        array $itineraries = [],
        bool $noItineraries = false,
        ?bool $checkIts = true,
        ?int $state = ACCOUNT_CHECKED,
        ?int $method = AccountUpdatedEvent::UPDATE_METHOD_LOYALTY
    ) {
        $userData = new UserData();
        $userData->setCheckIts($checkIts);

        return new AccountUpdatedEvent(
            $account,
            (new CheckAccountResponse())
                ->setItineraries($itineraries)
                ->setNoitineraries($noItineraries)
                ->setCheckdate(new \DateTime())
                ->setUserdata($userData)
                ->setState($state),
            new ProcessingReport(),
            $method
        );
    }
}
