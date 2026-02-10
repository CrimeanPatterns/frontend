<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\PasswordReauthenticator;

/**
 * @group frontend-unit
 * @group security
 */
class PasswordReauthenticatorTest extends AbstractReauthenticatorTest
{
    public function testStartUserWithoutPassShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"Password" method of authentication is not available');
        $this->authUser->getEntity()->setPass(null);
        $this->getPasswordReauth()->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }

    public function testStartSucces()
    {
        $response = $this->getPasswordReauth()->start(
            $this->authUser,
            Action::getChangeEmailAction(),
            $this->environment
        );
        $this->assertReauthResponse(
            $response,
            true,
            'password',
            PasswordReauthenticator::INPUT_TYPE
        );
        $this->assertNotEmpty($response->dialogTitle);
        $this->assertNotEmpty($response->inputTitle);
        $this->assertNull($response->resendAllowed);
    }

    public function testVerifyUserWithoutPassShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"Password" method of authentication is not available');
        $this->authUser->getEntity()->setPass(null);
        $this->getPasswordReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
    }

    public function testVerifyInvalidContextShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong context "test"');
        $this->getPasswordReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'test'),
            $this->environment
        );
    }

    public function testVerifyInvalidIntentShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported intent "test"');
        $this->getPasswordReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password', Action::getChangeEmailAction(), 'test'),
            $this->environment
        );
    }

    public function testVerifyBadCredentialsShouldReturnResultFalse()
    {
        $response = $this->getPasswordReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'Invalid password');
    }

    public function testVerifySuccess()
    {
        $passChecker = $this->getMockBuilder(PasswordChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $passChecker->expects($this->once())
            ->method('checkPasswordUnsafe');

        $response = $this->getPasswordReauth(null, $passChecker)
            ->verify(
                $this->authUser,
                $this->getRequest('xxx', 'password'),
                $this->environment
            );
        $this->assertResultResponse($response, true, null);
    }

    public function testSupportUserWithoutPassShouldReturnFalse()
    {
        $this->authUser->getEntity()->setPass(null);
        $this->assertFalse($this->getPasswordReauth()->support($this->authUser));
    }

    public function testSupportUserWithPassShouldReturnTrue()
    {
        $this->assertTrue($this->getPasswordReauth()->support($this->authUser));
    }
}
