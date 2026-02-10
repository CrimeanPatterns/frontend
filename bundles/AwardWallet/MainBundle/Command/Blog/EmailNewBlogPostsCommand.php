<?php

namespace AwardWallet\MainBundle\Command\Blog;

use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmailNewBlogPostsCommand extends Command
{
    protected static $defaultName = 'aw:email-blog-posts';
    private EmailNotificationNewPost $emailNotificationNewPost;

    public function __construct(EmailNotificationNewPost $emailNotificationNewPost)
    {
        parent::__construct();
        $this->emailNotificationNewPost = $emailNotificationNewPost;
    }

    public function configure()
    {
        parent::configure();
        $this->setDefinition([
            new InputOption('period', null, InputOption::VALUE_REQUIRED,
                '"day" or "week" for users who choose this option'
            ),
            new InputOption('end-date', null, InputOption::VALUE_OPTIONAL),
            new InputOption('time', null, InputOption::VALUE_OPTIONAL),
            new InputOption('userId', null, InputOption::VALUE_OPTIONAL),
            new InputOption('ignoreResend', null, InputOption::VALUE_NONE,
                'Ignore resend check'
            ),
            new InputOption('pack', null, InputOption::VALUE_REQUIRED),
            new InputOption('disable-notify', null, InputOption::VALUE_NONE),
            new InputOption('dry-run', null, InputOption::VALUE_NONE),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = $input->getOption('period');
        $date = $input->getOption('end-date', date('Y-m-d'));
        $isTime = $input->getOption('time') ?? false;
        $time = $isTime ? $input->getOption('time') : '00:00';

        $date = new \DateTimeImmutable($date . ' ' . $time);
        $endDate = $isTime
            ? $date->sub(new \DateInterval('P1D'))
            : $date->setTime(0, 0);

        $options = [];

        if (!empty($userId = $input->getOption('userId'))) {
            $options['userId'] = $userId;
        }

        if (!empty($pack = $input->getOption('pack'))) {
            $options['pack'] = $pack;
        }

        if ($input->getOption('ignoreResend')) {
            $options['ignoreResend'] = true;
        }

        if ($input->getOption('dry-run')) {
            $options['isDryRun'] = true;
        }

        if ($input->getOption('disable-notify')) {
            $options['isDisableNotify'] = true;
        }

        $this->emailNotificationNewPost->execute($period, $endDate, $options, $output);

        return 0;
    }
}
