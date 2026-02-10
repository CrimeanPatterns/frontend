<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ListPostWeek extends AbstractTemplate
{
    public string $period;
    public array $groups;
    public array $blogpost;
    public ?string $subject;
    public ?string $preview;
    public ?string $customMessage;
    public string $htmlGroups;

    public static function getDescription(): string
    {
        return 'Blog Weekly List Post';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static();
        $template->toUser(Tools::createUser());
        $template->period = EmailNotificationNewPost::PERIOD_WEEK;
        $template->htmlGroups = 'blogposts list';

        $template->subject = 'test-custom-subject';
        $template->preview = 'first text for preview';

        $template->blogpost = [
            new PostItem(
                1,
                'test blogpost title',
                'description',
                'https://awardwallet.com/blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
                new \DateTime(),
                'https://awardwallet.com/blog',
                0,
                'author-name',
                'https://awardwallet.com/blog/',
            ),
        ];

        $template->groups = [
            [
                'catId' => 12067,
                'name' => 'Travel Booking Tips',
                'slug' => 'booking-travel',
                'icon' => '/images/email/blog/category/travel-booking.png',
                'posts' => [
                    new PostItem(
                        1,
                        'blog-post-title-by-options',
                        'description',
                        'https://awardwallet.com/blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
                        new \DateTime(),
                        'https://awardwallet.com/blog',
                        0,
                        'author-name',
                        'author-link',
                        [
                            (object) [
                                'catId' => 12067,
                                'name' => 'Booking Travel',
                                'slug' => 'booking-travel',
                            ],
                        ],
                        [
                            'names' => '<a class="authors-name" href="http://blog.awardwallet.docker/blog/author/mark/" rel="noopener">Mark Jackson</a> and <a class="authors-name" style="color: #9ea0a6;text-decoration: underline;font-size: 13px;font-weight:700;" href="http://blog.awardwallet.docker/blog/author/ian-snyder/" rel="noopener">Ian Snyder</a>',
                            'count' => 2,
                            'list' => [
                                (object) [
                                    'name' => 'Mark Jackson',
                                    'link' => 'http://blog.awardwallet.docker/blog/author/mark/',
                                    'avatar' => 'http://blog.awardwallet.docker/blog/wp-content/uploads/2024/04/Mark-Jackson-profile-pic-150x150.jpg',
                                ],
                                (object) [
                                    'name' => 'Ian Snyder',
                                    'link' => 'http://blog.awardwallet.docker/blog/author/ian-snyder/',
                                    'avatar' => 'http://blog.awardwallet.docker/blog/wp-content/uploads/2023/12/IanSnyder-150x150.jpg',
                                ],
                            ],
                        ],
                    ),
                ],
            ],
        ];

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
