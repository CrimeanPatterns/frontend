<?php

namespace AwardWallet\MainBundle\Command\Translation;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Translation\Dumper\XliffDumper;
use JMS\TranslationBundle\Translation\Loader\XliffLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/*
 * run translation:fix-source-xliff                          // will replace in all non-english files sources based on english file
 * run translation:fix-source-xliff -s faq.en.xliff          // replacement in all faq files
 * run translation:fix-source-xliff -s faq.en.xliff -l de    // replacement only in faq german file
 */

class FixSourceXliff extends Command
{
    protected static $defaultName = 'translation:fix-source-xliff';

    private string $projectDir;

    public function __construct(
        $projectDir
    ) {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    public function configure()
    {
        $this
            ->setDescription('Updating the source tag in files based on the original file')
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'source')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceOpt = $input->getOption('source');
        $localeOpt = $input->getOption('locale');

        if (empty($sourceOpt)) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to process all xliff files? [y n]', false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $transDir = $this->projectDir . '/translations';

        $loader = new XliffLoader();
        $dumper = new XliffDumper();
        $dumper->setAddDate(false);

        $locales = [];

        if (empty($localeOpt)) {
            foreach (glob($transDir . '/messages.*.xliff') as $file) {
                $locales[] = explode('.', pathinfo($file, PATHINFO_BASENAME))[1];
            }
        } else {
            $locales = [$localeOpt];
        }

        $sourceLocale = 'en';
        $sourceXliff = (new Finder())->in($transDir)->name(empty($sourceOpt) ? '/\.' . $sourceLocale . '\.xliff$/i' : '/' . $sourceOpt . '$/i')->files();

        /** @var SplFileInfo $xliff */
        foreach ($sourceXliff as $xliff) {
            $sourceData = [];

            /** @var XliffMessage $translation */
            foreach ($loader->load($xliff->getPathname(), $sourceLocale)->getDomain('messages')->all() as $translation) {
                $sourceData[$translation->getId()] = $translation->getSourceString();
            }

            foreach ($locales as $locale) {
                if ($sourceLocale === $locale) {
                    continue;
                }

                $transFile = str_replace('.' . $sourceLocale . '.', '.' . $locale . '.', $xliff->getBasename());
                $transFilePath = $transDir . '/' . $transFile;

                if (file_exists($transFilePath)) {
                    $loaderDest = new XliffLoader();
                    $catalogue = $loaderDest->load($transFilePath, $locale);
                    $collection = $catalogue->getDomain('messages');
                    $needFix = false;

                    /** @var XliffMessage $translation */
                    foreach ($collection->all() as $translation) {
                        if (array_key_exists($translation->getId(), $sourceData) && $sourceData[$translation->getId()] !== $translation->getSourceString()) {
                            $translation->setDesc($sourceData[$translation->getId()]);
                            $output->writeln('fix - ' . $transFile . ': id=' . $translation->getId());
                            $needFix = true;
                        }
                    }

                    if ($needFix) {
                        file_put_contents($transFilePath, $dumper->dump($catalogue, 'messages'));
                        $output->writeln('Dumping file ' . $transFile . "\n");
                    }
                }
            }
        }

        return 0;
    }
}
