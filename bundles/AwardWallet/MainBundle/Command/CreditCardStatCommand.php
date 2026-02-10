<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Globals\Utils\Criteria;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\column;
use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;
use function AwardWallet\MainBundle\Globals\Utils\f\orderBy;
use function AwardWallet\MainBundle\Globals\Utils\iter\fromCallable;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class CreditCardStatCommand extends Command
{
    private const DETECTED_PROPERTY = 3928;
    private const DISPLAY_NAME_PATTERNS = [
        '/^(.+)\s*-\s*\d{3,20}$/ims' => '$1',
        '/^(.+)\s+\d{3,20}$/ims' => '$1',
        '/^(.+)\s*\(-\d+\)$/ims' => '$1',
        '/^(.+)\s*\.\.\.\d+\s*(\([^\)]+\))$/ims' => '$1 $2',
        '/^(.+)\s*\(\.\.\.\d+\)$/ims' => '$1',
        '/^(.+)\s*ending (?:with|in) \d+\s*(\([^\)]+\))$/ims' => '$1 $2',
        '/^(.+)\s*ending (?:with|in) \d+$/ims' => '$1 $2',

        '/\(?\.\.\.\d+\)?/' => '',
        '/x{2,20}\d{2,20}/i' => '',
        '/\(?(card )?ending (?:with|in) \d+\)/i' => '',
        '/[\*0-9-]{10,30}/i' => '',
        '/\([M0-9]+\)/i' => '',
        '/(AAdvantage® Member): [\.a-z0-9]+/i' => '$1',
        '/[x0-9-]{2,20}/i' => '',
        '/-/' => '',
    ];

    protected static $defaultName = 'aw:credit-card-stat';

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string[]
     */
    private $patterns;

    /**
     * @var string[]
     */
    private $replacements;
    private Connection $unbufConnection;

    public function __construct(Connection $unbufConnection)
    {
        parent::__construct();
        $this->unbufConnection = $unbufConnection;
    }

    public function configure()
    {
        $this
            ->setDescription('Credit card stat');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->patterns = \array_keys(self::DISPLAY_NAME_PATTERNS);
        $this->replacements = \array_values(self::DISPLAY_NAME_PATTERNS);

        $this->connection = $this->unbufConnection;
        $providerStmt = $this->connection->executeQuery('select ProviderID, Code, DisplayName from Provider where Kind = ?', [PROVIDER_KIND_CREDITCARD], [\PDO::PARAM_INT]);
        $ccProviders =
            stmtAssoc($providerStmt)
            ->reindex(function ($row) { return (int) $row['ProviderID']; })
            ->toArrayWithKeys();

        $detectedCardsIter =
            it(fromCallable(function () {
                yield from $this->connection->executeQuery('
                    select 
                       a.ProviderID, 
                       ap.Val
                    from AccountProperty ap
                    join Account a on ap.AccountID = a.AccountID
                    where
                        ap.ProviderPropertyID = ?',
                    [self::DETECTED_PROPERTY],
                    [\PDO::PARAM_INT]
                );
            }))
            ->flatMap(function (array $row) use ($ccProviders) {
                if (!isset($ccProviders[(int) $row['ProviderID']])) {
                    return;
                }

                $detectedCards = @\unserialize($row['Val']);

                if (!\is_array($detectedCards)) {
                    return;
                }

                foreach ($detectedCards as $detectedCard) {
                    if (!isset($detectedCard['DisplayName'])) {
                        continue;
                    }

                    yield [$detectedCard['DisplayName'], $row['ProviderID']];
                }
            });

        $subaccountsIter =
            fromCallable(function () use ($ccProviders) {
                $stmt = $this->connection->executeQuery("
                    select sa.DisplayName, a.ProviderID
                    from Account a
                    join SubAccount sa on a.AccountID = sa.AccountID
                    where 
                        a.ProviderID in (?) and
                        position('FICO' in sa.Code) = 0 and
                        position('FICO' in sa.DisplayName)",
                    [\array_keys($ccProviders)],
                    [Connection::PARAM_INT_ARRAY]
                );
                $stmt->setFetchMode(\PDO::FETCH_NUM);

                yield from $stmt;
            });

        $detectedCardsMap = [];

        foreach (
            it($detectedCardsIter)
            ->chain($subaccountsIter)
            ->map(\Closure::fromCallable([$this, 'extractCardName'])) as [$cardName, $providerId]
        ) {
            $lowerName = \strtolower($cardName);

            if (isset($detectedCardsMap[$lowerName])) {
                $detectedCardsMap[$lowerName]['count']++;
            } else {
                $detectedCardsMap[$lowerName] = [
                    'count' => 1,
                    'name' => $cardName,
                    'provider' => $ccProviders[$providerId],
                ];
            }
        }

        \usort(
            $detectedCardsMap,
            orderBy(
                [compareBy(column('count')), Criteria::DESC],
                [compareBy(column('name')), Criteria::ASC]
            )
        );

        $this->exportCSVRow([
            'Provider',
            'Card',
            'Count',
        ]);

        foreach ($detectedCardsMap as $card) {
            [
                'count' => $count,
                'name' => $cardName,
                'provider' => ['DisplayName' => $providerName]
            ] = $card;

            $this->exportCSVRow([
                $providerName,
                $cardName,
                $count,
            ]);
        }

        return 0;
    }

    protected function exportCSVRow(array $arValues)
    {
        foreach ($arValues as $nKey => $sValue) {
            $sValue = \str_replace("\"", "\"\"", $sValue);
            $sValue = "\"" . $sValue . "\"";
            $arValues[$nKey] = $sValue;
        }
        echo \implode(',', $arValues) . "\n";
    }

    protected function extractCardName(array $nameData): array
    {
        [$displayName, $providerId] = $nameData;
        $count = 0;
        $result = \preg_replace($this->patterns, $this->replacements, $displayName, -1, $count);

        if ($count) {
            return [
                \preg_replace('/\s{2,}/', ' ', \trim($result)),
                $providerId,
            ];
        }

        return $nameData;
    }
}
