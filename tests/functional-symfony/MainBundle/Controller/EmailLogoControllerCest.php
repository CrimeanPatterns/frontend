<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Test\Test;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Test\TestTrackingPixel;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use Codeception\Util\Stub;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-functional
 */
class EmailLogoControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testUser(\TestSymfonyGuy $I)
    {
        $statLogged = false;

        $I->mockService("monolog.logger.email", $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Stub::atLeastOnce(function ($message, array $context = []) use (&$statLogged, $I) {
                if ($message === "email opened") {
                    $I->assertArrayHasKey("id", $context);
                    $I->assertEquals(32, strlen($context['id']));
                    unset($context["id"]);
                    $I->assertTrue($context['database_record_exists']);
                    $I->assertEquals('welcome_to_aw', $context['kind']);
                    $I->assertEquals('Symfony BrowserKit', $context['user_agent']);
                    $statLogged = true;
                }
            }),
        ]));

        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());

        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $template = new WelcomeToAw($user);
        $message = $mailer->getMessageByTemplate($template);
        $I->assertTrue($mailer->send($message));
        $trackingId = $this->extractTrackingId($I);

        $I->amOnRoute("aw_email_logo", ["userId" => $user->getUserid(), "hash" => $user->getEmailVerificationHash(), "trackingId" => $trackingId]);
        $this->testEmailIsOpen($I, 1, $trackingId);
        $I->assertTrue($statLogged);
    }

    public function testNotAUser(\TestSymfonyGuy $I)
    {
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $template = new Test();
        $message = $mailer->getMessageByTemplate($template);
        $message->setTo("some@email.com");
        $I->assertTrue($mailer->send($message));
        $trackingId = $this->extractTrackingId($I);

        $I->amOnRoute("aw_email_logo", ["userId" => 0, "hash" => "somechars", "trackingId" => $trackingId]);
        $I->assertTrue($I->grabResponse() === file_get_contents(__DIR__ . '/../../../../web/images/email/newdesign/logo.png'));
        $this->testEmailIsOpen($I, 1, $trackingId);
    }

    public function testTrackingPixel(\TestSymfonyGuy $I)
    {
        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $template = new TestTrackingPixel();
        $message = $mailer->getMessageByTemplate($template);
        $message->setTo("some@email.com");
        $I->assertTrue($mailer->send($message));
        $trackingPixel = $this->extractTrackingPixel($I);

        if (preg_match('#/emtrpx/(\w+)\.png#ims', $trackingPixel, $matches)) {
            $trackingId = $matches[1];
        } else {
            $I->fail("failed to extract tracking id from $trackingPixel");
        }

        $I->assertNotEquals("0000", $trackingId);

        $I->amOnPage($trackingPixel);
        $I->assertEquals($I->grabResponse(), file_get_contents(__DIR__ . '/../../../../web/images/email/newdesign/emtrpx.png'));
        $this->testEmailIsOpen($I, 1, $trackingId);
    }

    private function testEmailIsOpen(\TestSymfonyGuy $I, int $expectedOpenCount, string $trackingId)
    {
        $row = $I->query("select * from SentEmail where ID = ?", [hex2bin($trackingId)])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals($expectedOpenCount, $row["OpenCount"]);
        $I->assertNotNull($row["FirstOpenDate"]);
        $I->assertNotNull($row["LastOpenDate"]);
    }

    private function extractTrackingId(\TestSymfonyGuy $I)
    {
        $body = $I->grabLastMailMessageBody();

        if (!preg_match('#/logo/\w+/\w+/(\w+)\.png#ims', $body, $matches)) {
            $I->fail("failed to extract trackingId from body");
        }

        return $matches[1];
    }

    private function extractTrackingPixel(\TestSymfonyGuy $I): string
    {
        $body = $I->grabLastMailMessageBody();

        if (!preg_match('#/emtrpx/[^\"]+#ims', $body, $matches)) {
            $I->fail("failed to extract tracking pixel from body");
        }

        return $matches[0];
    }
}
