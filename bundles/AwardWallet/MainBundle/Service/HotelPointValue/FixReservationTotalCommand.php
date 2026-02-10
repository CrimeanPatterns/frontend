<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixReservationTotalCommand extends Command
{
    public static $defaultName = 'aw:fix:reservation-total';

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private SpentAwardsParser $spentAwardsParser;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, SpentAwardsParser $spentAwardsParser)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->spentAwardsParser = $spentAwardsParser;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise just search')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'max records')
            ->addOption('note', null, InputOption::VALUE_REQUIRED, 'set not on changed records')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $reservations = $this->loadReservations($input->getOption('limit'));
        ConsoleTable::render($reservations, $output);

        $this->fix($reservations, $input->getOption('apply'), $input->getOption('note'));

        if ($input->getOption('apply')) {
            ConsoleTable::render($reservations, $output);
        }

        $this->logger->info("done, processed " . count($reservations) . " reservations");
    }

    private function loadReservations(?int $limit): array
    {
        $this->logger->info("loading reservations");

        $q = $this->em->createQuery("
            select
                r
            from
                AwardWallet\MainBundle\Entity\Reservation r
                join r.hotelPointValue as hpv
            where 
                r.provider is not null 
                and r.pricingInfo.spentAwards like '%+%points'
                and r.pricingInfo.total is null
        ");
        $q->setMaxResults($limit);

        $results = $q->execute();
        $this->logger->info("loaded " . count($results) . " rows");

        return $results;
    }

    private function fix(array $reservations, bool $apply, ?string $note): void
    {
        it($reservations)
            ->apply(function (Reservation $reservation) use ($apply, $note) {
                $total = $this->extractTotal($reservation->getPricingInfo()->getSpentAwards());

                if ($total === null || abs($total - $reservation->getPricingInfo()->getTotal()) < 0.01) {
                    return;
                }

                $this->logger->info("changing total for {$reservation->getId()}: {$reservation->getPricingInfo()->getSpentAwards()} -> {$total}");

                if ($apply) {
                    $reservation->getPricingInfo()->setTotal($total);

                    if ($note !== null) {
                        $reservation->getHotelPointValue()->setNote($note);
                    }
                    $this->em->flush();
                }
            });
    }

    /**
     * @param string $spentAwards - like '69.52 € + 11,000 points'
     * @return - total, 69.52
     */
    private function extractTotal(string $spentAwards): ?float
    {
        $parsed = $this->spentAwardsParser->parse($spentAwards);

        if ($parsed === null) {
            return null;
        }

        return $parsed->getTotal();
    }
}
