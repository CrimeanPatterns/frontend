<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Service\MerchantLookup;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LinkMerchantsToRetailProvidersCommand extends Command
{
    protected const MAX_MERCHANTS_FOR_AUTO = 1;
    protected static $defaultName = 'aw:credit-cards:link-merchants-to-retail-providers';
    private Connection $dbConnection;
    private MerchantLookup $merchantLookup;

    public function __construct(
        Connection $dbConnection,
        MerchantLookup $merchantLookup
    ) {
        parent::__construct();

        $this->dbConnection = $dbConnection;
        $this->merchantLookup = $merchantLookup;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $retailProvidersList = $this->loadRetail();
        $output->writeln(\sprintf("Total %d retail provider(s) found.", \count($retailProvidersList)));
        $detectedProvidersCount = 0;
        $insertedLinksCount = 0;

        foreach ($retailProvidersList as [
            'ProviderID' => $providerId,
            'Code' => $code,
            'ShortName' => $shortName,
            'Name' => $name,
        ]) {
            $output->writeln("Processing {$name} ({$code} - {$providerId})");
            $this->dbConnection->beginTransaction();
            $needRollback = true;

            try {
                $existingMerchantsMap = \array_flip($this->dbConnection
                    ->executeQuery('select MerchantID from RetailProviderMerchant where ProviderID = ? FOR UPDATE', [$providerId])
                    ->fetchFirstColumn()
                );

                $existingMerchantsCount = \count($existingMerchantsMap);

                if ($existingMerchantsCount >= self::MAX_MERCHANTS_FOR_AUTO) {
                    $output->writeln(\sprintf("Existing merchants {$existingMerchantsCount} >= %d, skipping...", self::MAX_MERCHANTS_FOR_AUTO));

                    continue;
                }

                $vacantCount = self::MAX_MERCHANTS_FOR_AUTO * 2;

                $merchantsList =
                    it(
                        $this->merchantLookup->getMerchantLookupList($shortName, $vacantCount, false) ?:
                        $this->merchantLookup->getMerchantLookupList($name, $vacantCount, false)
                    )
                    ->filterNot(fn (array $merchant) => isset($existingMerchantsMap[$merchant['id']]))
                    ->take(self::MAX_MERCHANTS_FOR_AUTO - $existingMerchantsCount)
                    ->toArray();

                if (!$merchantsList) {
                    $output->writeln('No unique merchants found.');

                    continue;
                }

                $detectedProvidersCount++;
                $output->writeln(\sprintf(
                    "Found unique %d merchants:\n%s",
                    \count($merchantsList),
                    it($merchantsList)
                    ->map(fn (array $merchant) => "\t{$merchant['label']} ({$merchant['id']} - https://awardwallet.com/merchants/{$merchant['nameToUrl']})")
                    ->joinToString("\n")
                ));

                $inserted = $this->insert($merchantsList, (int) $providerId);
                $insertedLinksCount += $inserted;
                $output->writeln("Inserted {$inserted} rows.");

                $this->dbConnection->commit();
                $needRollback = false;
            } finally {
                if ($needRollback) {
                    $this->dbConnection->rollBack();
                }
            }
        }

        $output->writeln("Found merchants for {$detectedProvidersCount} retail provider(s)");
        $output->writeln("{$insertedLinksCount} links inserted");

        return 0;
    }

    protected function loadRetail(): array
    {
        return $this->dbConnection
            ->executeQuery('select ProviderID, Code, ShortName, Name from Provider')
            ->fetchAllAssociative();
    }

    protected function insert(array $merchants, int $providerId): int
    {
        $sqlParts = [];
        $params = [];

        foreach ($merchants as $merchant) {
            $params[] = $merchant['id'];
            $params[] = $providerId;
            $sqlParts[] = "(?, 1, ?)";
        }

        $sql = 'INSERT IGNORE INTO RetailProviderMerchant (MerchantID, Auto, ProviderID) VALUES ' .

                it($sqlParts)
                ->joinToString(', ')
        ;

        return $this->dbConnection->executeStatement($sql, $params);
    }
}
