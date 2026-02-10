<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Repositories\CurrencyRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixReservationCurrencyCommand extends Command
{
    public static $defaultName = 'aw:fix:reservation-currency';

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private CurrencyRepository $currencyRepository;

    private SpentAwardsParser $spentAwardsParser;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, CurrencyRepository $currencyRepository, SpentAwardsParser $spentAwardsParser)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->currencyRepository = $currencyRepository;
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
                and r.pricingInfo.currencyCode is null
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
                $currency = $this->extractCurrency($reservation->getPricingInfo()->getSpentAwards());

                if ($currency === null || $currency === $reservation->getPricingInfo()->getCurrencyCode()) {
                    return;
                }

                $this->logger->info("changing currency for {$reservation->getId()}: {$reservation->getPricingInfo()->getSpentAwards()} -> {$currency}");

                if ($apply) {
                    $reservation->getPricingInfo()->setCurrencyCode($currency);

                    if ($note !== null) {
                        $reservation->getHotelPointValue()->setNote($note);
                    }
                    $this->em->flush();
                }
            });
    }

    /**
     * @param string $spentAwards - like '69.52 € + 11,000 points'
     * @return string|null - 3-char currency code, like EUR
     */
    private function extractCurrency(string $spentAwards): ?string
    {
        $parsed = $this->spentAwardsParser->parse($spentAwards);

        if ($parsed === null) {
            return null;
        }

        $symbol = $parsed->getCurrencySymbol();

        if ($symbol === null) {
            return null;
        }

        static $codeToSymbolMap;

        if ($codeToSymbolMap === null) {
            $codeToSymbolMap = it($this->currencyRepository->findAll())
                ->flatMap(function (Currency $currency) {
                    return [$currency->getCode() => $currency->getSign()];
                })
                ->toArrayWithKeys()
            ;
        }

        if (array_key_exists($symbol, $codeToSymbolMap)) {
            return $symbol;
        }

        if (preg_match('#^[A-Z]{3}$#uims', $symbol)) {
            return $symbol;
        }

        $code = array_search($symbol, $codeToSymbolMap);

        if ($code === false) {
            return null;
        }

        return $code;
    }
}
