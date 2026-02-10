<?php

namespace AwardWallet\MainBundle\Command\Translation;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use JMS\TranslationBundle\Model\Message\XliffMessage;
use JMS\TranslationBundle\Translation\Dumper\XliffDumper;
use JMS\TranslationBundle\Translation\Loader\XliffLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FixEnglishTranslations extends Command
{
    protected static $defaultName = 'translation:fix-english-translations';

    private string $projectDir;

    public function __construct(
        $projectDir
    ) {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    public function configure()
    {
        $this->setDescription("update Desc with english values in source field, synchronize source and target field values in english locale");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new XliffLoader();
        $dumper = new XliffDumper();
        $dumper->setAddDate(false);

        $transDir = $this->projectDir . '/translations';

        $fs = (new Finder())->in($transDir)->name('/\.en\.xliff$/i')->files()->sortByName();

        /** @var SplFileInfo $file */
        foreach ($fs as $file) {
            $loaded = $loader->load($file->getPathname(), 'en');
            $catalogue = $loader->load($file->getPathname(), 'en');
            $collection = $catalogue->getDomain('messages');
            $hasFixed = false;
            $translations = $collection->all();

            /** @var XliffMessage $translation */
            foreach ($translations as $translation) {
                if ($translation->getDesc() !== $translation->getLocaleString()) {
                    $hasFixed = true;
                    $output->writeln(sprintf("Fixing '%s' key in %s.", $translation->getId(), $file->getFilename()));
                    $translation->setDesc($translation->getLocaleString());
                }
            }

            if ($hasFixed) {
                $output->writeln(sprintf("Dumping %s", $file->getFilename()));
                file_put_contents($file->getPathname(), $dumper->dump($catalogue, 'messages'));
            }
        }

        return 0;
    }
}
