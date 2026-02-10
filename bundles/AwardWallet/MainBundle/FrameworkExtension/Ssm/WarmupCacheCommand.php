<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WarmupCacheCommand extends Command
{
    public static $defaultName = 'aw:ssm-warmup-cache';
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cache->warmup();
    }
}
