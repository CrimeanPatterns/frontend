<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

class AccountCheckResponseConverterCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function convertRandomProps(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->user->getUserid(), 'testprovider', 'future.trip.random.props');
        $I->checkAccount($accountId);
        $I->seeInDatabase('Trip', ['UserID' => $this->user->getUserid()]);
        $tripId = $I->grabFromDatabase('Trip', 'TripID', ['UserID' => $this->user->getUserid()]);
        $I->seeInDatabase('TripSegment', ['TripID' => $tripId, 'MarketingAirlineConfirmationNumber' => 'TESTRANDT']);
    }
}
