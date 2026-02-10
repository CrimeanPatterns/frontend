<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Service\Blog\BlogPostMock;
use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class EmailNewBlogPostsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    private string $loginImmediateUser;
    private string $loginDayUser;
    private string $loginWeekUser;
    private string $loginNeverUser;

    private int $idImmediateUser;
    private int $idDayUser;
    private int $idWeekUser;
    private int $idNeverUser;

    private string $emailDomain = '@awardwallet.com';

    public function _before(\TestSymfonyGuy $I)
    {
        $this->loginImmediateUser = 'immediateUser_' . $I->grabRandomString(8);
        $this->loginDayUser = 'dayUser_' . $I->grabRandomString(8);
        $this->loginWeekUser = 'weekUser_' . $I->grabRandomString(8);
        $this->loginNeverUser = 'neverUser_' . $I->grabRandomString(8);

        $this->idImmediateUser = $I->createAwUser(
            $this->loginImmediateUser,
            null,
            [
                'FirstName' => $this->loginImmediateUser,
                'Email' => $this->loginImmediateUser . $this->emailDomain,
                'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_IMMEDIATE,
            ]);
        $this->idDayUser = $I->createAwUser(
            $this->loginDayUser,
            null,
            [
                'FirstName' => $this->loginDayUser,
                'Email' => $this->loginDayUser . $this->emailDomain,
                'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_DAY,
            ]);
        $this->idWeekUser = $I->createAwUser(
            $this->loginWeekUser,
            null,
            [
                'FirstName' => $this->loginWeekUser,
                'Email' => $this->loginWeekUser . $this->emailDomain,
                'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK,
            ]);
        $this->idNeverUser = $I->createAwUser(
            $this->loginNeverUser,
            null,
            [
                'FirstName' => $this->loginNeverUser,
                'Email' => $this->loginNeverUser . $this->emailDomain,
                'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_NEVER,
            ]);

        $blogPosts = $I->stubMake(BlogPostMock::class, [
            'fetchPostByOptions' => Stub::atLeastOnce(static function (array $options) {
                return [
                    new PostItem(
                        1,
                        'blog-post-title-by-options',
                        'post-description',
                        '',
                        new \DateTime('-1 day'),
                        'https://awardwallet.com/blog/post-slug',
                        0,
                        'authorName',
                        'https://awardwallet.com/blog/authorLink',
                    ),
                ];
            }),
        ]);

        $I->mockService(BlogPostMock::class, $blogPosts);
    }

    public function testDay(\TestSymfonyGuy $I): void
    {
        $emailNotificationNewPost = $I->grabService(EmailNotificationNewPost::class);
        $emailNotificationNewPost->execute(EmailNotificationNewPost::PERIOD_DAY, new \DateTimeImmutable());

        $I->seeEmailTo($this->loginDayUser . $this->emailDomain);
        $I->assertStringContainsString($this->loginDayUser, $I->grabLastMail()->getBody());

        $I->seeInDatabase('EmailLog', ['UserID' => $this->idDayUser]);

        $I->dontSeeEmailTo($this->loginImmediateUser . $this->emailDomain);
        $I->dontSeeEmailTo($this->loginNeverUser . $this->emailDomain);
        $I->dontSeeEmailTo($this->loginWeekUser . $this->emailDomain);

        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idImmediateUser]);
        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idNeverUser]);
        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idWeekUser]);
    }

    public function testWeek(\TestSymfonyGuy $I): void
    {
        $emailNotificationNewPost = $I->grabService(EmailNotificationNewPost::class);
        $emailNotificationNewPost->execute(
            EmailNotificationNewPost::PERIOD_WEEK,
            new \DateTimeImmutable(),
            [
                'isDryRun' => false,
                'userId' => implode(',', [$this->idImmediateUser, $this->idDayUser, $this->idWeekUser, $this->idNeverUser]),
            ]
        );

        $I->seeEmailTo($this->loginWeekUser . $this->emailDomain);
        $I->assertStringContainsString($this->loginWeekUser, $I->grabLastMail()->getBody());

        $I->seeInDatabase('EmailLog', ['UserID' => $this->idWeekUser]);

        $I->dontSeeEmailTo($this->loginImmediateUser . $this->emailDomain);
        $I->dontSeeEmailTo($this->loginDayUser . $this->emailDomain);
        $I->dontSeeEmailTo($this->loginNeverUser . $this->emailDomain);

        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idImmediateUser]);
        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idNeverUser]);
        $I->dontSeeInDatabase('EmailLog', ['UserID' => $this->idDayUser]);
    }

    public function testDuplicate(\TestSymfonyGuy $I)
    {
        $this->testDay($I);
        $messageLast = $I->grabLastMail();

        $this->testDay($I);
        $I->assertSame($messageLast->getId(), $I->grabLastMail()->getId());
    }
}
