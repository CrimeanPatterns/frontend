<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportBonusJsonCommand extends Command
{
    private const FILTER_PROVIDER_ID = [
        Provider::AMEX_ID,
        Provider::BANKOFAMERICA_ID,
        Provider::BARCLAYCARD_ID,
        Provider::CAPITAL_ONE_ID,
        Provider::CHASE_ID,
        Provider::CITI_ID,
        Provider::DISCOVER_ID,
        Provider::USBANK_ID,
        Provider::WELLSFARGO_ID,
    ];

    private const NO_CATEGORY_GROUP_NAME = 'All Purchases';

    private const CARD_TYPE_IS_BUSINESS = [
        false => 'personal',
        true => 'business',
    ];
    private const DATE_FORMAT = 'm/d/Y';
    public static $defaultName = 'aw:credit-cards:export-bonus-json';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private MileValueService $mileValueService;
    private MileValueCards $mileValueCards;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Connection $connection,
        MileValueService $mileValueService,
        MileValueCards $mileValueCards
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->mileValueService = $mileValueService;
        $this->mileValueCards = $mileValueCards;
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'filename for saving')
            ->addOption('allcards', 'a', InputOption::VALUE_NONE, 'disable provider filter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isFetchAllCards = $input->getOption('allcards');
        $jsonFilePath = $input->getOption('file');

        if (file_exists($jsonFilePath) && !is_writable($jsonFilePath)) {
            throw new \RuntimeException('JSON file is not writable');
        }

        $data = [];
        $creditCards = $isFetchAllCards
            ? $this->entityManager->getRepository(CreditCard::class)->findAll()
            : $this->entityManager->getRepository(CreditCard::class)->findBy([
                'provider' => self::FILTER_PROVIDER_ID,
                'isApiReady' => true,
            ]);
        $shoppingCategoryGroupsCards = $this->getShoppingCategoryGroups();
        $creditCardMerchantGroupsCards = $this->getMerchantGroups();

        /** @var CreditCard $creditCard */
        foreach ($creditCards as $creditCard) {
            $cardId = $creditCard->getId();
            // $mileValueItem = $this->mileValueService->getMileValueViaCreditCardId($creditCard);
            $mileValueItem = $this->mileValueCards->getCardMileValueCost($creditCard);

            $card = [
                'cardId' => $cardId,
                // 'cardProvider' => $creditCard->getProvider()->getDisplayname(),
                'issuingBank' => $creditCard->getProvider()->getName(),
                'cardName' => $creditCard->getName(),
                'cardType' => self::CARD_TYPE_IS_BUSINESS[$creditCard->isBusiness()],
                'isCashback' => $this->boolField($creditCard->isCashBackOnly()),
                'isDiscontinued' => $this->boolField($creditCard->isDiscontinued()),
                'shortEarningDescription' => $this->cleanStringField($creditCard->getDescription()),
                'awardWalletPointValue' => $mileValueItem->getPrimaryValue(),
                'currencyName' => $creditCard->isCashBackOnly()
                    ? (CreditCard::CASHBACK_TYPE_POINT === $creditCard->getCashBackType() ? 'Point' : 'USD')
                    : $creditCard->getPointName(),
                'earningCategories' => [],
                'earningMerchants' => [],
            ];

            if (empty($card['issuingBank'])) {
                throw new \Exception('Empty provider name');
            }

            if (array_key_exists($cardId, $shoppingCategoryGroupsCards)) {
                foreach ($shoppingCategoryGroupsCards[$cardId] as $shoppingCategory) {
                    $card['earningCategories'][] = [
                        'categoryId' => (int) $shoppingCategory['ShoppingCategoryGroupID'],
                        'categoryName' => $shoppingCategory['Name'],
                        'multiplier' => $this->floatField($shoppingCategory['Multiplier']),
                        'startDate' => $this->dateField($shoppingCategory['StartDate']),
                        'endDate' => $this->dateField($shoppingCategory['EndDate']),
                        'description' => $this->cleanStringField($shoppingCategory['Description']),
                    ];
                }
            }

            if (array_key_exists($cardId, $creditCardMerchantGroupsCards)) {
                foreach ($creditCardMerchantGroupsCards[$cardId] as $creditCardMerchantGroupsCard) {
                    $card['earningMerchants'][] = [
                        'merchantGroupId' => (int) $creditCardMerchantGroupsCard['MerchantGroupID'],
                        'merchantGroupName' => $creditCardMerchantGroupsCard['Name'],
                        'multiplier' => $this->floatField($creditCardMerchantGroupsCard['Multiplier']),
                        'startDate' => $this->dateField($creditCardMerchantGroupsCard['StartDate']),
                        'endDate' => $this->dateField($creditCardMerchantGroupsCard['EndDate']),
                        'description' => $this->cleanStringField($creditCardMerchantGroupsCard['Description']),
                        'merchantNames' => $this->getMerchants($creditCardMerchantGroupsCard['MerchantGroupID']),
                    ];
                }
            }

            $data[] = $card;
        }

        try {
            $jsonFileHandle = '-' === $jsonFilePath ?
                \STDOUT :
                \fopen($jsonFilePath, 'w');
            $writeResult = \fwrite($jsonFileHandle, \json_encode(['cards' => $data]));
        } finally {
            if (isset($jsonFileHandle)) {
                \fclose($jsonFileHandle);
            }
        }

        if (false === $writeResult) {
            throw new \RuntimeException('JSON Credit Card Bonus - file write error');
        }

        $output->writeln(['--', 'data written successfully', '--']);
    }

    private function getShoppingCategoryGroups(): array
    {
        $shoppingCategoryGroups = $this->connection->fetchAllAssociative('
            SELECT
                ccscg.ShoppingCategoryGroupID, ccscg.CreditCardID, ccscg.Multiplier, ccscg.StartDate, ccscg.EndDate, ccscg.Description,
                scg.Name
            FROM CreditCardShoppingCategoryGroup ccscg
            LEFT JOIN ShoppingCategoryGroup scg ON (scg.ShoppingCategoryGroupID = ccscg.ShoppingCategoryGroupID)
            ORDER BY ccscg.SortIndex ASC, ccscg.Multiplier ASC, ccscg.StartDate ASC
        ');

        $shoppingCategoryGroupsCards = [];

        foreach ($shoppingCategoryGroups as $categoryGroup) {
            $cardId = (int) $categoryGroup['CreditCardID'];

            if (null === $categoryGroup['ShoppingCategoryGroupID'] && empty($categoryGroup['Name'])) {
                $categoryGroup['Name'] = self::NO_CATEGORY_GROUP_NAME;
            }
            $shoppingCategoryGroupsCards[$cardId][] = $categoryGroup;
        }

        return $shoppingCategoryGroupsCards;
    }

    private function getMerchantGroups(): array
    {
        $creditCardMerchantGroups = $this->connection->fetchAllAssociative('
            SELECT
                ccmg.CreditCardID, ccmg.MerchantGroupID, ccmg.Multiplier, ccmg.StartDate, ccmg.EndDate, ccmg.Description,
                mg.Name
            FROM CreditCardMerchantGroup ccmg
            JOIN MerchantGroup mg ON (mg.MerchantGroupID = ccmg.MerchantGroupID)
            ORDER BY ccmg.SortIndex ASC
        ');

        $creditCardMerchantGroupsCards = [];

        foreach ($creditCardMerchantGroups as $creditCardMerchantGroup) {
            $cardId = (int) $creditCardMerchantGroup['CreditCardID'];
            $creditCardMerchantGroupsCards[$cardId][] = $creditCardMerchantGroup;
        }

        return $creditCardMerchantGroupsCards;
    }

    private function getMerchants($merchantGroupId): array
    {
        static $cache = [];

        if (array_key_exists($merchantGroupId, $cache)) {
            return $cache[$merchantGroupId];
        }

        $rows = $this->connection->fetchAllAssociative('
            SELECT 
                mp.MerchantPatternID, 
                mp.Name
            FROM MerchantPatternGroup mpg
            JOIN MerchantPattern mp ON mp.MerchantPatternID = mpg.MerchantPatternID
            WHERE mpg.MerchantGroupID = ?',
            [$merchantGroupId],
            [\PDO::PARAM_INT]
        );

        foreach ($rows as $row) {
            $cache[$merchantGroupId][] = [
                'merchantId' => (int) $row['MerchantPatternID'],
                'merchantName' => $row['Name'],
            ];
        }

        return $cache[$merchantGroupId] ?? [];
    }

    private function floatField($value): float
    {
        return (float) $value;
    }

    private function dateField($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return date(self::DATE_FORMAT, strtotime($date));
    }

    private function boolField($value)
    {
        return $value;
    }

    private function cleanStringField($value)
    {
        if (is_string($value)) {
            $value = str_replace(["\n", "\n", '<br>', '<br/>'], ' ', $value);
            $value = strip_tags($value);
            $value = preg_replace('/\s+/', ' ', $value);
        }

        return $value;
    }
}
