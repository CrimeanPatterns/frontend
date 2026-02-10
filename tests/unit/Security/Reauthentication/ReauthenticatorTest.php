<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\CodeReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\EmailCodeSender;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\PasswordReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\SendReport;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @group frontend-unit
 * @group security
 */
class ReauthenticatorTest extends AbstractReauthenticatorTest
{
    public function testStartAlreadyAuthShouldReturnAuthorized()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
        $response = $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, false);
    }

    public function testStartAlreadyAuthUserWithPasswordWithChangedIpShouldShowPasswordPopup()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
        $response = $reauth->start($this->authUser, Action::getChangeEmailAction(), new Environment('1.1.1.2'));
        $this->assertReauthResponse($response, true, 'password', PasswordReauthenticator::INPUT_TYPE);
        $response = $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, false);
    }

    public function testStartNoReauthenticatorsShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unable to authenticate/');
        $reauth = $this->getReauth(null, null, []);
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }

    public function testStartNoSupportedReauthenticatorsShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unable to authenticate/');
        $passChecker = $this->mockServiceWithBuilder(PasswordReauthenticator::class);
        $passChecker->method('support')->willReturn(false);
        $passChecker = $this->mockServiceWithBuilder(CodeReauthenticator::class);
        $passChecker->method('support')->willReturn(false);
        $this->getReauth()->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }

    public function testStartUserWithPasswordShouldShowPasswordPopup()
    {
        $this->passwordWillMatch();
        $response = $this->getReauth()->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true, 'password', PasswordReauthenticator::INPUT_TYPE);
    }

    public function testStartUserWithoutPasswordShouldShowCodePopup()
    {
        $this->authUser->getEntity()->setPass(null);
        $response = $this->getReauth()->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true, 'code', CodeReauthenticator::INPUT_TYPE);
    }

    public function testVerifyAlreadyAuthShouldReturnAuthorized()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testVerifyAlreadyAuthUserWithPasswordWithChangedIpShouldReturnFalseResponse()
    {
        $passChecker = $this->mockServiceWithBuilder('aw.security.password_checker');
        $passChecker
            ->method('checkPasswordUnsafe')
            ->will($this->returnCallback(function (Usr $user, string $presentedPassword) {
                if ($presentedPassword === 'xxx') {
                    return true;
                }

                throw new BadCredentialsException('Bad credentials');
            }));
        $this->authUser->getEntity()->setPass('xxx');
        $reauth = $this->getReauth();
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);

        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password'),
            new Environment('1.1.1.2')
        );
        $this->assertResultResponse($response, false, 'Invalid password');

        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testVerifyNoReauthenticatorsShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unable to authenticate/');
        $reauth = $this->getReauth(null, null, []);
        $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
    }

    public function testVerifyNoSupportedReauthenticatorsShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unable to authenticate/');
        $passChecker = $this->mockServiceWithBuilder(PasswordReauthenticator::class);
        $passChecker->method('support')->willReturn(false);
        $passChecker = $this->mockServiceWithBuilder(CodeReauthenticator::class);
        $passChecker->method('support')->willReturn(false);
        $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password'),
            $this->environment
        );
    }

    public function testVerifyUserWithPasswordInvalidInputShouldReturnFalseResponse()
    {
        $this->authUser->getEntity()->setPass('xxx');
        $response = $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'Invalid password');
    }

    public function testVerifyUserWithPasswordSuccessInputShouldReturnTrueResponse()
    {
        $this->passwordWillMatch();
        $response = $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password'),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testVerifyUserWithoutPasswordInvalidInputShouldReturnFalseResponse()
    {
        $this->authUser->getEntity()->setPass(null);
        $response = $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('yyy', 'code'),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'The code that you provided has expired');
    }

    public function testVerifyUserWithoutPasswordSuccessInputShouldReturnTrueResponse()
    {
        $code = null;
        $action = Action::getChangeEmailAction();
        $codeSender = $this->getMockBuilder(EmailCodeSender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $codeSender->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (AuthenticatedUser $authUser, string $c, Environment $environment) use (&$code) {
                $code = $c;

                return new SendReport(true, 'test@test.com');
            });
        $reauth = $this->getReauth(null, null, [
            $this->container->get(PasswordReauthenticator::class),
            $this->getCodeReauth(null, [$codeSender]),
        ]);
        $this->authUser->getEntity()->setPass(null);
        $reauth->start($this->authUser, $action, $this->environment);
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest($code, 'code', $action),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testVerifyUserWithPasswordIntentShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Unsupported intent/');
        $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('yyy', 'password', null, 'test'),
            $this->environment
        );
    }

    public function testVerifyUserWithoutPasswordIntentShouldReturnTrueResponse()
    {
        $this->authUser->getEntity()->setPass(null);
        $response = $this->getReauth()->verify(
            $this->authUser,
            $this->getRequest('yyy', 'code', null, CodeReauthenticator::INTENT_RESEND),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testLoginLockoutError()
    {
        $locker = $this->getLocker($error = 'lock error');
        $reauth = $this->getReauth(null, null, null, null, $locker);
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code'),
            $this->environment
        );
        $this->assertResultResponse($response, false, $error);
    }

    public function testReauthenticated()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $action = Action::getChangeEmailAction();
        $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password', $action),
            $this->environment
        );
        $this->assertTrue($reauth->isReauthenticated($action, $this->environment->getIp()));
    }

    public function testNotReauthenticatedAfter5Minutes()
    {
        $time = time();
        $action = Action::getChangeEmailAction();
        $this->passwordWillMatch();
        $reauth = $this->getReauth(null, $this->getTime(function () use (&$time) { return $time; }));
        $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password', $action),
            $this->environment
        );
        $this->assertTrue($reauth->isReauthenticated($action, $this->environment->getIp()));
        $time = strtotime('+5 minutes');
        $this->assertFalse($reauth->isReauthenticated($action, $this->environment->getIp()));
    }

    public function testNotReauthenticatedWithChangedIp()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $action = Action::getChangeEmailAction();
        $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password', $action),
            $this->environment
        );
        $this->assertTrue($reauth->isReauthenticated($action, $this->environment->getIp()));
        $this->assertFalse($reauth->isReauthenticated($action, '1.1.1.2'));
    }

    public function testReset()
    {
        $this->passwordWillMatch();
        $reauth = $this->getReauth();
        $action = Action::getChangeEmailAction();
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'password', $action),
            $this->environment
        );
        $this->assertResultResponse($response, true);
        $this->assertTrue($reauth->isReauthenticated($action, $this->environment->getIp()));
        $reauth->reset($action);
        $this->assertFalse($reauth->isReauthenticated($action, $this->environment->getIp()));
    }
}
