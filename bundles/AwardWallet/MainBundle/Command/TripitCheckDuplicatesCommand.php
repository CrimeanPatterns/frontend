<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TripitCheckDuplicatesCommand extends Command
{
    public static $defaultName = 'aw:tripit:check-duplicates';
    private EntityManagerInterface $entityManager;
    private TripitHelper $tripitHelper;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TripitHelper $tripitHelper,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->tripitHelper = $tripitHelper;
        $this->logger = $logger;
        parent::__construct();
    }

    public function configure()
    {
        $this->setDescription('Counts the number of duplicate reservations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(Usr::class, 'u');

        $query = $queryBuilder
            ->where($queryBuilder->expr()->isNotNull('u.tripitOauthToken'))
            ->orderBy('u.userid')
            ->getQuery();

        foreach ($query->getResult() as $user) {
            /** @var Usr $user */
            if ($user->getTripitOauthToken()['oauth_access_token'] !== null) {
                $tripitUser = new TripitUser($user, $this->entityManager);
                $result = $this->tripitHelper->list($tripitUser, true);

                if ($result->getSuccess() && !empty($result->getItineraries())) {
                    $this->logger->info('TripIt duplicates: ' . json_encode([
                        'userId' => $tripitUser->getCurrentUser()->getId(),
                        'response' => $result->getItineraries(),
                    ]));
                }
            }
        }

        $output->writeln('Done.');

        return 0;
    }
}
