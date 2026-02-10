<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\PageVisit;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeletePageVisitsCommand extends Command
{
    public static $defaultName = 'aw:page-visit:clear';
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Delete page visits older than 6 months');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(PageVisit::class, 'p');

        $date = (new \DateTime())->sub(new \DateInterval('P6M'));
        $result = $queryBuilder->delete()
            ->where('p.day < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();

        $this->logger->info('Page visits, number of deleted rows: ' . $result . ', where date < ' . $date->format('Y-m-d'));

        return 0;
    }
}
