<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCacheCommand extends Command
{
    protected static $defaultName = 'aw:initialize-cache';

    private CacheManager $cacheManager;

    public function __construct(
        CacheManager $cacheManager
    ) {
        parent::__construct();
        $this->cacheManager = $cacheManager;
    }

    public function configure()
    {
        $this
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, "don't do anything real, logging only")
            ->setDescription('Initialize cache to resolve some cold start issues. Run this command after composer invocation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $refl = new \ReflectionClass(Tags::class);

        $constants = $refl->getConstants();
        // filter only TAG_* constants
        $tags = array_values(array_intersect_key(
            $constants,
            array_flip(array_filter(
                array_keys($constants),
                function ($key) { return strpos($key, 'TAG_') === 0; }
            ))
        ));

        $output->writeln(sprintf('Initializing %d tag(s): %s', count($tags), json_encode($tags)));

        if (!$input->getOption('dry-run')) {
            $newTags = array_map('intval', $this->cacheManager->invalidateTags($tags, true, true, Tags::GLOBAL_TAGS_EXPIRATION));
        } else {
            $newTags = [];
        }

        $output->writeln(sprintf('Initialized %d tag(s): %s', count($newTags), json_encode($newTags)));

        if (
            !$input->getOption('dry-run')
            && count($newTags) !== count($tags)
        ) {
            throw new \RuntimeException(sprintf('These tags were failing to initialize: %s', json_encode(array_diff($tags, array_keys($newTags)))));
        }

        return 0;
    }
}
