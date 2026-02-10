<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use Codeception\Example;

/**
 * @group frontend-functional
 */
class StartCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider startDataProvider
     */
    public function testStart(\TestSymfonyGuy $I, Example $example)
    {
        $this->start($example);
    }

    private function startDataProvider(): array
    {
        return [
            [
                'wantToTest' => 'invalid type',
                'routeParams' => [
                    'type' => 'tesla',
                ],
                'expectedCode' => 200,
            ],
            [
                'wantToTest' => 'invalid action',
                'routeParams' => [
                    'type' => 'google',
                    'action' => 'chill',
                ],
                'expectedCode' => 400,
            ],
            [
                'wantToTest' => 'default action',
                'routeParams' => [
                    'type' => 'google',
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'accounts.google.com',
                'expectedScopes' => ['gmail.readonly'],
                'notExpectedScopes' => ['userinfo.profile'],
            ],
            [
                'wantToTest' => 'google register with mailbox',
                'routeParams' => [
                    'type' => 'google',
                    'action' => 'register',
                    'mailboxAccess' => 1,
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'accounts.google.com',
                'expectedScopes' => ['userinfo.profile', 'gmail.readonly', 'userinfo.email'],
            ],
            $this->googleRegisterNoMailboxExample(),
            $this->googleLoginNoMailboxExample(),
            [
                'wantToTest' => 'microsoft register with mailbox',
                'routeParams' => [
                    'type' => 'microsoft',
                    'action' => 'register',
                    'mailboxAccess' => 1,
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'login.microsoftonline.com',
                'expectedScopes' => ['User.Read', 'offline_access', 'Mail.Read'],
            ],
            [
                'wantToTest' => 'yahoo register with mailbox',
                'routeParams' => [
                    'type' => 'yahoo',
                    'action' => 'register',
                    'mailboxAccess' => 1,
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'api.login.yahoo.com',
                'expectedScopes' => ['profile', 'email', 'mail-r'],
            ],
            [
                'wantToTest' => 'yahoo login with mailbox',
                'routeParams' => [
                    'type' => 'yahoo',
                    'action' => 'login',
                    'mailboxAccess' => 'true',
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'api.login.yahoo.com',
                'expectedScopes' => ['profile', 'email', 'mail-r'],
            ],
            [
                'wantToTest' => 'aol register with mailbox',
                'routeParams' => [
                    'type' => 'aol',
                    'action' => 'register',
                    'mailboxAccess' => 1,
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'api.login.aol.com',
                'expectedScopes' => ['profile', 'email', 'mail-r'],
            ],
            [
                'wantToTest' => 'apple register no mailbox',
                'routeParams' => [
                    'type' => 'apple',
                    'action' => 'register',
                    'mailboxAccess' => 'false',
                ],
                'expectedCode' => 302,
                'expectedRedirect' => 'appleid.apple.com',
                'expectedScopes' => ['email', 'name'],
            ],
        ];
    }
}
