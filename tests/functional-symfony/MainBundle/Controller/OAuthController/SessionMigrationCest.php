<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use Codeception\Example;

/**
 * @group frontend-functional
 */
class SessionMigrationCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testLogin(\TestSymfonyGuy $I)
    {
        $startLoginExample = $this->googleLoginNoMailboxExample();
        $this->start(new Example($startLoginExample));

        $anonymousSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($anonymousSessionId);

        $exampleData = $this->loginExistingOAuthExample();
        $this->callback(new Example($exampleData));

        $authorizedSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($anonymousSessionId);
        $I->assertNotEquals($anonymousSessionId, $authorizedSessionId);
    }

    public function testRegister(\TestSymfonyGuy $I)
    {
        $startExample = $this->googleRegisterNoMailboxExample();
        $this->start(new Example($startExample));

        $anonymousSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($anonymousSessionId);

        $exampleData = $this->registerNewUserExample();
        $this->callback(new Example($exampleData));

        $authorizedSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($anonymousSessionId);
        $I->assertNotEquals($anonymousSessionId, $authorizedSessionId);
    }
}
