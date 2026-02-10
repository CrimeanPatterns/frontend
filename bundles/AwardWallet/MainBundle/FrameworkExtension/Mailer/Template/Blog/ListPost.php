<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ListPost extends AbstractTemplate
{
    public string $period;
    public array $blogpost;
    public ?string $customMessage;

    public static function getDescription(): string
    {
        return 'Blog List Post';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static();
        $template->toUser(Tools::createUser());
        $template->period = EmailNotificationNewPost::PERIOD_DAY;
        $template->customMessage = 'message';

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

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
