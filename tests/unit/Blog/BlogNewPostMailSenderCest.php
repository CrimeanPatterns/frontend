<?php

namespace AwardWallet\Tests\Unit\Blog;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog\NewPost;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostMailSender;
use AwardWallet\MainBundle\Service\Blog\BlogNotification;
use AwardWallet\MainBundle\Service\Blog\BlogUser;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class BlogNewPostMailSenderCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const URL_DOMAIN = 'https://awardwallet.com/';

    private $mocks = [];

    private $blogpost = [
        'id' => 123,
        'title' => 'test blogpost title',
        'url' => self::URL_DOMAIN . 'blog/test-url',
        'image' => self::URL_DOMAIN . 'blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
        'announce' => 'test blogpost announce',
        'author' => 'authorName',
    ];

    public function _before(\TestSymfonyGuy $I)
    {
        $this->blogpost['date'] = new \DateTime();

        $login = 'notifytest' . StringHandler::getRandomCode(12);
        $userId = $I->createAwUser($login, null, [
            'Email' => $login . '@fakemail.com',
            'EmailVerified' => EMAIL_VERIFIED,
            // 'CountryID' => 230,
            'WpDisableAll' => 0,
            'MpDisableAll' => 0,
            'WpNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_IMMEDIATE,
        ]);
        $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => 3]);
        $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => 37]);
        $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => 49]);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        foreach ($this->mocks as $mock) {
            $mock->__phpunit_getInvocationHandler()->verify();
        }
    }

    public function testSendToUsers(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostMailSender(
            $this->createMailerMock($I, 2),
            new NullLogger(),
            $this->createEmMock($I, 2),
            $I->getContainer()->get(BlogNotification::class),
            $I->getContainer()->get(BlogUser::class)
        );
        $sender->execute(new Task("dummy", "dummy", null, ['emails' => [], 'blogpost' => $this->blogpost]));
    }

    public function testSendToNoOne(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostMailSender(
            $this->createMailerMock($I, 0),
            new NullLogger(),
            $this->createEmMock($I, 0),
            $I->getContainer()->get(BlogNotification::class),
            $I->getContainer()->get(BlogUser::class)
        );
        $sender->execute(new Task("dummy", "dummy", null, ['emails' => [], 'blogpost' => $this->blogpost]));
    }

    public function testSendToAnonymous(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostMailSender(
            $this->createMailerMock($I, 3),
            new NullLogger(),
            $this->createEmMock($I, 0),
            $I->getContainer()->get(BlogNotification::class),
            $I->getContainer()->get(BlogUser::class)
        );
        $sender->execute(new Task("dummy", "yummy", null, ['emails' => ["user1@awardwallet.com", "user2@awardwallet.com", "user3@awardwallet.com"], 'blogpost' => $this->blogpost]));
    }

    public function testSendToUsersExcludingAnonymous(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostMailSender(
            $this->createMailerMock($I, 3),
            new NullLogger(),
            $this->createEmMock($I, 3),
            $I->getContainer()->get(BlogNotification::class),
            $I->getContainer()->get(BlogUser::class)
        );
        $sender->execute(new Task("dummy", "yummy", null, ['emails' => ["user2@awardwallet.com"], 'blogpost' => $this->blogpost]));
    }

    public function testSendToUsersAndAnonymous(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostMailSender(
            $this->createMailerMock($I, 4),
            new NullLogger(),
            $this->createEmMock($I, 3),
            $I->getContainer()->get(BlogNotification::class),
            $I->getContainer()->get(BlogUser::class)
        );
        $sender->execute(new Task("dummy", "yummy", null, ['emails' => ["anonymous@awardwallet.com"], 'blogpost' => $this->blogpost]));
    }

    /**
     * @return Mailer
     */
    private function createMailerMock(\TestSymfonyGuy $I, $count)
    {
        /** @var Mailer $result */
        $result = $I->stubMakeEmpty(Mailer::class, [
            "getMessageByTemplate" => Stub::exactly(
                $count,
                function (NewPost $template) {
                    return new Message();
                }),
            'send' => Stub::exactly(
                $count,
                function ($messages, $options = []) use ($I) {
                    $I->assertCount(1, $messages);
                    $I->assertInstanceOf(\Swift_Message::class, array_pop($messages));
                }),
        ]);
        $this->mocks[] = $result;

        return $result;
    }

    /**
     * @return EntityManagerInterface
     */
    private function createEmMock(\TestSymfonyGuy $I, $count)
    {
        /** @var EntityManagerInterface $result */
        $result = $I->stubMakeEmpty(EntityManagerInterface::class, [
            'createQuery' => Stub::once(
                function () use ($count) {
                    return new class($count) {
                        public function __construct($count)
                        {
                            $this->count = $count;
                        }

                        public function setParameter($key, $value)
                        {
                            return $this;
                        }

                        public function iterate()
                        {
                            $result = [];

                            for ($n = 0; $n < $this->count; $n++) {
                                $user = new Usr();
                                $user->setEmail("user{$n}@awardwallet.com");
                                $result[] = [$user];
                            }

                            return $result;
                        }
                    };
                }),

            'getRepository' => Stub::once(
                function () use ($I) {
                    return $I->stubMakeEmpty(UsrRepository::class, [
                        'findOneBy' => function ($arg) {
                            if (isset($arg['groupname'])) {
                                return new Sitegroup();
                            }

                            return null;
                        },
                    ]);
                }),
        ]);
        $this->mocks[] = $result;

        return $result;
    }
}
