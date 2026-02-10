<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController
{
    use AwardWallet\MainBundle\Globals\StringUtils;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\OAuthCallbackHandler;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\AuthorizedUser;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CallbackResultInterface;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\ExistingUserError;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidCsrfTextError;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidState;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\LoggedIn;
    use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\Registered;
    use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
    use Codeception\Example;
    use Prophecy\Argument;

    use const AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController\Dsl\HANDLER_RESULT;
    use const AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController\Dsl\PRESET;
    use const AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController\Dsl\REQUEST_ASSERT;
    use const AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController\Dsl\WANT_TO_TEST;

    /**
     * @group mobile
     * @group frontend-functional
     */
    class CallbackCest
    {
        use AutoVerifyMocksTrait;

        /**
         * @dataProvider callbackDataProvider
         */
        public function callback(\TestSymfonyGuy $I, Example $example)
        {
            [
                WANT_TO_TEST => $wantToTest,
                HANDLER_RESULT => $handlerResult,
                REQUEST_ASSERT => $requestAssert
            ] = $example;
            $I->wantToTest($wantToTest);
            $I->mockService(
                'aw.oauth.callback_handler.mobile',
                $I->prophesize(OAuthCallbackHandler::class)
                    ->handle(Argument::cetera())
                    ->willReturn($handlerResult)
                    ->getObjectProphecy()
                    ->reveal()
            );

            if (isset($example[PRESET])) {
                $presetDataList = $example[PRESET]($I);
            }

            $I->sendGET('/m/api/login_status');
            $I->saveCsrfToken();
            $I->sendPOST('/m/api/oauth/callback', ['google' => ['some' => 1, 'bar' => 2]]);
            $requestAssert($I, ...($presetDataList ?? []));
        }

        private function callbackDataProvider(): array
        {
            return [
                [
                    WANT_TO_TEST => 'invalid csrf',
                    HANDLER_RESULT => new InvalidCsrfTextError('Invalid csrf'),
                    REQUEST_ASSERT => $this->assertError('Invalid csrf'),
                ],
                [
                    WANT_TO_TEST => 'invalid state',
                    HANDLER_RESULT => InvalidState::getInstance(),
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I) {
                        $I->seeResponseCodeIs(400);
                    },
                ],
                [
                    WANT_TO_TEST => 'authorized user',
                    HANDLER_RESULT => AuthorizedUser::getInstance(),
                    PRESET => function (\TestSymfonyGuy $I) {
                        $login = StringUtils::getRandomCode(15);
                        $I->createAwUser($login, null, [
                            'Email' => "{$login}@testme.com",
                        ]);
                        $I->switchToUser($login);

                        return [$login];
                    },
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I, string $login) {
                        $I->seeResponseContainsJson([
                            'email' => "{$login}@testme.com",
                            'authenticated' => true,
                        ]);
                    },
                ],
                [
                    WANT_TO_TEST => 'logged in',
                    HANDLER_RESULT => new LoggedIn('some@email.com'),
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I) {
                        $I->seeResponseContainsJson([
                            'email' => 'some@email.com',
                        ]);
                    },
                ],
                [
                    WANT_TO_TEST => 'registered',
                    HANDLER_RESULT => new Registered('some@email.com', null),
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I) {
                        $I->seeResponseContainsJson([
                            'email' => 'some@email.com',
                        ]);
                    },
                ],
                [
                    WANT_TO_TEST => 'existing user',
                    HANDLER_RESULT => new ExistingUserError('some@email.com', 'Existing user detected'),
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I) {
                        $I->seeResponseContainsJson([
                            'error' => 'Existing user detected',
                            'requiredPassword' => true,
                            'email' => 'some@email.com',
                        ]);
                    },
                ],
                [
                    WANT_TO_TEST => 'unknown result',
                    HANDLER_RESULT => new class() implements CallbackResultInterface {},
                    REQUEST_ASSERT => function (\TestSymfonyGuy $I) {
                        $I->seeResponseCodeIs(500);
                    },
                ],
            ];
        }

        private function assertError(string $error): callable
        {
            return function (\TestSymfonyGuy $I) use ($error) {
                $I->seeResponseContainsJson(['error' => $error]);
            };
        }
    }
}

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\OauthController\Dsl
{
    const WANT_TO_TEST = 'want_to_test';
    const HANDLER_RESULT = 'handler_result';
    const REQUEST_ASSERT = 'request_assert';
    const PRESET = 'PRESET';
}
