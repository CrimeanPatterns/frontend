<?php

namespace AwardWallet\MainBundle\Command\Blog;

use AwardWallet\MainBundle\Service\Blog\BlogLinkClickSync;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlogLinkClickSyncCommand extends Command
{
    protected static $defaultName = 'aw:blog:sync-link-click';

    private BlogLinkClickSync $blogLinkClickSync;

    public function __construct(BlogLinkClickSync $blogLinkClickSync)
    {
        parent::__construct();

        $this->blogLinkClickSync = $blogLinkClickSync;
    }

    protected function configure(): void
    {
        $this->setDescription('Syncing the link_click table from a blog to the frontend');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->blogLinkClickSync->sync();

        return 0;
    }
}
