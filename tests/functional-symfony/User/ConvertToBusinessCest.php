<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class ConvertToBusinessCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testConvertToBusiness(\TestSymfonyGuy $I)
    {
        $I->wantTo("test convert to business");

        $login = 'test' . $I->grabRandomString(5);
        $companyName = $login . 'Company';
        $userId = $I->createAwUser($login, null, [], false, true);

        $page = $I->grabService('router')->generate('aw_user_convert_to_business');

        $I->comment("convert to business");
        $I->amOnPage($page . "?_switch_user=" . $login);
        $I->fillField(['name' => 'form[name]'], $companyName);
        $I->click('button[type=submit]');
        $I->see('Thank you, your business account has been created');

        $I->comment("business created");
        $businessId = $I->grabFromDatabase('Usr', 'UserID', [
            'Company' => $companyName,
        ]);
        $I->assertNotEmpty($businessId);

        $I->comment("user is business admin");
        /** @var UsrRepository $usrRepository */
        $usrRepository = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        /** @var Usr $user */
        $user = $usrRepository->find($userId);
        $I->assertTrue($usrRepository->isUserBusinessAdmin($user));
        $business = $usrRepository->getBusinessByUser($user, [ACCESS_ADMIN]);
        $I->assertEquals($business->getUserid(), $businessId);
    }
}
