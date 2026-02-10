<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\ItineraryController;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\ItineraryFileAccessScenario;
use Codeception\Example;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group frontend-functional
 */
class ItineraryFileAccessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _after(\TestSymfonyGuy $I)
    {
        $I->logout();
    }

    /**
     * @dataProvider accessDataProvider
     */
    public function testItineraryFileAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var ItineraryFileAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        /** @var GoogleGeo $geoCoder */
        $geoCoder = $I->grabService('aw.geo.google_geo');
        $startDate = new \DateTime('+1 day 11:00');
        $endDate = new \DateTime('+2 days 12:00');
        /** @var Geotag $pickUpGeoTag */
        $pickUpGeoTag = $geoCoder->findGeoTagEntity('Moscow, Russia');
        /** @var Geotag $pickUpGeoTag */
        $dropOffGeoTag = $geoCoder->findGeoTagEntity('Perm, Russia');
        $rentalId = $I->haveInDatabase('Rental', [
            'UserID' => $scenario->victimId,
            'PickupLocation' => 'Moscow, Russia',
            'DropoffLocation' => 'Perm, Russia',
            'Number' => 'TEST_NUMBER',
            'PickupPhone' => 'TEST_PICK_UP_PHONE',
            'DropoffPhone' => 'TEST_DROP_OFF_PHONE',
            'PickupDatetime' => $startDate->format('Y-m-d H:i:s'),
            'DropoffDatetime' => $endDate->format('Y-m-d H:i:s'),
            'PickupGeoTagID' => $pickUpGeoTag->getGeotagid(),
            'DropoffGeoTagID' => $dropOffGeoTag->getGeotagid(),
            'RentalCompanyName' => 'TEST_PROVIDER',
            'ProviderID' => null,
            'Notes' => 'TEST_NOTES',
            'Cancelled' => false,
            'ChangeDate' => null,
            'Type' => Rental::TYPE_TAXI,
        ]);

        $I->switchToUser($scenario->victimId);
        $path = codecept_data_dir('simpleTextFile.txt');
        $file = [
            'name' => 'textFile' . StringUtils::getRandomCode(8) . '.txt',
            'type' => 'text/plain',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
        $I->sendPost(
            $I->grabService('router')->generate('upload_note_file', ['type' => 'rental', 'itineraryId' => $rentalId]),
            [],
            ['file' => $file]
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson(['status' => true]);

        $I->seeResponseContains($file['name']);
        $criteria = ['FileName' => $file['name']];
        $I->seeInDatabase('ItineraryFile', $criteria);

        $itineraryFileId = $I->grabFromDatabase('ItineraryFile', 'ItineraryFileID', $criteria);

        if ($scenario->authorized) {
            $I->switchToUser($scenario->getAttackerId());
            $I->saveCsrfToken();
        } else {
            $I->logout();
        }

        $I->amOnRoute(
            'aw_timeline_itinerary_fetch_file',
            ['itineraryFileId' => $itineraryFileId, 'off' => '']
        );

        switch ($scenario->expectedAction) {
            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl('/login?BackTo=');

                break;

            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);

                break;

            default:
                $I->seeResponseCodeIs(403);
                $I->seeInSource('Forbidden');
        }

        $I->grabService(ItineraryFileManager::class)->removeAllFilesByUser($scenario->victimId);
    }

    public static function accessDataProvider(): array
    {
        return ItineraryFileAccessScenario::dataProvider();
    }
}
