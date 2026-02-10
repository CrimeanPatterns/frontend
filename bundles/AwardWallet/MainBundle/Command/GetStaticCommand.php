<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetStaticCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('aw:static:get')
            ->setDescription('get static archive for designer/html editor')
            ->setDefinition([
                new InputOption('dir', null, InputOption::VALUE_REQUIRED, 'output directory'),
                new InputOption('url', null, InputOption::VALUE_OPTIONAL, 'source url', 'http://awardwallet.local/newDesign/leftColumnTest'),
                new InputOption('keep', null, InputOption::VALUE_NONE, 'keep previous files in target dir, do not delete'),
                new InputOption('new-mobile', null, InputOption::VALUE_NONE, 'for new mobile'),
                new InputOption('no-zip', null, InputOption::VALUE_NONE, 'do not create zip'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exportDir = $input->getOption('dir');

        if (!(file_exists($exportDir) && is_dir($exportDir))) {
            mkdir($exportDir, 0777, false);
        }
        $exportDir = realpath($exportDir);

        if (!(file_exists($exportDir) && is_dir($exportDir))) {
            throw new \Exception("can not create export dir");
        }

        if (!$input->getOption('keep')) {
            $output->write('cleaning dir ');

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($exportDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                /** @var \DirectoryIterator $path */
                if ($path->isFile()) {
                    $output->write('.');
                    unlink($path->getPathname());
                } else {
                    $output->write(':');
                    rmdir($path->getPathname());
                }
            }
            $output->writeln(' done');
        }

        $url = parse_url($input->getOption('url'));

        if ($url === false || !isset($url['host'])) {
            throw new \Exception("malformed url");
        }
        $cookieFile = tempnam('/tmp', 'cookie');
        $cookies = str_replace('awardwallet.local', $url['host'], file_get_contents(__DIR__ . "/../Resources/cookies/SiteAdmin.txt"));
        $cookies = str_replace('[time]', microtime(true) + SECONDS_PER_DAY, $cookies);
        file_put_contents($cookieFile, $cookies);

        $output->writeln("running wget");
        $command = [
            'wget',
            '--no-verbose',
            '--no-host-directories',
            '--reject=php,html,txt',
            '--page-requisites',
            '--convert-links',
            '--backup-converted',
            '--restrict-file-names=windows',
            '--load-cookies ' . $cookieFile,
            '--force-directories',
            '--directory-prefix=' . $exportDir,
            $input->getOption('url'),
        ];
        $command = implode(' ', $command);
        $output->writeln($command);
        passthru($command, $exitCode);

        if ($exitCode != 0) {
            throw new \Exception("wget returned $exitCode");
        }

        $output->writeln("copying less");
        $copyTo = $exportDir . '/design/bundles/awardwalletmain/css/';

        if (!file_exists($copyTo)) {
            mkdir($copyTo, 0777, true);
        }
        passthru('cp ' . __DIR__ . '/../../../../web/design/bundles/awardwalletmain/css/*.less ' . $copyTo, $exitCode);

        if ($exitCode != 0) {
            throw new \Exception("cp returned $exitCode");
        }

        if ($input->getOption('new-mobile')) {
            $copyTo = $exportDir . '/design/bundles/awardwalletmobile/new/css/';

            if (!file_exists($copyTo)) {
                mkdir($copyTo, 0777, true);
            }
            passthru('cp ' . __DIR__ . '/../../../../web/design/bundles/awardwalletmobile/new/css/*.less ' . $copyTo, $exitCode);

            if ($exitCode != 0) {
                throw new \Exception("cp returned $exitCode");
            }

            $copyTo = $exportDir . '/design/bundles/awardwalletmobile/new/img/';

            if (!file_exists($copyTo)) {
                mkdir($copyTo, 0777, true);
            }
            passthru('cp -r ' . __DIR__ . '/../../../../web/design/bundles/awardwalletmobile/new/img/* ' . $copyTo, $exitCode);

            if ($exitCode != 0) {
                throw new \Exception("cp returned $exitCode");
            }
        }

        if (!$input->getOption('no-zip')) {
            if (file_exists($exportDir . '/static.zip')) {
                unlink($exportDir . '/static.zip');
            }
            $output->writeln("creating zip");

            if (file_exists($exportDir . '/static.zip')) {
                unlink($exportDir . '/static.zip');
            }
            chdir($exportDir);
            passthru('zip -r static.zip *', $exitCode);

            if ($exitCode != 0) {
                throw new \Exception("zip returned $exitCode");
            }
        }
        $output->writeln("done");

        return 0;
    }
}
