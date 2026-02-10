<?php

namespace AwardWallet\Tests\Unit\Blog;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Blog\BlogNewPostPushSender;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class BlogNewPostPushSenderCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $mocks = [];

    private $blogpost = [
        'id' => 123,
        'title' => 'test blogpost title',
        'url' => 'https://awardwallet.com/blog/test-url',
        'image' => 'https://awardwallet.com/blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
        'announce' => 'test blogpost announce',
        'author' => 'authorName',
    ];

    private $userData;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->blogpost['date'] = new \DateTime();
        $this->userData = [
            'Login' => $login = 'test-user-' . substr(bin2hex(openssl_random_pseudo_bytes(7)), 0, 13),
            'Pass' => '$2y$04$8D8o2s3q7bkSRltaEU89fO9S.D/APIQaF2H7HDAvamzkwyPAbfazO', // awdeveloper
            'FirstName' => 'Ragnar',
            'LastName' => 'Petrovich',
            'Email' => $login . '@fakemail.com',
            'City' => 'Las Vegas',
            'CreationDateTime' => $now = (new \DateTime())->format('Y-m-d H:i:s'),
            'EmailVerified' => EMAIL_VERIFIED,
            'CountryID' => 230, // USA
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
            'RefCode' => \AwardWallet\MainBundle\Globals\StringUtils::getPseudoRandomString(5),
            'WpDisableAll' => 0,
            'MpDisableAll' => 0,
            'WpNewBlogPosts' => 1,
            'MpNewBlogPosts' => 1,
        ];
        $this->userData['UserID'] = (int) $I->haveInDatabase('Usr', $this->userData);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        foreach ($this->mocks as $mock) {
            $mock->__phpunit_getInvocationHandler()->verify();
        }
    }

    public function testSendToUsers(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostPushSender($this->createSenderMock($I, 2, 0), new NullLogger(), $this->createEmMock($I, 1, $this->userData), $I->grabService('translator'), $I->getContainer()->get('AwardWallet\MainBundle\Service\Blog\BlogNotification'));
        $sender->execute(new Task("dummy", "dummy", null, ['blogpost' => $this->blogpost]));
    }

    public function testSendToNoOne(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostPushSender($this->createSenderMock($I, 0, 0), new NullLogger(), $this->createEmMock($I, 1, $this->userData), $I->grabService('translator'), $I->getContainer()->get('AwardWallet\MainBundle\Service\Blog\BlogNotification'));
        $sender->execute(new Task("dummy", "dummy", null, ['blogpost' => $this->blogpost]));
    }

    public function testSendToAnonymous(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostPushSender($this->createSenderMock($I, 0, 2), new NullLogger(), $this->createEmMock($I, 1, $this->userData), $I->grabService('translator'), $I->getContainer()->get('AwardWallet\MainBundle\Service\Blog\BlogNotification'));
        $sender->execute(new Task("dummy", "dummy", null, ['blogpost' => $this->blogpost]));
    }

    public function testSendToUsersAndAnonymous(\TestSymfonyGuy $I)
    {
        $sender = new BlogNewPostPushSender($this->createSenderMock($I, 2, 2), new NullLogger(), $this->createEmMock($I, 1, $this->userData), $I->grabService('translator'), $I->getContainer()->get('AwardWallet\MainBundle\Service\Blog\BlogNotification'));
        $sender->execute(new Task("dummy", "dummy", null, ['blogpost' => $this->blogpost]));
    }

    /**
     * @return Sender
     */
    private function createSenderMock(\TestSymfonyGuy $I, $userDeviceCount, $anonymousDeviceCount)
    {
        /** @var Sender $result */
        $result = $I->stubMakeEmpty(Sender::class, [
            "getUserDevicesQuery" => Stub::exactly(1, function () use ($userDeviceCount) {
                return new class($userDeviceCount) {
                    private $count;

                    public function __construct($count)
                    {
                        $this->count = $count;
                    }

                    public function iterate()
                    {
                        $result = [];

                        for ($n = 0; $n < $this->count; $n++) {
                            $usr = new Usr();
                            $usr->setEmail('user' . StringUtils::getRandomCode(8) . '@fakemail.com');
                            $device = new MobileDevice();
                            $device->setUser($usr);
                            $result[] = [$device];
                        }

                        return $result;
                    }
                };
            }),

            "getAnonymousDevicesQuery" => Stub::exactly(1, function () use ($anonymousDeviceCount) {
                return new class($anonymousDeviceCount) {
                    public function __construct($count)
                    {
                        $this->count = $count;
                    }

                    public function iterate()
                    {
                        $result = [];

                        for ($n = 0; $n < $this->count; $n++) {
                            $device = new MobileDevice();
                            $result[] = [$device];
                        }

                        return $result;
                    }
                };
            }),

            'send' => Stub::exactly($userDeviceCount + $anonymousDeviceCount, function (Content $content, array $devices): bool {
                return true;
            }),
        ]);
        $this->mocks[] = $result;

        return $result;
    }

    /**
     * @return EntityManagerInterface
     */
    private function createEmMock(\TestSymfonyGuy $I, $count, $userData)
    {
        /** @var EntityManagerInterface $result */
        $result = $I->stubMakeEmpty(EntityManagerInterface::class, [
            /*'getConnection' => Stub::once(function () use ($count, $userData) {
                return new Class($count, $userData)
                {
                    private $userData = [];

                    public function __construct($count, $userData)
                    {
                        $this->count = $count;
                        $this->userData = $userData;
                    }

                    public function fetchAll($sql, $params = [], $types = [])
                    {
                        return [['UserID' => $this->userData['UserID']]];
                    }
                };
            }),*/
        ]);
        $this->mocks[] = $result;

        return $result;
    }
}
