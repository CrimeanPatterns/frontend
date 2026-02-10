<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Test\Test;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\UrlSigner;
use Codeception\Util\Stub;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-functional
 */
class EmailClickControllerCest
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

        // redirect to awardwallet.com, relative, with fragment
        $statLogged = false;
        $expectedUrl = "/faqs#10";
        $I->amOnPage("/t/$trackingId/a/faqs?fragment=10");
        $I->seeRedirectTo($expectedUrl);
        $this->testEmailIsClicked($I, 1, $trackingId);
        $I->assertTrue($statLogged);

        // check blog links were redirected
        $body = $I->grabLastMailMessageBody();
        $I->assertRegExp("#/t/{$trackingId}/a/blog#ims", $body);

        // redirect to awardwallet.com, relative, querystring, no fragment
        $statLogged = false;
        $expectedUrl = "/faqs?x=1";
        $I->amOnPage("/t/$trackingId/a/faqs?x=1");
        $I->seeRedirectTo($expectedUrl);
        $this->testEmailIsClicked($I, 2, $trackingId);
        $I->assertTrue($statLogged);

        // redirect to awardwallet.com, relative, querystring and fragment
        $statLogged = false;
        $expectedUrl = "/faqs?x=1#abc";
        $I->amOnPage("/t/$trackingId/a/faqs?x=1&fragment=abc");
        $I->seeRedirectTo($expectedUrl);
        $this->testEmailIsClicked($I, 3, $trackingId);
        $I->assertTrue($statLogged);

        // redirect to external site, absolute
        $statLogged = false;
        $expectedUrl = "external.site/faqs?x=1#10";
        $sha = $I->getContainer()->get(UrlSigner::class)->getSign("external.site/faqs?x=1&fragment=10");
        $I->amOnPage("/t/$trackingId/e/$sha/external.site/faqs?x=1&fragment=10");
        $I->seeRedirectTo('https://' . $expectedUrl);
        $this->testEmailIsClicked($I, 4, $trackingId);
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

        $I->amOnPage("/t/$trackingId/a/faqs?x=1&fragment=abc");
        $this->testEmailIsClicked($I, 1, $trackingId);
    }

    private function extractTrackingId(\TestSymfonyGuy $I)
    {
        $body = $I->grabLastMailMessageBody();

        if (!preg_match('#/logo/\w+/\w+/(\w+)\.png#ims', $body, $matches)) {
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
