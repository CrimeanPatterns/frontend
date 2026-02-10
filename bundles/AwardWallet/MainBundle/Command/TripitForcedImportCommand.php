<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TripitForcedImportCommand extends Command
{
    private const USER_BATCH_SIZE = 1000;

    public static $defaultName = 'aw:tripit:run-import';
    private EntityManagerInterface $entityManager;
    private TripitHelper $tripitHelper;
    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        EntityManagerInterface $entityManager,
        TripitHelper $tripitHelper,
        LoggerInterface $logger,
        Connection $unbufConnection,
        Connection $connection
    ) {
        $this->entityManager = $entityManager;
        $this->tripitHelper = $tripitHelper;
        $this->logger = $logger;
        $this->unbufConnection = $unbufConnection;
        $this->connection = $connection;
        parent::__construct();
    }

    public function configure()
    {
        $this->setDescription('Runs import of reservations for users who haven\'t received a notification in a while')
            ->addOption('weeks', null, InputOption::VALUE_REQUIRED, 'Time period in weeks since the last successful synchronization', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = (new \DateTime())->sub(new \DateInterval('P' . $input->getOption('weeks') . 'W'));
        $query = $this->unbufConnection->executeQuery('
            SELECT `UserID`
            FROM `Usr`
            WHERE `TripitOauthToken` IS NOT NULL AND (`TripitLastSync` < :date OR `TripitLastSync` IS NULL)
            ORDER BY `UserID` ASC',
            ['date' => $date->format('Y-m-d H:i:s')]
        );
        $query->execute();
        $i = 0;

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $user = $this->entityManager->getRepository(Usr::class)->find($row['UserID']);

            if (!$user || $user->getTripitOauthToken()['oauth_access_token'] === null) {
                continue;
            }

            $tripitUser = new TripitUser($user, $this->entityManager);
            $result = $this->tripitHelper->list($tripitUser);

            if ($result->getSuccess() && !empty($result->getItineraries())) {
                $this->logger->info('TripIt forced import: ' . json_encode([
                    'userId' => $tripitUser->getCurrentUser()->getId(),
                    'added' => $result->getCountAdded(),
                    'updated' => $result->getCountUpdated(),
                ]));

                if ($result->getCountAdded() > 0) {
                    $this->logger->notice('TripIt forced import: new reservations have been added');
                }

                $user->setTripitLastSync(new \DateTime());
                $this->entityManager->flush();
            }

            ++$i;

            if (($i % self::USER_BATCH_SIZE) === 0) {
                $this->entityManager->clear();
            }
        }

        return 0;
    }
}
