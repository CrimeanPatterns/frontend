<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Security\Reauthentication\EmailCodeSender;

/**
 * @group frontend-unit
 * @group security
 */
class EmailCodeSenderTest extends AbstractReauthenticatorTest
{
    public function testSuccessSend()
    {
        $code = '123456';
        $geo = $this->getGeoMock(['ip' => $ip = '1.1.1.1', 'name' => $testLocation = 'Test Location'], $testLocation);
        $mailer = $this->getMailerMock($ip, $testLocation, $code, true);

        $sender = new EmailCodeSender(
            $geo,
            $mailer,
            $this->container->get('translator'),
            new TimeCommunicator()
        );
        $result = $sender->send($this->authUser, $code, $this->environment);

        $this->assertTrue($result->success);
        $this->assertEquals('test@test.com', $result->recepient);
    }

    public function testFailSend()
    {
        $code = '123456';
        $geo = $this->getGeoMock(['ip' => $ip = null, 'name' => $testLocation = 'unknown'], $testLocation);
        $mailer = $this->getMailerMock($ip, $testLocation, $code, false);

        $sender = new EmailCodeSender(
            $geo,
            $mailer,
            $this->container->get('translator'),
            new TimeCommunicator()
        );
        $result = $sender->send($this->authUser, $code, $this->environment);

        $this->assertFalse($result->success);
    }
}
