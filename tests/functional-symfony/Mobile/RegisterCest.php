<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

/**
 * @group frontend-functional
 * @group mobile
 */
class RegisterCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function register(\TestSymfonyGuy $I)
    {
        $userSteps = $this->userSteps;
        $userSteps->register(
            $password = 'password',
            $firstName = 'New TEST RANDOM',
            $lastName = 'User',
            $email = 'newuser-' . $I->grabRandomString(10) . "@udaff.com"
        );
        $I->seeResponseContains("$firstName $lastName");

        $userSteps->logout();

        $userSteps->register(
            $password = '',
            'New',
            'User',
            'newuser-' . $I->grabRandomString(10) . "@udaff.com",
            [['children.0.children.1', "This value should not be blank."]]
        );
        $userSteps->register(
            $password = '   ',
            'New',
            'User',
            'newuser-' . $I->grabRandomString(10) . "@udaff.com",
            [['children.0.children.1', "This value should not be blank."]]
        );
    }

    public function listaCCFakeRegistrationBan(\TestSymfonyGuy $I)
    {
        $this->userSteps->register(
            $password = 'password',
            $firstName = 'New TEST RANDOM',
            $lastName = 'User',
            $email = 'newuser-' . $I->grabRandomString(10) . "@lista.cc",
            [['children.0.children.0', "Invalid security code, please try again"]]
        );
    }

    public function bbnekCCFakeRegistrationBan(\TestSymfonyGuy $I)
    {
        $this->userSteps->register(
            $password = 'password',
            $firstName = 'New TEST RANDOM',
            $lastName = 'User',
            $email = $I->grabRandomString(10) . "bbbnekj@" . $I->grabRandomString(5) . ".cc",
            [['children.0.children.0', "This email is already taken"]]
        );
    }

    public function emailAwDomainRestriction(\TestSymfonyGuy $I)
    {
        $this->userSteps->register(
            'password',
            'New TEST RANDOM',
            'User',
            sprintf('%s@awardwallet.com', $I->grabRandomString(10)),
            [['children.0.children.0', "This email is already taken"]]
        );
        $this->userSteps->register(
            'password',
            'New TEST RANDOM',
            'User',
            sprintf('%s@%s.awardwallet.com', $I->grabRandomString(10), $I->grabRandomString(5)),
            [['children.0.children.0', "This email is already taken"]]
        );
    }
}
