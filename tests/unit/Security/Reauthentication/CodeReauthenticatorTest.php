<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\CodeReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\CodeSenderInterface;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\SendReport;

/**
 * @group frontend-unit
 * @group security
 */
class CodeReauthenticatorTest extends AbstractReauthenticatorTest
{
    public function testStartUnsupportedUserShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reauth = $this->getCodeReauth(['support']);
        $reauth->expects($this->once())
            ->method('support')
            ->willReturn(false);
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }

    public function testStartFirstCallShouldSendCode()
    {
        $response = $this->getCodeReauth(null, $this->getCodeSender())
            ->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse(
            $response,
            true,
            'code',
            CodeReauthenticator::INPUT_TYPE
        );
        $this->assertNotEmpty($response->dialogTitle);
        $this->assertNotEmpty($response->inputTitle);
        $this->assertTrue($response->resendAllowed);
    }

    public function testStartNoCodeSentToAnyoneShouldThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->getCodeReauth(null, $this->getCodeSender([false], [false]))
            ->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }

    public function testStartSecondCallShouldNotSendCode()
    {
        $time = strtotime('-2 minutes');
        $codeReauth = $this->getCodeReauth(null, $this->getCodeSender([true], [true]), $this->getTime(function () use ($time) { return $time; }));
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);

        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);
    }

    public function testStartSecondCallAfter5MinutesShouldSendCode()
    {
        $time = time();
        $codeReauth = $this->getCodeReauth(null, $this->getCodeSender([true, true], [true, true]), $this->getTime(function () use (&$time) { return $time; }));
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);

        $time = strtotime('+6 minutes');
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);
    }

    public function testVerifyUnsupportedUserShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reauth = $this->getCodeReauth(['support']);
        $reauth->expects($this->once())
            ->method('support')
            ->willReturn(false);
        $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code'),
            $this->environment
        );
    }

    public function testVerifyInvalidContextShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong context "test"');
        $this->getCodeReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'test'),
            $this->environment
        );
    }

    public function testVerifyInvalidIntentShouldThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported intent "test"');
        $this->getCodeReauth()->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code', Action::getChangeEmailAction(), 'test'),
            $this->environment
        );
    }

    public function testVerifyIntentResendShouldSendCode()
    {
        $time = time();
        $codeReauth = $this->getCodeReauth(null, $this->getCodeSender([true, true], [true, true]), $this->getTime(function () use (&$time) { return $time; }));
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);

        $time = strtotime('+1 minutes');
        $response = $codeReauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code', Action::getChangeEmailAction(), CodeReauthenticator::INTENT_RESEND),
            $this->environment
        );
        $this->assertResultResponse($response, true);
    }

    public function testVerifyFrequentIntentResendShouldReturnError()
    {
        $time = time();
        $codeReauth = $this->getCodeReauth(null, $this->getCodeSender([true], [true]), $this->getTime(function () use (&$time) { return $time; }));
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);

        $time = strtotime('+15 seconds');
        $response = $codeReauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code', Action::getChangeEmailAction(), CodeReauthenticator::INTENT_RESEND),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'Please wait');
    }

    public function testVerifyFrequentIntentResendExpiredCodeShouldSendNewCode()
    {
        $time = time();
        $codeReauth = $this->getCodeReauth(null, $this->getCodeSender([true, true, true], [true, true, true]), $this->getTime(function () use (&$time) { return $time; }));
        $response = $codeReauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $this->assertReauthResponse($response, true);

        $time = strtotime('+4 minutes +55 seconds', $time);
        $request = $this->getRequest('xxx', 'code', Action::getChangeEmailAction(), CodeReauthenticator::INTENT_RESEND);
        $response = $codeReauth->verify($this->authUser, $request, $this->environment);
        $this->assertResultResponse($response, true);

        $time = strtotime('+15 seconds', $time);
        $response = $codeReauth->verify($this->authUser, $request, $this->environment);
        $this->assertResultResponse($response, true);
    }

    public function testVerifyInvalidCodeShouldReturnResultFalse()
    {
        $reauth = $this->getCodeReauth();
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest('xxx', 'code'),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'The code that you provided is invalid');
    }

    public function testVerifyExpiredCodeShouldResendAndReturnResultFalse()
    {
        $time = time();
        $code = null;
        $codeSender = $this->getMockBuilder(CodeSenderInterface::class)->getMock();
        $codeSender->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function (AuthenticatedUser $authUser, string $c, Environment $environment) use (&$code) {
                $code = $c;

                return new SendReport(true, 'device');
            });
        $reauth = $this->getCodeReauth(null, [$codeSender], $this->getTime(function () use (&$time) { return $time; }));
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $time = strtotime('+10 minutes');
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest($code, 'code'),
            $this->environment
        );
        $this->assertResultResponse($response, false, 'The code that you provided has expired');
    }

    public function testVerifyValidCodeShouldReturnResultTrue()
    {
        $time = time();
        $code = null;
        $codeSender = $this->getMockBuilder(CodeSenderInterface::class)->getMock();
        $codeSender->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (AuthenticatedUser $authUser, string $c, Environment $environment) use (&$code) {
                $code = $c;

                return new SendReport(true, 'device');
            });
        $reauth = $this->getCodeReauth(null, [$codeSender], $this->getTime(function () use (&$time) { return $time; }));
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $time = strtotime('+2 minutes');
        $response = $reauth->verify(
            $this->authUser,
            $this->getRequest($code, 'code'),
            $this->environment
        );
        $this->assertResultResponse($response, true, null);
    }

    public function testReset()
    {
        $reauth = $this->getCodeReauth(null, $this->getCodeSender([true, true], [true, true]));
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
        $reauth->reset(Action::getChangeEmailAction());
        $reauth->start($this->authUser, Action::getChangeEmailAction(), $this->environment);
    }
}
