<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;
use Codeception\Example;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class LoginCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    public function testLoginExistingThenValidPassword(\TestSymfonyGuy $I)
    {
        $exampleData = $this->loginExistingExample();
        $user = $this->callback(new Example($exampleData));
        $this->loginUser([
            'login' => $user->getEmail(),
            'password' => static::USER_PASS,
        ], $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("UserOAuth", ["UserID" => $user->getUserid(), "Provider" => "google"]);
    }

    public function testLoginExistingThenEmailMismatch(\TestSymfonyGuy $I)
    {
        $exampleData = $this->loginExistingExample();
        $user = $this->callback(new Example($exampleData));
        $user->setEmail("new_" . $user->getEmail());
        /** @var EntityManagerInterface $em */
        $em = $I->grabService("doctrine.orm.entity_manager");
        $em->flush();
        $this->loginUser([
            'login' => $user->getEmail(),
            'password' => static::USER_PASS,
        ], $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->dontSeeInDatabase("UserOAuth", ["UserID" => $user->getUserid(), "Provider" => "google"]);
    }

    public function testLoginSuccessWithBackTo(\TestSymfonyGuy $I)
    {
        $startLoginExample = $this->googleLoginNoMailboxExample();
        $startLoginExample['routeParams']['BackTo'] = '/test/client-info?x=1';
        $this->start(new Example($startLoginExample));

        $exampleData = $this->loginExistingOAuthExample();
        $exampleData['expectedRedirect'] = '/test/client-info?x=1';
        $exampleData['expectedAfterRedirect'] = 'host_ip';
        $this->callback(new Example($exampleData));
    }
}
