<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BookingUnsharingAllAccountsCommand extends Command
{
    public static $defaultName = 'aw:booking:unsharing';
    private EntityManagerInterface $entityManager;
    private ProgramShareManager $programShareManager;

    public function __construct(EntityManagerInterface $entityManager, ProgramShareManager $programShareManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
        $this->programShareManager = $programShareManager;
    }

    protected function configure()
    {
        $this->setDescription('Remove account sharing for booker purposes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $conn = $em->getConnection();
        $repShare = $em->getRepository(\AwardWallet\MainBundle\Entity\AbShare::class);
        $repUa = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        /** @var ProgramShareManager $shareManager */
        $shareManager = $this->programShareManager;

        $items = $conn->executeQuery(
            "
            SELECT
                s.*
            FROM
                AbShare s
                LEFT JOIN (
                    SELECT
                        UserID,
                        BookerUserID,
                        COUNT(*) AS ActiveRequestsCount
                    FROM
                        AbRequest
                    WHERE
                        Status NOT IN (?, ?)
                        OR LastUpdateDate > NOW() - INTERVAL 1 MONTH
                    GROUP BY UserID, BookerUserID
                ) r ON r.UserID = s.UserID AND r.BookerUserID = s.BookerID
            WHERE
                s.IsApproved = 1
                AND (r.ActiveRequestsCount IS NULL OR r.ActiveRequestsCount = 0)
        ",
            [AbRequest::BOOKING_STATUS_PROCESSING, AbRequest::BOOKING_STATUS_CANCELED],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $processed = 0;

        foreach ($items as $item) {
            /** @var AbShare $entity */
            $entity = $repShare->find($item['AbShareID']);

            if ($entity) {
                /** @var Useragent $ua */
                $ua = $repUa->findOneBy([
                    'agentid' => $entity->getBooker(),
                    'clientid' => $entity->getUser(),
                    'isapproved' => true,
                ]);

                if ($ua) {
                    $shareManager->setUser($entity->getUser());
                    $shareManager->apiSharingDenyAll($entity->getUser(), $ua);
                    $em->remove($entity);
                    $em->flush();
                    $processed++;
                }
            }
        }

        $output->writeln(sprintf('done, processed: %s', $processed));

        return 0;
    }
}
