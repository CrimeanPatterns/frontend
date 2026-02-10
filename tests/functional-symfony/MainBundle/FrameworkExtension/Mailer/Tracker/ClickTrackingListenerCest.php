<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\FrameworkExtension\Mailer\Tracker;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use Codeception\Util\Stub;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-functional
 */
class ClickTrackingListenerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testClick(\TestSymfonyGuy $I)
    {
        $I->mockService("monolog.logger.email", $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Stub::atLeastOnce(function ($message, array $context = []) use (&$statLogged, $I, &$expectedUrl) {
                if ($message === "email clicked") {
                    $I->assertArrayHasKey("id", $context);
                    $I->assertEquals(32, strlen($context['id']));
                    unset($context["id"]);
                    $I->assertTrue($context['database_record_exists']);
                    $I->assertEquals('welcome_to_aw', $context['kind']);
                    $I->assertEquals($expectedUrl, $context['url']);
                    $I->assertEquals('Symfony BrowserKit', $context['user_agent']);
                    $statLogged = true;
                }
            }),
        ]));

        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $I->followRedirects(false);

        /** @var Mailer $mailer */
        $mailer = $I->grabService(Mailer::class);
        $template = new WelcomeToAw($user);
        $message = $mailer->getMessageByTemplate($template);
        $I->assertTrue($mailer->send($message));
        $trackingId = $this->extractTrackingId($I);

        $statLogged = false;
        $expectedUrl = "/faqs?emtr={$trackingId}";
        $I->amOnPage("/faqs?emtr={$trackingId}#10");
        $I->seeResponseCodeIs(200);
        $this->testEmailIsClicked($I, 1, $trackingId);
        $I->assertTrue($statLogged);
    }

    private function extractTrackingId(\TestSymfonyGuy $I)
    {
        $body = $I->grabLastMailMessageBody();

        if (!preg_match('#emtr=([a-z\d]{32})#ims', $body, $matches)) {
            $I->fail("failed to extract trackingId from body");
        }

        return $matches[1];
    }

    private function testEmailIsClicked(\TestSymfonyGuy $I, int $expectedClickCount, string $trackingId)
    {
        $row = $I->query("select * from SentEmail where ID = ?", [hex2bin($trackingId)])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals($expectedClickCount, $row["ClickCount"]);
        $I->assertNotNull($row["FirstClickDate"]);
        $I->assertNotNull($row["LastClickDate"]);
    }
}
