<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;

class DataCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->createUserAndLogin($I);
    }

    public function timestampFix(\TestSymfonyGuy $I)
    {
        $this->accountSteps->loadData();
        $I->assertLessThan(10, abs(time() - (int) $I->grabDataFromJsonResponse('timestamp')));

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.6.1');
        $this->accountSteps->loadData();
        $I->dontSeeDataInJsonResponse('timestamp');
    }
}
