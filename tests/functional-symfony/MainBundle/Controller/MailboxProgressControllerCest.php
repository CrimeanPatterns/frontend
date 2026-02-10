<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\Tests\Modules\Utils\Prophecy\ArgumentExtended as Argument;
use Prophecy\Prophet;

/**
 * @group frontend-functional
 */
class MailboxProgressControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testProgressUnauthorized(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('wronguser:wrongpass'));
        $I->sendPOST('/api/awardwallet/mailbox-progress');
        $I->seeResponseCodeIs(401);
    }

    public function testProgressBadData(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('awardwallet:' . $I->grabService("service_container")->getParameter("email.callback_password")));
        $I->sendPOST('/api/awardwallet/mailbox-progress');
        $I->seeResponseCodeIs(400);
    }

    public function testProgressSuccess(\TestSymfonyGuy $I)
    {
        $prophet = new Prophet();
        $centrifuge = $prophet->prophesize(\AwardWallet\MainBundle\Service\SocksMessaging\Client::class);
        $centrifuge
            ->publish('$mailboxes_1234', Argument::containsArray(['id' => "1", 'status' => 'Successfully connected and waiting for new emails to arrive.', 'icon' => 'icon-green-check']))
            ->shouldBeCalled();

        $I->mockService(\AwardWallet\MainBundle\Service\SocksMessaging\NullClient::class, $centrifuge->reveal());

        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('awardwallet:' . $I->grabService("service_container")->getParameter("email.callback_password")));
        $I->sendPOST('/api/awardwallet/mailbox-progress', json_encode([[
            'event' => 'state_change',
            'mailbox' => [
                'type' => 'google',
                'id' => 1,
                'state' => 'listening',
                'tags' => ['user_1234'],
            ],
        ]]));
        $I->seeResponseCodeIs(200);

        $prophet->checkPredictions();
    }

    public function testEmailOnConnectionLost(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('awardwallet:' . $I->grabService("service_container")->getParameter("email.callback_password")));
        $userId = $I->createAwUser();
        $I->sendPOST('/api/awardwallet/mailbox-progress', json_encode([[
            'event' => 'state_change',
            'mailbox' => [
                'type' => 'google',
                'email' => 'some@mailbox.com',
                'id' => 1,
                'state' => 'error',
                'errorCode' => 'authentication',
                'tags' => ['user_' . $userId],
                'userData' => '{"user":' . $userId . '}',
            ],
        ]]));
        $I->seeResponseCodeIs(200);

        $mail = $I->grabLastMail();
        $I->assertStringContainsString("lost the connection to your mailbox", $mail->getBody());
        $I->assertStringContainsString("some@mailbox.com", $mail->getBody());
    }

    public function testNoEmailOnRepeatedConnectionLost(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('awardwallet:' . $I->grabService("service_container")->getParameter("email.callback_password")));
        $userId = $I->createAwUser();
        $I->sendPOST('/api/awardwallet/mailbox-progress', json_encode([[
            'event' => 'state_change',
            'mailbox' => [
                'type' => 'google',
                'id' => 1,
                'state' => 'error',
                'errorCode' => 'authentication',
                'tags' => ['user_' . $userId],
                'userData' => '{"user":' . $userId . ', "lastNotification": ' . time() . '}',
            ],
        ]]));
        $I->seeResponseCodeIs(200);

        $mail = $I->grabLastMail();
        $I->assertNull($mail->getBody());
    }
}
