<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Security\Reauthentication\MobilePushCodeSender;

/**
 * @group frontend-unit
 * @group security
 */
class MobilePushCodeSenderTest extends AbstractReauthenticatorTest
{
    public function testSuccessSend()
    {
        $this->markTestSkipped('how to send push notifications to secure devices');
        $code = '123456';
        $mobileSender = $this->getMobileSenderMock($this->authUser->getEntity(), true);

        $sender = new MobilePushCodeSender(
            $mobileSender,
            $this->container->get('translator'),
            new TimeCommunicator()
        );
        $result = $sender->send($this->authUser, $code, $this->environment);

        $this->assertTrue($result->success);
        $this->assertEquals('mobile device', $result->recepient);
    }

    public function testFailSend()
    {
        $this->markTestSkipped('how to send push notifications to secure devices');
        $code = '123456';
        $mobileSender = $this->getMobileSenderMock($this->authUser->getEntity(), false);

        $sender = new MobilePushCodeSender(
            $mobileSender,
            $this->container->get('translator'),
            new TimeCommunicator()
        );
        $result = $sender->send($this->authUser, $code, $this->environment);

        $this->assertFalse($result->success);
    }
}
