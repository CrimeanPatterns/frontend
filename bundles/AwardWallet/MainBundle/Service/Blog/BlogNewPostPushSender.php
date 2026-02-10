<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlogNewPostPushSender implements ExecutorInterface
{
    private Sender $sender;

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private TranslatorInterface $translator;

    private BlogNotification $blogNotification;

    public function __construct(
        Sender $sender,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        BlogNotification $blogNotification
    ) {
        $this->sender = $sender;
        $this->logger = $logger;
        $this->em = $em;
        $this->translator = $translator;
        $this->blogNotification = $blogNotification;
    }

    public function execute(Task $task, $delay = null)
    {
        $blogpost = $task->parameters['blogpost'];

        $stat = [
            'mobile' => ['registered' => 0, 'anonymous' => 0],
            'desktop' => ['registered' => 0, 'anonymous' => 0],
        ];
        $this->logger->info('BlogNewPostPushSender sending pushes about new blog post');

        $count = 0;

        foreach ($this->sender->getUserDevicesQuery([], MobileDevice::TYPES_ALL, Content::TYPE_BLOG_POST, true)->iterate() as $device) {
            $parseUrl = parse_url($blogpost['url']);
            /** @var Usr $user */
            $user = $device[0]->getUser();
            $refCode = $user ? $user->getRefcode() : '';

            if ($device[0]->isDesktop()) {
                $query = 'utm_source=aw&utm_medium=push_web&utm_campaign=new_post_notify&awid=aw&mid=push_web&cid=new_post_notify&rkbtyn=' . $refCode;
            } else {
                $query = 'utm_source=aw&utm_medium=push_mobile&utm_campaign=new_post_notify&awid=aw&mid=push_mobile&cid=new_post_notify&rkbtyn=' . $refCode;
            }
            $content = new Content($this->translator->trans('blog.new-post.published', ['%link_on%' => '', '%link_off%' => ''], 'email', $user->getLocale()), $blogpost['title'], Content::TYPE_BLOG_POST, $parseUrl['path'] . "?$query");
            $content->options = new Options();
            $content->options->setAutoClose(false);
            $content->options->setInterruptionLevel(InterruptionLevel::ACTIVE);
            // $content->options->addFlag(Options::FLAG_DRY_RUN);
            $this->logger->warning('BlogNewPostPushSender registered debug', [
                'login' => $device[0]->getUser()->getLogin(),
                'locale' => $user->getLocale(),
                'lang' => $user->getLanguage(),
                'transLocale' => $this->translator->getLocale(),
            ]);
            $this->sender->send($content, $device);

            if ((++$count % 100) == 0) {
                $this->em->clear();
            }

            $device[0]->isDesktop()
                ? ++$stat['desktop']['registered']
                : ++$stat['mobile']['registered'];
        }
        $this->logger->info('BlogNewPostPushSender sent push messages to registered users', ["count" => $count]);

        $this->logger->info('sending push messages to anonymous users');

        foreach ($this->sender->getAnonymousDevicesQuery(MobileDevice::TYPES_ALL, true)->iterate() as $device) {
            $parseUrl = parse_url($blogpost['url']);
            $content = new Content($this->translator->trans('blog.new-post.published', ['%link_on%' => '', '%link_off%' => ''], 'email', 'en_US'), $blogpost['title'], Content::TYPE_BLOG_POST, $parseUrl['path']);
            $content->options = new Options();
            $content->options->setAutoClose(false);
            $content->options->setInterruptionLevel(InterruptionLevel::ACTIVE);
            // $content->options->addFlag(Options::FLAG_DRY_RUN);
            $this->logger->warning('BlogNewPostPushSender anonymous debug');
            $this->sender->send($content, $device);

            if ((++$count % 100) == 0) {
                $this->em->clear();
            }

            $device[0]->isDesktop()
                ? ++$stat['desktop']['anonymous']
                : ++$stat['mobile']['anonymous'];
        }
        $this->logger->info('sent push messages to anonymous users', ['count' => $count]);

        $this->blogNotification->notifyAboutNewBlogPost(BlogNotification::TYPE_PUSH, $blogpost, $stat);

        return new Response();
    }
}
