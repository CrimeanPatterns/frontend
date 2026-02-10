<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationResponse;
use AwardWallet\MainBundle\Loyalty\Resources\InputField;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\SerializerInterface;

/**
 * @group frontend-functional
 * @coversDefaultClass \AwardWallet\MainBundle\Loyalty\Converter
 */
class ConverterCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function _before(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->serializer = $I->grabService('jms_serializer');
    }

    public function processCheckConfirmationResponse(\TestSymfonyGuy $I): void
    {
        /** @var Converter $converter */
        $converter = $I->grabService(Converter::class);
        $jsonData = file_get_contents(__DIR__ . '/../../../_data/loyaltyCheckConfirmationResponse.json');
        /** @var CheckConfirmationResponse $response */
        $response = $this->serializer->deserialize($jsonData, CheckConfirmationResponse::class, 'json');
        $request = new CheckConfirmationRequest();
        $request->setProvider('testprovider');
        $request->setUserId($this->user->getUserid());
        $request->setPriority(7);
        $request->setFields([
            (new InputField())->setCode('ConfNo')->setValue('future.trip.and.reservation'),
            (new InputField())->setCode('LastName')->setValue('SomeLastName'),
        ]);
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $I->grabService('event_dispatcher');
        $hasBeenDispatched = false;
        $eventDispatcher->addListener('aw.itinerary.update', function () use (&$hasBeenDispatched) {
            $hasBeenDispatched = true;
        });

        $report = $converter->processCheckConfirmationResponse($response, $request);

        $I->assertInstanceOf(ProcessingReport::class, $report);
        $I->assertCount(2, $report->getAdded());
        $I->assertInstanceOf(Reservation::class, $report->getAdded()[1]);
        $I->assertInstanceOf(Trip::class, $report->getAdded()[0]);
        $I->assertTrue($hasBeenDispatched);
    }

    public function processCheckAccountResponse(\TestSymfonyGuy $I): void
    {
        $jsonData = file_get_contents(__DIR__ . '/../../../_data/loyaltyCheckAccountResponse.json');
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find(
            $I->createAwAccount($this->user->getUserid(), 'testprovider', 'history')
        );
        $jsonData = str_replace('%accountId%', $account->getId(), $jsonData);
        /** @var CheckAccountResponse $response */
        $response = $this->serializer->deserialize($jsonData, CheckAccountResponse::class, 'json');
        $I->assertInstanceOf(UserData::class, $response->getUserdata());
        $callback = (new CheckAccountCallback())->setResponse([$response]);

        /** @var Converter $converter */
        $converter = $I->grabService(Converter::class);

        $result = $converter->processCallbackPackage($callback);
        $I->assertEquals([], $result);
        $I->seeInDatabase('Trip', [
            'AccountID' => $account->getAccountid(),
            'RecordLocator' => 'MRTG67',
        ]);
    }

    public function prepareCheckAccountRequest(\TestSymfonyGuy $I): void
    {
        $accountId = $I->createAwAccount($this->user->getUserid(), "testprovider", "some");
        $subAccount1Id = $I->createAwSubAccount($accountId, ["Code" => "sub1", "DisplayName" => "SubAccount1"]);
        $subAccount2Id = $I->createAwSubAccount($accountId, ["Code" => "sub2", "DisplayName" => "SubAccount2"]);
        /** @var Converter $converter */
        $converter = $I->grabService(Converter::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($accountId);
        $options = new ConverterOptions();
        $options->setParseHistory(true);

        // account without server history state and client history
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertEquals(null, $request->getHistory()->getState());

        // has server history state, and no client history
        /** @var EntityManagerInterface $em */
        $em = $I->grabService("doctrine.orm.entity_manager");
        $encryptionKey = $I->getContainer()->getParameter("loyalty_encryption_key");
        $account->setHistoryState(base64_encode(AESEncode(json_encode([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "subAccountLastDates" => [
                "sub1" => "2021-06-06",
            ],
        ]), $encryptionKey)));
        $em->flush();
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertNotNull($request->getHistory()->getState());
        $state = json_decode(AESDecode(base64_decode($request->getHistory()->getState()), $encryptionKey), true);
        $I->assertEquals([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "subAccountLastDates" => [
                "sub1" => "2021-06-06",
            ],
        ], $state);

        // has server history state, and client history. client history merged into server
        $I->haveInDatabase("AccountHistory", ["AccountID" => $accountId, "SubAccountID" => $subAccount2Id, "PostingDate" => "2020-02-01", "UUID" => bin2hex(random_bytes(18))]);
        $I->haveInDatabase("AccountHistory", ["AccountID" => $accountId, "SubAccountID" => $subAccount2Id, "PostingDate" => "2020-02-02", "UUID" => bin2hex(random_bytes(18))]);
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertNotNull($request->getHistory()->getState());
        $state = json_decode(AESDecode(base64_decode($request->getHistory()->getState()), $encryptionKey), true);
        $I->assertEquals([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "subAccountLastDates" => [
                "sub1" => "2021-06-06",
                "sub2" => "2020-02-02",
            ],
        ], $state);

        // no server history, but has client history
        $account->setHistoryState(null);
        $em->flush();
        $I->haveInDatabase("AccountHistory", ["AccountID" => $accountId, "PostingDate" => "2020-10-01", "UUID" => bin2hex(random_bytes(18))]);
        $I->haveInDatabase("AccountHistory", ["AccountID" => $accountId, "PostingDate" => "2020-10-02", "UUID" => bin2hex(random_bytes(18))]);
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertNotNull($request->getHistory()->getState());
        $state = json_decode(AESDecode(base64_decode($request->getHistory()->getState()), $encryptionKey), true);
        $I->assertEquals([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "lastDate" => "2020-10-02",
            "subAccountLastDates" => [
                "sub2" => "2020-02-02",
            ],
        ], $state);

        // invalid server state structure, but has client history
        $account->setHistoryState(base64_encode(AESEncode(json_encode(["invalid" => "state"]), $encryptionKey)));
        $em->flush();
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertNotNull($request->getHistory()->getState());
        $state = json_decode(AESDecode(base64_decode($request->getHistory()->getState()), $encryptionKey), true);
        $I->assertEquals([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "lastDate" => "2020-10-02",
            "subAccountLastDates" => [
                "sub2" => "2020-02-02",
            ],
        ], $state);

        // badly encoded server state structure, but has client history
        $account->setHistoryState(base64_encode(AESEncode(json_encode(["invalid" => "state"]), "somebadkey")));
        $em->flush();
        $request = $converter->prepareCheckAccountRequest($account, $options);
        $I->assertNotNull($request->getHistory()->getState());
        $state = json_decode(AESDecode(base64_decode($request->getHistory()->getState()), $encryptionKey), true);
        $I->assertEquals([
            "structureVersion" => 1,
            "cacheVersion" => 1,
            "lastDate" => "2020-10-02",
            "subAccountLastDates" => [
                "sub2" => "2020-02-02",
            ],
        ], $state);
    }
}
