<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Mailer;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Test\Test;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use Codeception\Util\Stub;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class SendLoggingCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testTemplate(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $statLogged = false;
        $trackingId = null;

        $I->mockService("monolog.logger.email", $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Stub::atLeastOnce(function ($message, array $context = []) use (&$statLogged, $I, $user, &$trackingId) {
                if ($message === "email sent") {
                    $I->assertArrayHasKey("id", $context);
                    $trackingId = $context["id"];
                    unset($context["id"]);
                    $I->assertEquals([
                        "to" => "some@bad.email",
                        "from" => 'info@awardwallet.com',
                        "subject" => 'Welcome to AwardWallet.com',
                        "UserID" => $user->getUserid(),
                        'template' => 'welcome_to_aw',
                        'kind' => 'welcome_to_aw',
                        'businessArea' => false,
                        'lang' => 'en',
                        'locale' => 'en',
                        'enableUnsubscribe' => true,
                        'extraContext' => 'blah',
                    ], $context);
                    $statLogged = true;
                }
            }),
        ]));

        $template = new WelcomeToAw($user);
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $message = $mailer->getMessageByTemplate($template);
        $message->addContext(["extraContext" => "blah"]);
        $message->setTo("some@bad.email");
        $I->assertTrue($mailer->send($message));
        $I->assertTrue($statLogged);

        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString($trackingId . ".png", $email);

        $links = $this->extractLinks($email);
        $I->assertNotEmpty($links);

        $mailLink = 'mailto:' . $user->getLogin() . '@AwardWallet.com';
        $I->assertContains($mailLink, $links);
        unset($links[array_search($mailLink, $links)]);

        $protoAndHost = $I->getContainer()->getParameter('requires_channel') . '://' . $I->getContainer()->getParameter('host');
        $I->assertContains($protoAndHost . "/faqs?emtr={$trackingId}#10", $links);
        $sha = sha1("award.travel/101" . $I->getContainer()->getParameter("email_tracking_salt"));
        $I->assertContains($protoAndHost . "/t/{$trackingId}/e/{$sha}/award.travel/101", $links);

        foreach ($links as $link) {
            $I->assertStringContainsString($trackingId, $link);
        }

        // text part
        $text = $I->grabLastMail()->getBody();
        $I->assertTrue(strpos($text, "Thank you for") === 0);
        $I->assertStringContainsString("/faqs?emtr={$trackingId}#10", $text);
    }

    public function testNotAUser(\TestSymfonyGuy $I)
    {
        $statLogged = false;
        $trackingId = null;

        $I->mockService("monolog.logger.email", $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Stub::atLeastOnce(function ($message, array $context = []) use (&$statLogged, $I, &$trackingId) {
                if ($message === "email sent") {
                    $I->assertArrayHasKey("id", $context);
                    $I->assertEquals(32, strlen($context['id']));
                    $trackingId = $context["id"];
                    unset($context["id"]);
                    $I->assertEquals([
                        "to" => "some@bad.email",
                        "from" => 'info@awardwallet.com',
                        "subject" => 'Test message',
                        'template' => 'test',
                        'kind' => 'test',
                        'businessArea' => false,
                        'lang' => 'en',
                        'locale' => 'en',
                        'enableUnsubscribe' => true,
                    ], $context);
                    $statLogged = true;
                }
            }),
        ]));

        $template = new Test();
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $message = $mailer->getMessageByTemplate($template);
        $message->setTo("some@bad.email");
        $I->assertTrue($mailer->send($message));
        $I->assertTrue($statLogged);

        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString($trackingId . ".png", $email);
        $I->assertStringNotContainsString("logo.png", $email);
    }

    public function testNoOpenTracking(\TestSymfonyGuy $I)
    {
        $template = new Test();
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $message = $mailer->getMessageByTemplate($template);
        $message->setTo("some@bad.email");
        $I->assertTrue($mailer->send($message, [Mailer::OPTION_EXTERNAL_OPEN_TRACKING => false]));

        $email = $I->grabLastMail()->toString();
        $I->assertStringContainsString('"open_tracking":false', $email);
    }

    public function testNoClickTracking(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $template = new WelcomeToAw($user);
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $message = $mailer->getMessageByTemplate($template);
        $message->setTo("some@bad.email");
        $I->assertTrue($mailer->send($message, [Mailer::OPTION_EXTERNAL_CLICK_TRACKING => false]));

        $email = $I->grabLastMail()->toString();
        $I->assertStringContainsString('"click_tracking":false', $email);
    }

    private function extractLinks(string $email): array
    {
        if (!preg_match_all('#href=[\'"]([^\'"]+)[\'"]#ims', $email, $matches, PREG_SET_ORDER)) {
            throw new \Exception("Failed to extract links from email");
        }

        return array_map(function (array $set) { return $set[1]; }, $matches);
    }
}
