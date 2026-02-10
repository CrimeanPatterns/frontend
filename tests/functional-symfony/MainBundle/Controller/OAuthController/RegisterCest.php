<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\CodeReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\PasswordReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthResponse;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ConnectOAuthMailboxRequest;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\EmailScannerApiStub;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;
use Codeception\Example;
use Codeception\Util\Stub;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class RegisterCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    public function testRegisterExistingThenValidPassword(\TestSymfonyGuy $I)
    {
        $exampleData = $this->registerExistingExample(true);
        $user = $this->callback(new Example($exampleData));
        $I->mockService(EmailScannerApiStub::class, $I->stubMakeEmpty(EmailScannerApiStub::class, [
            'connectGoogleMailbox' => Stub::once(function (ConnectOAuthMailboxRequest $request) {
                $this->I->assertInstanceOf(ConnectOAuthMailboxRequest::class, $request);
                $this->I->assertEquals(static::ACCESS_TOKEN, $request->getAccessToken());
                $this->I->assertEquals(static::REFRESH_TOKEN, $request->getRefreshToken());

                return new OAuthMailbox(['id' => 12345]);
            }),
            'listMailboxes' => Stub::atLeastOnce(function () { return []; }),
        ]));
        $this->loginUser(['login' => $user->getEmail(), "password" => static::USER_PASS], $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("UserOAuth", ["UserID" => $user->getUserid(), "Provider" => "google"]);
        $I->verifyMocks();

        // check reauth
        /** @var RouterInterface $router */
        $router = $I->grabService('router');
        $I->saveCsrfToken();
        $I->sendPOST($router->generate('aw_reauth_start'), ['action' => Action::getChangeEmailAction()]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'action' => ReauthResponse::ACTION_ASK,
            'inputType' => PasswordReauthenticator::INPUT_TYPE,
        ]);
    }

    public function testRegisterSuccessWithBackTo(\TestSymfonyGuy $I)
    {
        $startExample = $this->googleRegisterNoMailboxExample();
        $startExample['routeParams']['BackTo'] = '/test/client-info?x=1';
        $this->start(new Example($startExample));

        $exampleData = $this->registerNewUserExample();
        $exampleData['seeInSource'] = "window.location.href = '/test/client-info?x=1';";
        // $exampleData['expectedRedirect'] = '/test/client-info?x=1';
        // $exampleData['expectedAfterRedirect'] = 'host_ip';
        $this->callback(new Example($exampleData));
    }

    public function testRegisterSuccessWithCouponCode(\TestSymfonyGuy $I)
    {
        $startExample = $this->googleRegisterNoMailboxExample();
        $startExample['routeParams']['Code'] = 'SomeCouponCode';
        $this->start(new Example($startExample));

        $exampleData = $this->registerNewUserExample();
        parse_str(parse_url($I->grabHttpHeader('Location'), PHP_URL_QUERY), $params);
        $exampleData['routeParams']['state'] = $params["state"];
        $exampleData['seeInSource'] = "window.location.href = '/user/useCoupon';";
        // $exampleData['expectedRedirect'] = '/user/useCoupon';
        // $exampleData['expectedAfterRedirect'] = 'Please enter a coupon code';
        unset($exampleData['csrf']);
        $this->callback(new Example($exampleData));
    }

    public function testRegisterReferer(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Referer', 'http://some.ref/com?x=1');
        $I->amOnPage("/");
        $I->deleteHeader('Referer');

        $startExample = $this->googleRegisterNoMailboxExample();
        $this->start(new Example($startExample));

        $exampleData = $this->registerNewUserExample();
        $this->callback(new Example($exampleData));
        $I->assertEquals(
            'http://some.ref/com?x=1',
            $I->grabFromDatabase(
                "Usr",
                "Referer",
                $exampleData['expectedNewUserWith']
            )
        );
    }

    public function testRegisterWithoutPassword(\TestSymfonyGuy $I)
    {
        $exampleData = $this->registerNewUserExample();
        $this->callback(new Example($exampleData));
        /** @var RouterInterface $router */
        $router = $I->grabService('router');
        $I->saveCsrfToken();
        $I->sendPOST($router->generate('aw_reauth_start'), ['action' => Action::getChangeEmailAction()]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'action' => ReauthResponse::ACTION_ASK,
            'inputType' => CodeReauthenticator::INPUT_TYPE,
        ]);
    }
}
