<?php

namespace AwardWallet\MainBundle\Command\Timeline;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveDetachedFiles extends Command
{
    public const FILE_LIFETIME_HOURS = 3;
    protected static $defaultName = 'aw:service:remove-detached-files';

    private EntityManagerInterface $entityManager;
    private ItineraryFileManager $itineraryFileManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ItineraryFileManager $itineraryFileManager
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->itineraryFileManager = $itineraryFileManager;
    }

    public function configure(): void
    {
        $this->setDescription('Removing unrelated file entries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->removeNonAttachedFiles($output);
        $list = $this->itineraryFileManager->removeAllDetachedFiles();

        $output->writeln(count($list) . ' all done.');

        return 0;
    }

    private function removeNonAttachedFiles(OutputInterface $output)
    {
        $files = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(ItineraryFile::class, 'f')
            ->where('f.itineraryId IS NULL')
            ->andWhere("f.uploadDate < DATE_SUB(CURRENT_TIMESTAMP(), :hour, 'HOUR')")
            ->setParameter(':hour', self::FILE_LIFETIME_HOURS)
            ->getQuery()
            ->getResult();

        /** @var ItineraryFile $file */
        foreach ($files as $file) {
            $this->itineraryFileManager->removeFile($file, false);
            $output->writeln($file->getFileName() . ' removed');
        }
        $this->entityManager->flush();

        $output->writeln(count($files) . ' new itinerary files removed.');
    }
}
