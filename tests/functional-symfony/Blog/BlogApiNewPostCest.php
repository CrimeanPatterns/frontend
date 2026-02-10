<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostMailSender;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostPushSender;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class BlogApiNewPostCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const URL = '/api/blog/new-post';
    public const URL_DOMAIN = 'https://awardwallet.com/';

    public function testNoAuth(\TestSymfonyGuy $I)
    {
        $I->sendPOST(self::URL);
        $I->seeResponseCodeIs(403);
    }

    public function testBadAuth(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:somebadpass"));
        $I->sendPOST(self::URL);
        $I->seeResponseCodeIs(403);
    }

    public function testBadEmail(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $emails = ["some_bad_email."];
        $I->sendPOST(self::URL, [
            'emails' => $emails,
            'postName' => 'post-name',
            'postUrl' => 'post-url',
        ]);
        $I->seeResponseContainsJson(['invalid' => [$emails[0] . '@gmail.com']]);
    }

    public function testEmptyBlogpost(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $I->sendPOST(self::URL, []);
        $I->seeResponseCodeIs(500);
    }

    public function testSuccess(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $data = [
            'emails' => [StringUtils::getRandomCode(20) . "@test.com"],
            'postName' => 'test blogpost title',
            'postUrl' => self::URL_DOMAIN . 'blog/test-url',
            'postImgUrl' => self::URL_DOMAIN . 'blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
            'postExcerpt' => 'test blogpost announce',
        ];

        $call = 0;
        $mock = $I->stubMakeEmpty(Process::class, [
            'execute' => Stub::exactly(2, function (Task $task) use ($I, $data, &$call) {
                $validation = ['url' => 'postUrl', 'title' => 'postName', 'image' => 'postImgUrl', 'announce' => 'postExcerpt'];

                switch ($call) {
                    case 0:
                        $I->assertEquals(BlogNewPostMailSender::class, $task->serviceId);
                        $I->assertEquals(null, $task->method);
                        $I->assertEquals($data['emails'], $task->parameters['emails']);

                        foreach ($validation as $taskKey => $postKey) {
                            $I->assertEquals($data[$postKey], $task->parameters['blogpost'][$taskKey]);
                        }

                        break;

                    case 1:
                        $I->assertEquals(BlogNewPostPushSender::class, $task->serviceId);
                        $I->assertEquals(null, $task->method);

                        foreach ($validation as $taskKey => $postKey) {
                            $I->assertEquals($data[$postKey], $task->parameters['blogpost'][$taskKey]);
                        }

                        break;

                    default:
                        $I->fail("too many calls");
                }
                $call++;

                return new Response();
            }),
        ]);
        $I->mockService(Process::class, $mock);

        $I->sendPOST(self::URL, $data);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "success"]);
    }
}
