<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;

/**
 * @group frontend-functional
 */
class BlogApiNotificationsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const URL = '/api/blog/update-notifications';

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

    public function testNoEmail(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $I->sendPOST(self::URL);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "not_found"]);
    }

    public function testBadEmail(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $I->sendPOST(self::URL, ["email" => "somebadaddress"]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "not_found"]);
    }

    public function testGoodEmail(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $userId = $I->createAwUser();
        $fields = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(0, $fields['WpNewBlogPosts']);
        $I->assertEquals(0, $fields['MpNewBlogPosts']);
        $I->assertEquals(NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK, $fields['EmailNewBlogPosts']);

        $I->sendPOST(self::URL, ["email" => $fields['Email']]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "success"]);

        $fields = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(1, $fields['WpNewBlogPosts']);
        $I->assertEquals(1, $fields['MpNewBlogPosts']);
        $I->assertEquals(1, $fields['EmailNewBlogPosts']);
    }

    public function testLoggedIn(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "basic " . base64_encode("awardwallet:" . $I->getContainer()->getParameter("blog.api.secret")));
        $userId = $I->createAwUser();
        $fields = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(0, $fields['WpNewBlogPosts']);
        $I->assertEquals(0, $fields['MpNewBlogPosts']);
        $I->assertEquals(NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK, $fields['EmailNewBlogPosts']);

        $I->sendGET("/test/client-info?_switch_user=" . $fields['Login']);
        $I->sendPOST(self::URL);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "success"]);

        $fields = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(1, $fields['WpNewBlogPosts']);
        $I->assertEquals(1, $fields['MpNewBlogPosts']);
        $I->assertEquals(1, $fields['EmailNewBlogPosts']);
    }
}
