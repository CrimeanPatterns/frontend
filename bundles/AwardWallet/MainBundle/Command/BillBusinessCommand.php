<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BillBusinessCommand extends Command
{
    public static $defaultName = 'aw:bill-business';

    /** @var BusinessTransactionManager */
    private $transManager;
    private UsrRepository $usersRepo;

    private LoggerInterface $logger;

    public function __construct(BusinessTransactionManager $businessTransactionManager, EntityManagerInterface $em, LoggerInterface $paymentLogger)
    {
        parent::__construct();

        $this->transManager = $businessTransactionManager;
        $this->usersRepo = $em->getRepository(Usr::class);
        $this->logger = $paymentLogger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Bill businesses, renew membership, should be run once per month')
            ->addArgument('UserID', InputArgument::OPTIONAL, 'Business UserID filter')
            ->addOption('startFrom', null, InputOption::VALUE_REQUIRED, 'start from this user id, to continue failed billing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $businesses = 0;
        $members = 0;

        $qb = $this->usersRepo->createQueryBuilder("u");
        $qb->where("u.accountlevel = " . ACCOUNT_LEVEL_BUSINESS);

        if (!empty($input->getArgument('UserID'))) {
            $qb->andWhere("u.userid = " . $input->getArgument('UserID'));
        }

        if (!empty($input->getOption('startFrom'))) {
            $qb->andWhere("u.userid >= " . $input->getOption('startFrom'));
        }

        foreach ($qb->getQuery()->execute() as $business) {
            /** @var Usr $business */
            $members += $this->transManager->billMonth($business);
            $businesses++;
        }
        $this->logger->info(sprintf(
            "done, businesses: %d, members: %d",
            $businesses, $members
        ));

        return 0;
    }
}
