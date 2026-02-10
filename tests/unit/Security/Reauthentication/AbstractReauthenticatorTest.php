<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\ReauthCode;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\CodeReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\CodeSenderInterface;
use AwardWallet\MainBundle\Security\Reauthentication\EmailCodeSender;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\MobilePushCodeSender;
use AwardWallet\MainBundle\Security\Reauthentication\PasswordReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\Reauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthRequest;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthResponse;
use AwardWallet\MainBundle\Security\Reauthentication\ResultResponse;
use AwardWallet\MainBundle\Security\Reauthentication\SendReport;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class AbstractReauthenticatorTest extends BaseUserTest
{
    /**
     * @var AuthenticatedUser
     */
    protected $authUser;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var SessionInterface
     */
    protected $session;

    public function _before()
    {
        parent::_before();

        $this->authUser = new AuthenticatedUser($this->user, false);
        $this->environment = new Environment('1.1.1.1');
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function _after()
    {
        $this->authUser = null;
        $this->environment = null;
        $this->session = null;

        parent::_after();
    }

    protected function getRequest(string $input, string $context, ?string $action = null, ?string $intent = null): ReauthRequest
    {
        return new ReauthRequest(
            $action ?? Action::getChangeEmailAction(),
            $context,
            $input,
            $intent
        );
    }

    protected function getGeoMock(array $getLocationByIp, string $getLocationName): Geo
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Geo $geo */
        $geo = $this->getMockBuilder(Geo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $geo->expects($this->any())
            ->method('getLocationByIp')
            ->willReturn($getLocationByIp);
        $geo->expects($this->any())
            ->method('getLocationName')
            ->willReturn($getLocationName);

        return $geo;
    }

    protected function getMailerMock(?string $ip, ?string $location, string $code, bool $return = true): Mailer
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Mailer $mailer */
        $mailer = $this->getMockBuilder(Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('getMessageByTemplate')
            ->with($this->callback(function ($template) use ($ip, $location, $code) {
                return $template instanceof ReauthCode
                    && $template->ip === $ip
                    && $template->location === $location
                    && $template->code === $code;
            }))
            ->willReturn($message = (new Message())->setTo('test@test.com'));
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->equalTo([$message]))
            ->willReturn($return);

        return $mailer;
    }

    protected function getMobileSenderMock(Usr $user, bool $return = true): Sender
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Sender $mobileSender */
        $mobileSender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mobileSender->expects($this->once())
            ->method('loadDevices')
            ->with($this->equalTo([$user]), $this->equalTo(MobileDevice::TYPES_ALL), $this->equalTo(Content::TYPE_REAUTH_CODE))
            ->willReturn($devices = [new MobileDevice()]);
        $mobileSender->expects($this->once())
            ->method('send')
            ->with($this->anything(), $devices)
        ->willReturn($return);

        return $mobileSender;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CodeReauthenticator
     */
    protected function getCodeReauth(
        ?array $methods = null,
        ?array $codeSenders = null,
        ?TimeCommunicator $timeCommunicator = null
    ) {
        return $this->getMockBuilder(CodeReauthenticator::class)
            ->setConstructorArgs([
                $this->session,
                $this->container->get('translator'),
                $codeSenders ?? [
                    $this->container->get(EmailCodeSender::class),
                    $this->container->get(MobilePushCodeSender::class),
                ],
                $timeCommunicator ?? $this->container->get(TimeCommunicator::class),
                $this->container->get(LoggerInterface::class),
            ])
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PasswordReauthenticator
     */
    protected function getPasswordReauth(
        ?array $methods = null,
        ?PasswordChecker $passwordChecker = null
    ) {
        return $this->getMockBuilder(PasswordReauthenticator::class)
            ->setConstructorArgs([
                $this->container->get('translator'),
                $passwordChecker ?? $this->container->get('aw.security.password_checker'),
                $this->container->get(LoggerInterface::class),
            ])
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Reauthenticator
     */
    protected function getReauth(
        ?array $methods = null,
        ?TimeCommunicator $timeCommunicator = null,
        ?iterable $reauthenticators = null,
        ?AntiBruteforceLockerService $ipLocker = null,
        ?AntiBruteforceLockerService $loginLocker = null
    ) {
        return $this->getMockBuilder(Reauthenticator::class)
            ->setConstructorArgs([
                $reauthenticators ?? [
                    $this->container->get(PasswordReauthenticator::class),
                    $this->container->get(CodeReauthenticator::class),
                ],
                $this->session,
                $ipLocker ?? $this->container->get('aw.security.antibruteforce.ip'),
                $loginLocker ?? $this->container->get('aw.security.antibruteforce.login'),
                $timeCommunicator ?? $this->container->get(TimeCommunicator::class),
                $this->container->get(LoggerInterface::class),
            ])
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject[]|CodeSenderInterface[]
     */
    protected function getCodeSender(
        array $emailReturn = [true],
        array $mobileNotificationsReturn = [true]
    ) {
        $map = function ($v) {
            if (!is_array($v)) {
                $v = [$v];
            }

            if ($v[0] && !isset($v[1])) {
                $v[1] = 'device';
            }

            return new SendReport(...$v);
        };
        $emailCodeSender = $this->getMockBuilder(CodeSenderInterface::class)->getMock();
        $emailCodeSender->expects($this->exactly(count($emailReturn)))
            ->method('send')
            ->will($this->onConsecutiveCalls(...array_map($map, $emailReturn)));

        $mobilePushCodeSender = $this->getMockBuilder(CodeSenderInterface::class)->getMock();
        $mobilePushCodeSender->expects($this->exactly(count($mobileNotificationsReturn)))
            ->method('send')
            ->will($this->onConsecutiveCalls(...array_map($map, $mobileNotificationsReturn)));

        return [$emailCodeSender, $mobilePushCodeSender];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AntiBruteforceLockerService
     */
    protected function getLocker(?string $checkForLockoutReturn = null)
    {
        $locker = $this->getMockBuilder(AntiBruteforceLockerService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $locker->expects($this->once())
            ->method('checkForLockout')
            ->willReturn($checkForLockoutReturn);

        return $locker;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TimeCommunicator
     */
    protected function getTime(callable $callback)
    {
        $time = $this->getMockBuilder(TimeCommunicator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $time->method('getCurrentTime')
            ->willReturnCallback($callback);

        return $time;
    }

    protected function passwordWillMatch()
    {
        $passChecker = $this->mockServiceWithBuilder('aw.security.password_checker');
        $passChecker->method('checkPasswordUnsafe');
        $this->authUser->getEntity()->setPass('xxx');
    }

    /**
     * @param ReauthResponse $response
     */
    protected function assertReauthResponse(
        $response,
        bool $ask = true,
        ?string $context = null,
        ?string $inputType = null
    ) {
        $this->assertInstanceOf(ReauthResponse::class, $response);
        $this->assertEquals(
            $ask ? ReauthResponse::ACTION_ASK : ReauthResponse::ACTION_AUTHORIZED,
            $response->action
        );

        if ($context) {
            $this->assertEquals($context, $response->context);
        }

        if ($inputType) {
            $this->assertEquals($inputType, $response->inputType);
        }
    }

    /**
     * @param ResultResponse $response
     */
    protected function assertResultResponse($response, bool $success = true, ?string $error = null)
    {
        $this->assertInstanceOf(ResultResponse::class, $response);
        $this->assertEquals($success, $response->success);

        if (is_null($error)) {
            $this->assertNull($response->error);
        } else {
            $this->assertStringContainsString($error, $response->error);
        }
    }
}
