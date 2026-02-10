<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @group frontend-unit
 * @group security
 */
class PasswordCheckerTest extends BaseTest
{
    public const PROVIDER_KEY = 'test_provider_key';

    public function testCheckPasswordSafeSuccess()
    {
        $user = new Usr();
        $user->setPass($pass = '123456');
        $user->setLogin($login = 'logging');

        $ipLocker = $this->getAntiBruteforceLockerServiceMock($ip = '1.1.1.1', true, null);

        $loginLocker = $this->getMockBuilder(AntiBruteforceLockerService::class)->disableOriginalConstructor()->getMock();
        $loginLocker->expects($this->once())
            ->method('checkForLockout')
            ->with($user->getUsername(), false)
            ->willReturn(null);
        $loginLocker->expects($this->once())
            ->method('unlock')
            ->with($user->getUsername());

        $presentedPass = $pass;

        $provider = new PasswordChecker(
            $ipLocker,
            $loginLocker,
            $this->getLoggerInterfaceMock(),
            $this->getEncoderFactoryMock($user, $presentedPass),
            $this->neverUsed($this->getEntityManagerMock()),
            "xxx"
        );

        $this->assertTrue($provider->checkPasswordSafe($user, $presentedPass, $ip));
    }

    public function testCheckPasswordSafeInvalidPassword()
    {
        $user = new Usr();
        $user->setPass($pass = '123456');
        $user->setLogin($login = 'logging');

        $loginLocker = $this->getAntiBruteforceLockerServiceMock($login, false, null);
        $ipLocker = $this->getMockBuilder(AntiBruteforceLockerService::class)->disableOriginalConstructor()->getMock();
        $ipLocker->expects($this->exactly(2))
            ->method('checkForLockout')
            ->withConsecutive(
                [$ip = '1.1.1.1', true],
                [$ip, false]
            )->willReturn(null);

        $presentedPass = '533423';

        $provider = new PasswordChecker(
            $ipLocker,
            $loginLocker,
            $this->getLoggerInterfaceMock(),
            $this->getEncoderFactoryMock($user, $presentedPass),
            $this->neverUsed($this->getEntityManagerMock()),
            "xxx"
        );

        $this->assertFalse($provider->checkPasswordSafe($user, $presentedPass, $ip));
    }

    public function testCheckPasswordSafeLockedIp()
    {
        $user = new Usr();
        $user->setPass($pass = '123456');
        $user->setLogin($login = 'logging');

        $ipLocker = $this->getAntiBruteforceLockerServiceMock($ip = '1.1.1.1', true, 'Your IP is locked!!!');

        $provider = new PasswordChecker(
            $ipLocker,
            $this->neverUsed($this->getAntiBruteforceLockerServiceMock()),
            $this->neverUsed($this->getLoggerInterfaceMock()),
            $this->neverUsed($this->getEncoderFactoryMock()),
            $this->neverUsed($this->getEntityManagerMock()),
            "xxx"
        );

        $this->assertFalse($provider->checkPasswordSafe($user, $pass, $ip));
    }

    public function testCheckPasswordSafeLockedLogin()
    {
        $user = new Usr();
        $user->setPass($pass = '123456');
        $user->setLogin($login = 'logging');

        $ipLocker = $this->getAntiBruteforceLockerServiceMock($ip = '1.1.1.1', true, null);
        $loginLocker = $this->getAntiBruteforceLockerServiceMock($login, false, 'Your login is locked!!!');

        $provider = new PasswordChecker(
            $ipLocker,
            $loginLocker,
            $this->neverUsed($this->getLoggerInterfaceMock()),
            $this->neverUsed($this->getEncoderFactoryMock()),
            $this->neverUsed($this->getEntityManagerMock()),
            "xxx"
        );

        $this->assertFalse($provider->checkPasswordSafe($user, $pass, $ip));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|UserCheckerInterface
     */
    protected function getUserCheckerInterfaceMock()
    {
        return $this->createMock(UserCheckerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param string|int $clientCheckResult
     * @return Session
     */
    protected function getSessionMock($clientCheckResult = '')
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('client_check', [
            'result' => $clientCheckResult,
        ]);

        return $session;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function getLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|TwoFactorAuthenticationService
     */
    protected function getTwoFactorAuthenticationServiceMock($user = null)
    {
        $mock = $this->getMockBuilder(TwoFactorAuthenticationService::class)->disableOriginalConstructor()->getMock();

        if ($user) {
            $mock->expects($this->once())
                ->method('checkAuthentication')
                ->with($user);
        }

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|AntiBruteforceLockerService
     */
    protected function getAntiBruteforceLockerServiceMock($key = false, $readOnly = false, $return = null)
    {
        $mock = $this->getMockBuilder(AntiBruteforceLockerService::class)->disableOriginalConstructor()->getMock();

        $mock->expects($this->never())
            ->method('unlock');

        if (false !== $key) {
            $mock->expects($this->once())
                ->method('checkForLockout')
                ->with($key, $readOnly)
                ->willReturn($return);
        }

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|UserProviderInterface
     */
    protected function getUserProviderMock(?Usr $user = null)
    {
        $mock = $this->createMock(UserProviderInterface::class);

        if (null !== $user) {
            $mock->expects($this->once())
                ->method('loadUserByUsername')
                ->with($user->getLogin())
                ->willReturn($user);
        }

        return $mock;
    }

    /**
     * @param string $presentedPassword
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|EncoderFactoryInterface
     */
    protected function getEncoderFactoryMock(?Usr $user = null, $presentedPassword = null)
    {
        $encoderFactory = $this->createMock(EncoderFactoryInterface::class);

        if (null !== $user) {
            $encoder = $this->createMock(PasswordEncoderInterface::class);

            $encoder->expects($this->once())
                ->method('isPasswordValid')
                ->with(
                    $user->getPassword(),
                    $presentedPassword
                )->willReturn($presentedPassword === $user->getPassword());

            $encoderFactory->expects($this->once())
                ->method('getEncoder')
                ->willReturn($encoder);
        }

        return $encoderFactory;
    }

    /**
     * @param string|int $clientCheckResult
     * @return RequestStack
     */
    protected function getRequestStack($ip, $clientCheckResult = '')
    {
        $stack = new RequestStack();
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $ip]);

        $request->headers->set('X-Scripted', $clientCheckResult);

        $stack->push($request);

        return $stack;
    }
}
