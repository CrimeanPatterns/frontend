<?php

namespace AwardWallet\Tests\Unit\Blog;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\Blog\BlogNotification;
use AwardWallet\Tests\Unit\BaseTest;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class BlogNotificationsTest extends BaseTest
{
    public const URL_DOMAIN = 'https://awardwallet.com/';

    private $mocks = [];

    private $blogpost = [
        'id' => 123,
        'url' => self::URL_DOMAIN . 'blog/test-url',
        'title' => 'test blogpost title',
        'image' => self::URL_DOMAIN . 'blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
        'announce' => 'test blogpost announce',
        'author' => 'author name',
    ];

    public function _after()
    {
        foreach ($this->mocks as $mock) {
            $mock->__phpunit_getInvocationHandler()->verify();
        }
    }

    public function testNotifyAboutNewBlogPost()
    {
        $memcache = $this->getModule('Symfony')->_getContainer()->get(\Memcached::class);
        $cache = $appBot = $this->make(\Memcached::class, [
            'get' => Stub::exactly(2, function ($key) use ($memcache) {
                return $memcache->get($key);
            }),
            'set' => Stub::once(function ($key, $state, $expiration) use ($memcache) {
                return $memcache->set($key, $state, $expiration);
            }),
            'delete' => Stub::once(function () {
            }),
        ]);

        $appBot = $this->makeEmpty(AppBot::class, [
            'send' => Stub::once(function (string $channel, string $message) {
                foreach (['url', 'title', 'author'] as $key) {
                    $this->assertStringContainsString($this->blogpost[$key], $message);
                }
            }),
        ]);

        $this->mocks[] = $cache;
        $this->mocks[] = $appBot;
        $blogNotification = new BlogNotification(
            new NullLogger(),
            $cache,
            $appBot,
            $this->getModule('Symfony')->_getContainer()->get(LocalizeService::class),
            $this->getModule('Symfony')->_getContainer()->get(EntityManagerInterface::class),
        );

        $stat = ['registered' => 1, 'anonymous' => 1];

        $check = $blogNotification->notifyAboutNewBlogPost(BlogNotification::TYPE_EMAIL, $this->blogpost, $stat);
        $this->assertFalse($check);

        $check = $blogNotification->notifyAboutNewBlogPost(BlogNotification::TYPE_PUSH, $this->blogpost, [
            'mobile' => $stat,
            'desktop' => $stat,
        ]);
        $this->assertTrue($check);
    }
}
