<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class EmailVerifyCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function test(\TestSymfonyGuy $I)
    {
        $I->wantTo("verify email");

        $login = 'veremail' . $I->grabRandomString(5);
        $I->createAwUser($login, null, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'EmailVerified' => 0,
        ], true, true);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $I->grabService('doctrine')->getManager();
        /** @var Usr $user */
        $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneByLogin($login);
        $router = $I->grabService('router');
        $I->amOnPage($router->generate('aw_profile_overview') . "?_switch_user=" . $login);

        $I->see("Your email has not been verified");
        $I->saveCsrfToken();
        $I->sendAjaxPostRequest($router->generate('aw_email_verify_send'));
        $I->seeEmailTo($user->getEmail(), "Email verification request from", $login);
        $I->mockService(BackgroundCheckScheduler::class, $I->stubMake(BackgroundCheckScheduler::class, [
            'onUserEmailVerificationChanged' => Stub::once(),
        ]));

        $I->amOnPage($router->generate('aw_email_verify', [
            'login' => $login,
            'id' => $user->getEmailVerificationHash(),
        ]));
        $I->see("Your email has been verified");

        $I->amOnPage($router->generate('aw_profile_overview'));
        $I->seeElement('table.edit-account td.info-row span.verified');
    }
}
