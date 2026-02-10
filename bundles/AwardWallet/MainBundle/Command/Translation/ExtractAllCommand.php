<?php

namespace AwardWallet\MainBundle\Command\Translation;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ExtractAllCommand extends Command
{
    protected static $defaultName = 'translation:extract:all';

    private string $rootDir;

    public function __construct(
        $rootDir,
        $dumpLocalesParameters
    ) {
        parent::__construct();
        $this->rootDir = $rootDir;
        $this->dumpLocalesParameters = $dumpLocalesParameters;
    }

    public function configure()
    {
        $this->setDescription("Generate all supported site translation.");
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = $this->rootDir;
        $extractDirs = [
            'bundles/AwardWallet/MainBundle',
            'bundles/AwardWallet/MobileBundle/Form/Type',
            'bundles/AwardWallet/MobileBundle/Form/Type/Profile',
            'bundles/AwardWallet/MobileBundle/Form/Type/Helpers',
            'bundles/AwardWallet/MobileBundle/Controller',
            'bundles/AwardWallet/MobileBundle/Form/View',
            'bundles/AwardWallet/MobileBundle/View',
            'bundles/AwardWallet/MobileBundle/Resources',
            'bundles/AwardWallet/WidgetBundle',
            'web/assets/awardwalletmain/js',
            'web/assets/awardwalletnewdesign/js',
            'web/assets/awardwalletnewmobile/js',
            'web/assets/common/js',
            'mobile/scripts',
            'mobile/templates',
            'assets/react-app',
        ];

        foreach ($this->dumpLocalesParameters as $item) {
            $output->writeln("Extract translation: <comment>{$item}</comment>");
            $command =
                "php {$rootDir}/console translation:extract {$item}" .
                " -d " . implode(" -d ", $extractDirs) . " --config=app" .
                ($input->getOption('verbose') ? ' --verbose' : '');

            $output->writeln('<question>' . $command . '</question>');
            $process = new Process($command);
            $process->setTimeout(1800);
            $errorBufferOut = false;
            $process->run(function ($type, $buffer) use ($output, &$errorBufferOut) {
                if (Process::ERR === $type) {
                    $output->writeln('<error>' . $buffer . '</error>');
                    $errorBufferOut = true;
                } else {
                    $output->writeln('<info>' . $buffer . '</info>');
                }
            });

            if ($errorBufferOut) {
                exit;
            }

            $output->writeln("[<fg=green>OK</fg=green>]");
        }
        $output->writeln("Execute bazinga dumper...");
        $command = "php {$rootDir}/console bazinga:js-translation:dump --merge-domains --format=js {$rootDir}/../web/assets";
        $output->writeln('<question>' . $command . '</question>');
        $process = new Process($command);
        $process->setTimeout(600);
        $errorBufferOut = false;
        $process->run(function ($type, $buffer) use ($output, &$errorBufferOut) {
            if (Process::ERR === $type) {
                $output->writeln('<error>' . $buffer . '</error>');
                $errorBufferOut = true;
            } else {
                $output->writeln('<info>' . $buffer . '</info>');
            }
        });

        if ($errorBufferOut) {
            exit;
        }
        $output->writeln("[<fg=green>OK</fg=green>]");
        $output->writeln("All done!");

        return 0;
    }
}
