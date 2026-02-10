<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\Blog\BlogCommentNotification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlogCommentNotificationCommand extends Command
{
    protected static $defaultName = 'aw:blog:email:comment-notification';
    private BlogCommentNotification $blogCommentNotification;

    public function __construct(BlogCommentNotification $blogCommentNotification)
    {
        parent::__construct();
        $this->blogCommentNotification = $blogCommentNotification;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sending notifications for new comments on the blog')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->blogCommentNotification->send();

        return 0;
    }
}
