<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Controller\Manager\EmailViewerController;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\RelaySelector;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * @group frontend-unit
 */
class MailerTest extends BaseUserTest
{
    private const INVALID_EMAIL = 'sdfsdfd@,,,,aol.com';
    /**
     * @var Mailer
     */
    private $mailer;

    private $testEmail;

    public function _before()
    {
        parent::_before();
        $this->mailer = $this->container->get('aw.email.mailer');
        $this->testEmail = StringHandler::getRandomCode(20) . '@test.com';
    }

    public function testMailTransport()
    {
        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('start');
        $transport->expects($this->once())->method('send');
        $message = $this->getTestMessage();
        $this->mailer->addKindHeader('test', $message);
        $this->mailer->send($message, [
            Mailer::OPTION_TRANSPORT => $transport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    public function testSharedMessage()
    {
        $message1 = $this->mailer->getMessage();
        $message2 = $this->mailer->getMessage();
        $this->assertNotEquals(spl_object_hash($message1), spl_object_hash($message2));
        $this->assertNotEquals($message1->getId(), $message2->getId());
    }

    public function testDefaultHeaders()
    {
        $message = $this->mailer->getMessage();
        $this->assertEquals($this->mailer->getEmail('ndr'), $message->getReturnPath());
        $this->assertEquals(current($this->mailer->getEmail('bcc')), key($message->getBcc()));
        $this->assertEquals(key($this->mailer->getEmail('from')), key($message->getFrom()));
    }

    public function testKindHeader()
    {
        $message = $this->mailer->getMessage();
        $headers = $message->getHeaders();
        $this->assertFalse($headers->has(Mailer::HEADER_KIND));
        $this->mailer->addKindHeader('test_message', $message);
        $this->assertTrue($headers->has(Mailer::HEADER_KIND));
        $this->assertEquals($headers->get(Mailer::HEADER_KIND)->getFieldName(), Mailer::HEADER_KIND);
        $this->assertEquals($headers->get(Mailer::HEADER_KIND)->getFieldBodyModel(), 'test_message');

        $message2 = $this->mailer->getMessageByTemplate($this->getTestMailTemplate());
        $headers = $message2->getHeaders();
        $this->assertTrue($headers->has(Mailer::HEADER_KIND));
        $this->assertEquals($headers->get(Mailer::HEADER_KIND)->getFieldBodyModel(), 'welcome_to_aw');
        $this->mailer->addKindHeader('test_message', $message2);
        $this->assertEquals($headers->get(Mailer::HEADER_KIND)->getFieldBodyModel(), 'test_message');

        $message3 = $this->mailer->getMessage('text_xxx');
        $headers = $message3->getHeaders();
        $this->assertTrue($headers->has(Mailer::HEADER_KIND));
        $this->assertEquals($headers->get(Mailer::HEADER_KIND)->getFieldBodyModel(), 'text_xxx');
        $this->assertEmpty($message3->getTo());
        $this->assertEmpty($message3->getSubject());
        $message3 = $this->mailer->getMessage('text_xxx', $this->testEmail, 'Test Subject');
        $this->assertEquals(key($message3->getTo()), $this->testEmail);
        $this->assertEquals($message3->getSubject(), 'Test Subject');
    }

    public function testSetTo()
    {
        $message = $this->mailer->getMessage();
        $this->mailer->addKindHeader('test', $message);
        $message->setTo("one@awardwallet.com");
        $message->setTo("two@awardwallet.com");
        $this->assertCount(1, $message->getTo());
        $this->assertEquals("two@awardwallet.com", array_keys($message->getTo())[0]);
    }

    public function testBody()
    {
        $message = $this->mailer->getMessage();
        $this->mailer->addKindHeader('test', $message);
        $message->setTo("one@awardwallet.com");
        $message->setBody("first", "text/html");
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->assertStringContainsString('first', $message->getBody());

        $message = $this->mailer->getMessage();
        $this->mailer->addKindHeader('test', $message);
        $message->setTo("two@awardwallet.com");
        $message->setBody("second", "text/html");
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->assertStringNotContainsString('first', $message->toString());
        $this->assertStringContainsString('second', $message->toString());
    }

    public function testEmptyToBccCc()
    {
        $message = $this->getTestMessage();
        $message->setTo([]);
        $message->setCc([]);
        $message->setBcc([]);
        $this->assertFalse($this->mailer->send($message, [Mailer::OPTION_SKIP_STAT => true]));
    }

    public function testInvalidTo()
    {
        $message = $this->getTestMessage();
        $message->setTo(self::INVALID_EMAIL);
    }

    public function testInvalidCc()
    {
        $message = $this->getTestMessage();
        $message->setCc(self::INVALID_EMAIL);
    }

    public function testInvalidBcc()
    {
        $message = $this->getTestMessage();
        $message->setBcc(self::INVALID_EMAIL);
    }

    public function testClickTrackingOn()
    {
        $message = $this->mailer->getMessage(null, "some@user.com");
        $this->mailer->addKindHeader('test', $message);
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
            Mailer::OPTION_EXTERNAL_CLICK_TRACKING => true,
        ]);
        $this->assertStringNotContainsString('"click_tracking":false', $message->toString());
    }

    public function testClickTrackingOffByDefault()
    {
        $message = $this->mailer->getMessage(null, "some@user.com");
        $this->mailer->addKindHeader('test', $message);
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->assertStringContainsString('"click_tracking":false', $message->toString());
    }

    public function testOpenTrackingOff()
    {
        $message = $this->mailer->getMessage(null, "some@user.com");
        $this->mailer->addKindHeader('test', $message);
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
            Mailer::OPTION_EXTERNAL_OPEN_TRACKING => false,
        ]);
        $this->assertStringContainsString('"open_tracking":false', $message->toString());
    }

    public function testOpenTrackingOnByDefault()
    {
        $message = $this->mailer->getMessage(null, "some@email.com");
        $this->mailer->addKindHeader('test', $message);
        @$this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->assertStringNotContainsString('"open_tracking":false', $message->toString());
    }

    public function testAwEmailMessageService()
    {
        $container = $this->container;
        $message1 = $container->get('aw.email.message');
        $message2 = $container->get('aw.email.message');
        $this->assertNotEquals(spl_object_hash($message1), spl_object_hash($message2));
        $this->assertNotEquals($message1->getId(), $message2->getId());
        $this->assertEquals($this->mailer->getEmail('ndr'), $message1->getReturnPath());
        $this->assertEquals(current($this->mailer->getEmail('bcc')), key($message1->getBcc()));
        $this->assertEquals(key($this->mailer->getEmail('from')), key($message1->getFrom()));
    }

    public function testDeliveryToNDRAliases()
    {
        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), null, [], true /* staff user has access to test provider */);
        $this->assertNotNull($user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId));
        $message = $this->mailer->getMessage();
        $message->setTo($user->getEmail())
            ->setSubject('Test subject')
            ->setBody('Test body', 'text/plain');
        $message->getHeaders()->addTextHeader(Mailer::HEADER_KIND, 'test');

        // regular user - message should be sent
        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('start');
        $transport->expects($this->once())->method('send');

        $this->mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);

        // ndr user - message should not be sent
        $user->setEmailverified(EMAIL_NDR);
        $this->em->flush();
        $transport = $this->getTestTransport();
        $transport->expects($this->never())->method('send');
        $this->mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
        ]);

        // ndr user - forced sending
        $user->setEmailverified(EMAIL_NDR);
        $this->em->flush();
        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('send');
        $this->mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);

        // ndr user, but he have one of special @awardwallet.com emails - message should be sent
        // we do not want to modify mailer instance inside container, it will affect other tests
        $mailer = clone $this->mailer;
        $mailer->setEmails(['support' => $user->getEmail()]);
        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('send');
        $mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
        ]);
    }

    public function testMissingKindHeader()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('#^Unknown type of e-mail#');
        $message = $this->getTestMessage();
        $this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    public function testSubjectContainsLineBreaks()
    {
        $message = $this->getTestMessage();
        $this->mailer->addKindHeader('test', $message);
        $message->setSubject("Test\nsubject    ");
        $this->assertTrue(
            $this->mailer->send($message, [
                Mailer::OPTION_SKIP_DONOTSEND => true,
            ])
        );
    }

    public function testFailSendCallback()
    {
        $transport = $this->getTestTransport();
        $transportException = new \Swift_TransportException("Test exception");
        $transport->expects($this->any())->method('send')->will($this->throwException($transportException));
        $message = $this->getTestMessage();
        $this->mailer->addKindHeader('test', $message);

        $callback = $this->getMockBuilder('stdClass')->setMethods(['testcallback'])->getMock();
        $callback->expects($this->once())->method('testcallback')->with($this->isInstanceOf('\\Swift_TransportException'));
        $this->mailer->send($message, [
            Mailer::OPTION_ON_FAILED_SEND => [$callback, 'testcallback'],
            Mailer::OPTION_TRANSPORT => $transport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
            Mailer::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS => 0,
        ]);
    }

    public function testSendAttempts()
    {
        $transport = $this->getTestTransport();
        $transportException = new \Swift_TransportException("Test exception");
        $transport->expects($this->exactly(5))->method('send')->will($this->throwException($transportException));
        $message = $this->getTestMessage();
        $this->mailer->addKindHeader('test', $message);
        $mailerResult = $this->mailer->send($message, [
            Mailer::OPTION_SEND_ATTEMPTS => 5,
            Mailer::OPTION_TRANSPORT => $transport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
            Mailer::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS => 0,
        ]);
        $this->assertFalse($mailerResult);
    }

    public function testCustomTransport()
    {
        $customTransport = $this->getTestTransport();
        $customTransport->expects($this->once())->method('send');
        $message = $this->getTestMessage('test');
        $this->mailer->send($this->getTestMessage('test'), [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->mailer->send($message, [
            Mailer::OPTION_TRANSPORT => $customTransport,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
        $this->mailer->send($this->getTestMessage('test'), [
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    public function testSeparateBccTransport()
    {
        $customTransport = $this->getTestTransport();
        $customTransport->expects($this->once())->method('send');
        $message = $this->getTestMessage();
        $message->setBcc([$this->testEmail, 'two-' . $this->testEmail, 'three-' . $this->testEmail]);
        $this->mailer->addKindHeader('test', $message);
        $this->mailer->send($message, [
            Mailer::OPTION_TRANSPORT => $customTransport,
            Mailer::OPTION_SEPARATE_CC => true,
            Mailer::OPTION_SKIP_DONOTSEND => true,
        ]);
    }

    public function testDonNotSend()
    {
        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), null, [], true /* staff user has access to test provider */);
        $this->assertNotNull($user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId));
        $message = $this->mailer->getMessage();
        $message->setTo($user->getEmail())
            ->setSubject('Test subject')
            ->setBody('Test body', 'text/plain');
        $message->getHeaders()->addTextHeader(Mailer::HEADER_KIND, 'test');

        $this->db->executeQuery("DELETE FROM DoNotSend WHERE Email = '" . $user->getEmail() . "'");

        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('start');
        $transport->expects($this->once())->method('send');

        $this->mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
        ]);

        $this->db->executeQuery("INSERT INTO DoNotSend (Email, AddTime, IP) VALUES ('" . $user->getEmail() . "', '2020-12-12 12:12:12', '192.168.10.10')");

        $transport = $this->getTestTransport();
        $transport->expects($this->never())->method('send');
        $this->mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
        ]);

        $mailer = clone $this->mailer;
        $mailer->setEmails(['support' => $user->getEmail()]);
        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('send');
        $mailer->send(clone $message, [
            Mailer::OPTION_TRANSPORT => $transport,
        ]);
    }

    public function testUnsubscribeLink()
    {
        $template = new WelcomeToAw($this->user);
        $message = $this->mailer->getMessageByTemplate($template);

        $this->assertMatchesRegularExpression("/https?:\/\/" . $this->container->getParameter("host") . "\/unsubscribe/ims", $message->getBody());

        $template->enableUnsubscribe(false);
        $message2 = $this->mailer->getMessageByTemplate($template);

        $this->assertDoesNotMatchRegularExpression("/https?:\/\/" . $this->container->getParameter("host") . "\/unsubscribe/ims", $message2->getBody());
    }

    public function testServiceEmailAddress()
    {
        $from = $this->mailer->getEmail('from');
        $this->assertTrue(is_array($from));
        $this->assertEquals('info@awardwallet.com', strtolower(key($from)));
        $this->assertEquals('awardwallet', strtolower(current($from)));
        $this->mailer->setEmails([
            'from' => $this->testEmail,
        ]);
        $this->assertEquals($this->testEmail, $this->mailer->getEmail('from'));
        $this->mailer->setEmails([
            'from' => [$this->testEmail => 'Abc'],
        ]);
        $this->assertEquals([$this->testEmail => 'Abc'], $this->mailer->getEmail('from'));
    }

    public function testSendAllEmails()
    {
        $data = EmailViewerController::scanTemplates(
            EmailViewerController::TEMPLATE_PATH,
            EmailViewerController::TEMPLATE_NAMESPACE
        );
        $this->sendEmails($data);
    }

    public function testNontransactionalRelay()
    {
        $this->modifyRelays(function (RelaySelector $relaySelector) {
            $relaySelector->relays["nontransactional"] = [
                "host" => "localhost",
                "port" => 9999999999, // non-existent
            ];
        });
        $message = $this->getTestMessage();
        $message->setSubject("NonTransRelay");
        $message->getHeaders()->addTextHeader(Mailer::HEADER_KIND, 'test');
        $this->assertFalse($this->mailer->send([$message], [Mailer::OPTION_TRANSACTIONAL => false, Mailer::OPTION_DIRECT => false, Mailer::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS => 0]));
    }

    public function testNontransactionalDirectRelay()
    {
        $this->modifyRelays(function (RelaySelector $relaySelector) {
            $relaySelector->relays["nontransactional_direct"] = [
                "host" => "localhost",
                "port" => 9999999999, // non-existent
            ];
        });

        $message = $this->getTestMessage();
        $message->setSubject("NonTransRelay");
        $message->getHeaders()->addTextHeader(Mailer::HEADER_KIND, 'test');
        $this->assertFalse($this->mailer->send([$message], [Mailer::OPTION_TRANSACTIONAL => false, Mailer::OPTION_DELAY_BETWEEN_FAILED_ATTEMPTS => 0]));
    }

    public function testNontransactionalNoRelay()
    {
        $this->modifyRelays(function (RelaySelector $relaySelector) {
            unset($relaySelector->relays["nontransactional"]);
        });

        $message = $this->getTestMessage();
        $message->setSubject("NonTransNoRelay");
        $message->getHeaders()->addTextHeader(Mailer::HEADER_KIND, 'test');
        $this->assertTrue($this->mailer->send([$message], [Mailer::OPTION_TRANSACTIONAL => false]));
    }

    public function testForceLocale()
    {
        $template = new WelcomeToAw($this->user);
        $message = $this->mailer->getMessageByTemplate($template);
        $this->assertStringContainsString('Thank you for registering', $message->getBody());

        $template->setRegionalSettings(null, 'fr');
        $message = $this->mailer->getMessageByTemplate($template);
        $this->assertStringContainsString('Merci de vous être', $message->getBody());

        $template->setRegionalSettings(null, 'en');
        $this->user->setLanguage('fr');
        $message = $this->mailer->getMessageByTemplate($template);
        $this->assertStringContainsString('Thank you for registering', $message->getBody());
    }

    /**
     * @dataProvider yahooTransactionalDataProvider
     */
    public function testYahooTransactional(string $email, ?bool $transactional, ?string $expectedPool)
    {
        $message = $this->getTestMessage('test');
        $message->setSubject("Test subject");
        $message->setTo([$email]);

        $transport = $this->getTestTransport();
        $transport->expects($this->once())->method('start');
        $transport->expects($this->once())->method('send')->willReturnCallback(function (\Swift_Mime_SimpleMessage $message) use ($expectedPool) {
            if ($expectedPool) {
                $constraint = $this->stringContains('"ip_pool":"' . $expectedPool . '"');
            } else {
                $constraint = $this->logicalNot($this->stringContains('"ip_pool":"'));
            }

            $this->assertThat($message->getHeaders()->get('X-MSYS-API')->toString(), $constraint);
        });

        $options = [
            Mailer::OPTION_TRANSPORT => $transport,
        ];

        if ($transactional !== null) {
            $options[Mailer::OPTION_TRANSACTIONAL] = $transactional;
        }

        $this->mailer->send(clone $message, $options);
    }

    public function yahooTransactionalDataProvider()
    {
        return [
            ['email' => 'some@yahoo.com', 'transactional' => null, 'expectedPool' => "transactional"],
            ['email' => 'some@yahoo.com', 'transactional' => false, 'expectedPool' => "marketing"],
            ['email' => 'some@yahoo.com', 'transactional' => true, 'expectedPool' => "transactional"],
            ['email' => 'some@yahoo.com.au', 'transactional' => true, 'expectedPool' => "transactional"],

            ['email' => 'some@gmail.com', 'transactional' => true, 'expectedPool' => null],
        ];
    }

    protected function sendEmails(array $data)
    {
        foreach ($data as $row) {
            if ($row['type'] == 'dir') {
                $this->sendEmails($row['files']);
            } else {
                $class = $row['class'];

                if (call_user_func("$class::getStatus") == AbstractTemplate::STATUS_NOT_READY) {
                    continue;
                }
                /** @var AbstractTemplate $template */
                $template = call_user_func_array("$class::createFake", [$this->container]);
                $mailer = $this->container->get('aw.email.mailer');
                $message = $mailer->getMessageByTemplate($template);
                $message->setTo($this->container->getParameter('mailer_delivery_address'));
                $this->assertTrue($mailer->send($message, [
                    Mailer::OPTION_SKIP_DONOTSEND => true,
                    Mailer::OPTION_SKIP_STAT => true,
                ]));
            }
        }
    }

    protected function getTestTransport()
    {
        return $this
            ->getMockBuilder('\\Swift_Transport')
            ->setMethods([
                'isStarted', 'start', 'stop', 'send', 'registerPlugin', 'ping',
            ])
            ->getMock();
    }

    protected function getTestMessage(?string $kind = null)
    {
        $message = $this->mailer->getMessage();
        $message->setTo($this->testEmail)
            ->setSubject('Test subject')
            ->setBody('Test body', 'text/plain');

        if ($kind !== null) {
            $this->mailer->addKindHeader('test', $message);
        }

        return $message;
    }

    protected function getTestMailTemplate()
    {
        $template = WelcomeToAw::createFake($this->container);
        $template->setEmail($this->testEmail);

        return $template;
    }

    private function modifyRelays(callable $relaySelectorModifier)
    {
        $mailerModifier = function (Mailer $mailer) use ($relaySelectorModifier) {
            $relaySelectorModifier = \Closure::bind($relaySelectorModifier, null, $mailer->relaySelector);
            $relaySelectorModifier($mailer->relaySelector);
        };
        $mailerModifier = \Closure::bind($mailerModifier, null, $this->mailer);
        $mailerModifier($this->mailer);
    }
}
