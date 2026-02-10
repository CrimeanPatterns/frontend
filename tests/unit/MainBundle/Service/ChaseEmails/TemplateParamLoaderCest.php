<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\ChaseEmails;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Service\ChaseEmails\TemplateParamLoader;
use Codeception\Example;

/**
 * @group frontend-unit
 */
class TemplateParamLoaderCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(\TestSymfonyGuy $I, Example $example)
    {
        /** @var GoogleGeo $geo */
        $geo = $I->grabService(GoogleGeo::class);
        $geoTagId = $geo->FindGeoTag($example['Address'])['GeoTagID'];

        $userId = $I->createAwUser();

        if ($example['Kind'] === 'R') {
            $itineraryId = $I->haveInDatabase("Reservation", [
                "UserID" => $userId,
                "ConfirmationNumber" => "CONFN1",
                "HotelName" => "Santa Barbara",
                "Address" => $example['Address'],
                "CheckInDate" => date("Y-m-d", strtotime("+1 week")),
                "CheckOutDate" => date("Y-m-d", strtotime("+2 week")),
                'RoomCount' => 1,
                'GuestCount' => 1,
                'GeoTagID' => $geoTagId,
                'Total' => 100,
                'CurrencyCode' => 'USD',
            ]);
        } else {
            throw new \Exception("Unknown kind: {$example['Kind']}");
        }

        /** @var TemplateParamLoader $loader */
        $loader = $I->grabService(TemplateParamLoader::class);
        $id = $example['Kind'] . "." . $itineraryId . "." . $example['Field'];
        $params = $loader->load($id);

        $I->assertEquals($example['ExpectedLocation'], $params['location']);
    }

    private function loadDataProvider()
    {
        return [
            ['Kind' => 'R', 'Field' => 'City', 'Address' => '3070 Gulf to Bay Boulevard, Clearwater, Florida 33759 USA', 'ExpectedLocation' => 'Clearwater, Florida'],
            ['Kind' => 'R', 'Field' => 'City', 'Address' => 'Roissypole, Rue de Rome, BP16461, Roissy CDG Cedex, 95708, France', 'ExpectedLocation' => 'Paris, France'],
        ];
    }
}
