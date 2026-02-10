<?php

namespace AwardWallet\Tests\Unit\Security
;

use AwardWallet\Common\Memcached\MemcachedMock;
use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\TwoFactorAuth;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationException;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Prophecy\Argument;

/**
 * @group security
 * @group frontend-unit
 */
class TwoFactorAuthenticationServiceTest extends BaseContainerTest
{
    /**
     * @var TwoFactorAuthenticationService
     */
    private $service;
    private $oldEm;
    private $oldMemcached;

    public function _before()
    {
        parent::_before();
        $this->oldEm = $oldEm = $this->container->get('doctrine.orm.default_entity_manager');

        $connection = $this->prophesize(Connection::class)
            ->prepare(Argument::any())
            ->willReturn($this->prophesize(Statement::class)->reveal())
            ->getObjectProphecy()
            ->reveal();

        $this->mockService('doctrine.orm.default_entity_manager',
            $this
                ->prophesize(EntityManager::class)

                ->getRepository(Argument::any())
                ->will(function ($repository) use ($oldEm) {
                    return $oldEm->getRepository($repository[0]);
                })
                ->getObjectProphecy()

                ->persist(Argument::cetera())
                ->willReturn()
                ->getObjectProphecy()

                ->flush(Argument::cetera())
                ->willReturn()
                ->getObjectProphecy()

                ->getConnection()
                ->willReturn($connection)
                ->getObjectProphecy()

                ->reveal()
        );
        $this->oldMemcached = $this->container->get(\Memcached::class);
        $this->mockService(\Memcached::class, new MemcachedMock());
        $this->service = $this->container->get(TwoFactorAuthenticationService::class);
    }

    public function _after()
    {
        $this->mockService('doctrine.orm.default_entity_manager', $this->oldEm);
        $this->mockService(\Memcached::class, $this->oldMemcached);

        $this->service =
        $this->oldMemcached =
        $this->oldEm = null;

        parent::_after();
    }

    public function testTwoFactorConfirmAuth()
    {
        $user = $this->getUser();
        $user->setEmail('test@mail.com');
        $this->expectEmail();

        $gen = $this->service->getAuthenticator();
        $secret = $this->service->generateSecret();
        $code = $gen->getCode($secret);
        $this->service->storeCheckpoint($user, $secret, substr($code, 0, 3) . ' ' . substr($code, 3));
        $this->assertNull($user->getGoogleAuthRecoveryCode());
        $this->assertNull($user->getGoogleAuthSecret());
        /** @var MemcachedMock $session */
        $session = $this->container->get(\Memcached::class);
        [$secret2, $recovery] = $session->get(TwoFactorAuthenticationService::SESSION_CHECKPOINT_PREFIX . '_' . $secret . '_' . $user->getUserid());
        $this->assertEquals($secret, $secret2);

        $this->assertEquals(null, $user->getGoogleAuthSecret());
        $this->assertEquals(null, $user->getGoogleAuthRecoveryCode());

        $this->service->saveTwoFactorCredentials($user, $secret);

        $decryptor = $this->container->get(PasswordDecryptor::class);
        $this->assertEquals($secret, $decryptor->decrypt($user->getGoogleAuthSecret()));
        $this->assertEquals($recovery, $decryptor->decrypt($user->getGoogleAuthRecoveryCode()));
    }

    public function testTwoFactorCancel()
    {
        $user = $this->getUser();
        $user->setEmail('test@mail.com');
        $this->expectEmail();

        $user->setGoogleAuthRecoveryCode($this->service->generateRecoveryCode());
        $user->setGoogleAuthSecret($this->service->generateSecret());

        $this->service->cancelTwoFactor($user);

        $this->assertEquals(null, $user->getGoogleAuthSecret());
        $this->assertEquals(null, $user->getGoogleAuthRecoveryCode());
    }

    public function testStoreCheckpointShouldFailWithTwoFactorAlreadyTurnedOn()
    {
        $this->expectException(TwoFactorAuthenticationException::class);
        $this->expectExceptionMessage('AwardWallet two-factor authentication already enabled');
        $user = $this->getUser()
            ->setGoogleAuthSecret($secret = $this->service->generateSecret())
            ->setGoogleAuthRecoveryCode($this->service->generateRecoveryCode());

        $this->service->storeCheckpoint($user, $secret, $this->service->getAuthenticator()->getCode($secret));
    }

    public function testSaveCredentialsShouldFailWithTwoFactorAlreadyTurnedOn()
    {
        $this->expectException(TwoFactorAuthenticationException::class);
        $this->expectExceptionMessage('AwardWallet two-factor authentication already enabled');
        $user = $this->getUser()
            ->setGoogleAuthSecret($secret = $this->service->generateSecret())
            ->setGoogleAuthRecoveryCode($this->service->generateRecoveryCode());

        $this->service->storeCheckpoint($user, $secret, $this->service->getAuthenticator()->getCode($secret));
        $this->service->saveTwoFactorCredentials($user, $secret);
    }

    public function testFreeAccountQRCodeShouldNotFail()
    {
        $user = $this->getUser();
        $this->service->generateOtpImage($user, $this->service->generateSecret(), 'host', 'AwardWallet', 'png');
    }

    public function testQrCodeGeneration()
    {
        $user = $this->getUser();
        $user->setLogin('testuser');
        $qrCode = $this->service->generateOtpImage($user, 'OVEK7TIJ3A3DM3M6', 'awardwallet.com', 'AwardWallet', 'png');
        $this->assertEquals('af6b3db3fd163af891dca4030b4b8d01', md5($qrCode));
    }

    protected function _expectEmail(InvocationOrder $matcher)
    {
        $mailer = $this->getMockBuilder(Mailer::class)
            ->disableOriginalConstructor()->getMock();

        $mailer->expects(clone $matcher)
            ->method('getMessageByTemplate')
            ->with($this->isInstanceOf(TwoFactorAuth::class))
            ->willReturn(new Message());

        $mailer->expects(clone $matcher)
            ->method('send')
            ->with($this->isInstanceOf(Message::class));

        $this->container->set('aw.email.mailer', $mailer);
    }

    protected function dontExpectEmail()
    {
        $this->_expectEmail($this->never());
    }

    protected function expectEmail()
    {
        $this->_expectEmail($this->once());
    }

    protected function getUser()
    {
        $user = (new Usr())->setLogin('login');

        $property = new \ReflectionProperty('\\AwardWallet\\MainBundle\\Entity\\Usr', 'userid');
        $property->setAccessible(true);
        $property->setValue($user, rand(10000, 20000));
        $property->setAccessible(false);

        return $user;
    }
}
