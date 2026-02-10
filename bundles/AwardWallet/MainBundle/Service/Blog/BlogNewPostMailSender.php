<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog\NewPost;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BlogNewPostMailSender implements ExecutorInterface
{
    private Mailer $mailer;

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private BlogNotification $blogNotification;

    private BlogUser $blogUser;

    public function __construct(
        Mailer $mailer,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        BlogNotification $blogNotification,
        BlogUser $blogUser
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->em = $em;
        $this->blogNotification = $blogNotification;
        $this->blogUser = $blogUser;
    }

    public function execute(Task $task, $delay = null)
    {
        $stat = ['registered' => 0, 'anonymous' => 0];
        $emails = $task->parameters['emails'];
        $this->logger->info("sending emails about new blog post to registered users", ["email_count" => count($emails)]);
        $users = $this->em->createQuery("select u from AwardWallet\MainBundle\Entity\Usr u where u.emailNewBlogPosts = 1")->iterate();
        $template = new NewPost();
        $template->blogpost = $task->parameters['blogpost'];

        $count = 0;

        for ($i = -1, $iCount = count($emails); ++$i < $iCount;) {
            $emails[$i] = strtolower($emails[$i]);
        }

        foreach ($users as $user) {
            $user = $user[0];
            /** @var Usr $user */
            $template->toUser($user);
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send([$message]);

            if (false !== ($pos = array_search(strtolower($user->getEmail()), $emails))) {
                unset($emails[$pos]);
            }

            if ((++$count % 100) == 0) {
                $this->em->clear();
            }

            ++$stat['registered'];
        }
        $this->logger->info("sent email messages to registered users", ["count" => $count]);

        $template->awUser = null;
        $usersRepo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $emails = array_values($emails);

        for ($i = -1, $iCount = count($emails); ++$i < $iCount;) {
            $user = $usersRepo->findOneBy(['email' => $emails[$i]]);

            if (!empty($user)) {
                continue;
            } // ignore registered users, they will be processed above
            $template->unsubscribeUrl = $this->blogUser->getUserUnsubscribeUrl($emails[$i]);
            $user = new Usr();
            $user->setLogin($emails[$i]);
            $user->setCreationdatetime(new \DateTime());
            $template->toUser($user, false, $emails[$i]);
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send([$message]);

            ++$stat['anonymous'];
        }
        $this->logger->info("sent email messages about new blog post to anonymous users", ["count" => count($emails)]);

        $this->blogNotification->notifyAboutNewBlogPost(BlogNotification::TYPE_EMAIL, $task->parameters['blogpost'], $stat);

        return new Response();
    }
}
