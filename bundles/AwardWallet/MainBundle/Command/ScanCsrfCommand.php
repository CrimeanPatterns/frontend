<?php

namespace AwardWallet\MainBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCsrfCommand extends Command
{
    public const HOST = 'http://awardwallet.docker';
    protected static $defaultName = 'aw:scan-csrf';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var \HttpBrowser
     */
    protected $authBrowser;
    /**
     * @var \HttpBrowser
     */
    protected $anonBrowser;

    public function __construct(
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('scan url list to include CSRF')
            ->setDefinition([
                new InputOption('file', null, InputOption::VALUE_REQUIRED, 'source file with url list'),
                new InputOption('logdir', null, InputOption::VALUE_OPTIONAL, 'log directory'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getOption('file');
        $this->logger->info("scanning URLs from file $file");
        $urlList = explode("\n", file_get_contents($file));

        $this->anonBrowser = $this->createBrowser($input->getOption('logdir'), "anon");
        $this->anonBrowser->GetURL(self::HOST);
        $this->authBrowser = $this->createBrowser($input->getOption('logdir'), "auth");
        $this->authBrowser->GetURL(self::HOST . '/?_switch_user=SiteAdmin');

        foreach ($urlList as $url) {
            if (!empty($url)) {
                $this->scanUrl($url);
            }
        }

        return 0;
    }

    protected function createBrowser($logDir, $suffix)
    {
        $driver = new \CurlDriver();
        $logMode = "none";

        if (!empty($logDir)) {
            $logMode = "dir";
        }
        $result = new \HttpBrowser($logMode, $driver);

        if (!empty($logDir)) {
            $result->LogDir = $logDir . '/' . $suffix;

            if (!file_exists($result->LogDir)) {
                mkdir($result->LogDir, 0777, true);
            }
        }
        $result->RetryCount = 0;

        return $result;
    }

    protected function scanUrl($url)
    {
        $this->logger->info($url);
        $this->anonBrowser->GetURL(self::HOST . $url);
        $browser = $this->anonBrowser;

        if (stripos($this->anonBrowser->currentUrl(), 'unauthorized') !== false) {
            $this->logger->info("trying as authenticated user: " . $url);
            $this->authBrowser->GetURL(self::HOST . $url);
            $browser = $this->authBrowser;
        }

        if (empty($browser->FindSingleNode('//input[@name = "FormToken"]'))) {
            $this->logger->warning("missing csrf: " . $url);
        }
    }
}
