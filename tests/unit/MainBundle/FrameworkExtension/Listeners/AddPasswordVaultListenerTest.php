<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Passwordvault;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Event\AddPasswordVaultEvent;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\AddPasswordVaultListener;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class AddPasswordVaultListenerTest extends BaseTest
{
    /** @var EntityManager */
    private $em;

    public function _before()
    {
        parent::_before();
        $container = $this->getModule('Symfony')->_getContainer();
        $this->em = $container->get('doctrine.orm.entity_manager');
    }

    public function _after()
    {
        $this->em = null;
        parent::_after();
    }

    public function testByAccount()
    {
        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())->method('getMessage')->willReturn(new Message());
        $mailer->expects($this->once())->method('send');

        $appBot = $this->createMock(AppBot::class);
        $appBot->expects($this->once())->method('send');

        $userId = $this->aw->createAwUser('login' . StringUtils::getRandomCode(8), null);
        $accountId = $this->aw->createAwAccount(
            $userId,
            $provider = 'testprovider',
            $login = 'accountlogin' . StringUtils::getRandomCode(8)
        );

        $event = new AddPasswordVaultEvent($provider, $login, $pass = StringUtils::getRandomCode(8), null, null,
            $userId, 'awardwallet', [], $accountId, 'some note for request');

        $listener = new AddPasswordVaultListener(new NullLogger(), $mailer, $this->em, $appBot, '');
        $listener->onAddPasswordVault($event);

        $pv = $this->em->getRepository(Passwordvault::class)->findOneBy(['accountid' => $accountId]);
        $this->assertInstanceOf(Passwordvault::class, $pv);

        if ($pv instanceof Passwordvault) {
            $this->assertEquals($accountId, $pv->getAccountid()->getId());
            $this->assertEquals($userId, $pv->getUserid()->getUserid());
            $this->assertEquals($login, $pv->getLogin());
            $this->assertEquals($pass, $pv->getPass());
        }
    }

    public function testWithoutAccount()
    {
        $provider = $this->em->getRepository(Provider::class)->findOneBy(['code' => 'testprovider']);
        $login = 'accountlogin' . StringUtils::getRandomCode(8);

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())->method('getMessage')->willReturn(new Message());
        $mailer->expects($this->once())->method('send');

        $appBot = $this->createMock(AppBot::class);
        $appBot->expects($this->once())->method('send');

        $event = new AddPasswordVaultEvent($provider->getCode(), $login, $pass = StringUtils::getRandomCode(8), null, null,
            null, 'awardwallet');

        $listener = new AddPasswordVaultListener(new NullLogger(), $mailer, $this->em, $appBot, '');
        $listener->onAddPasswordVault($event);

        $pv = $this->em->getRepository(Passwordvault::class)->findOneBy(['login' => $login, 'providerid' => $provider]);
        $this->assertInstanceOf(Passwordvault::class, $pv);

        if ($pv instanceof Passwordvault) {
            $this->assertEquals($login, $pv->getLogin());
            $this->assertEquals($pass, $pv->getPass());
        }
    }
}
