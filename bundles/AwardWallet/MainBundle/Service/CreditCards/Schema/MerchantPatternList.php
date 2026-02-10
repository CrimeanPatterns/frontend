<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Globals\Utils\ConcurrentArrayFactory;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;
use function Duration\minutes;

class MerchantPatternList extends \TBaseList
{
    private Connection $connection;
    private Process $process;
    private \DateTimeImmutable $merchantUpperDate;
    private Environment $twig;
    private ConcurrentArrayFactory $concurrentArrayFactory;
    private SocksClient $messaging;

    public function __construct(
        string $table,
        array $fields,
        Connection $connection,
        Process $process,
        Environment $twig,
        ConcurrentArrayFactory $concurrentArrayFactory,
        SocksClient $messaging
    ) {
        parent::__construct($table, $fields);
        unset($this->Fields['Stat']);
        unset($this->Fields['ConfidenceIntervalStartDate']);

        $this->connection = $connection;
        $this->merchantUpperDate = new \DateTimeImmutable($connection
            ->executeQuery(
                'select Val from Param where Name = ?',
                [ParameterRepository::MERCHANT_UPPER_DATE],
            )
            ->fetchOne() ?: 'now');

        $this->SQL = "
            select 
                MerchantPatternID,
                Name,
                Patterns,
                DescriptionExamples,
                ClickUrl,
                Transactions,
                DetectPriority,
                TransactionsConfidenceInterval,
                ConfidenceIntervalStartDate,
                null as `Groups`
            from MerchantPattern mp";
        $this->process = $process;
        $this->twig = $twig;
        $this->concurrentArrayFactory = $concurrentArrayFactory;
        $this->messaging = $messaging;
    }

    public function DrawHeader()
    {
        $channelsList = $this->concurrentArrayFactory->create('merchant_pattern_save_progress', minutes(30));

        echo $this->twig->render(
            '@AwardWalletMain/Manager/CreditCards/merchantPatternSaveProgressContent.html.twig',
            [
                "channels" => $channelsList->all(),
                'centrifuge_config' => $this->messaging->getClientData(),
            ]
        );

        parent::DrawHeader();
    }

    public function FormatFields($output = "html")
    {
        $ciStartDate = $this->Query->Fields['ConfidenceIntervalStartDate'];
        unset($this->Query->Fields['ConfidenceIntervalStartDate']);

        parent::FormatFields($output);

        if ($output === 'html') {
            $this->Query->Fields["Groups"] = stmtAssoc($this->connection->executeQuery("
                select
                    mg.MerchantGroupID, 
                    mg.Name
                from 
                    MerchantPatternGroup mpg
                    join MerchantGroup mg on mpg.MerchantGroupID = mg.MerchantGroupID
                where
                    mpg.MerchantPatternID = ?
                order by
                    mg.Name", [$this->OriginalFields["MerchantPatternID"]]))
            ->map(fn (array $row) => "<a href='edit.php?Schema=MerchantGroup&ID={$row['MerchantGroupID']}' target='_blank'>{$row['Name']}</a>")
            ->joinToString(", ");

            if ($this->OriginalFields["ClickUrl"] !== null) {
                $this->Query->Fields["Name"] = "<a href='{$this->OriginalFields["ClickUrl"]}' target='_blank'>{$this->Query->Fields["Name"]}</a>";
            }

            $this->Query->Fields["TransactionsConfidenceInterval"] = isset($ciStartDate) ?
                "<span title='" . ((int) \ceil($this->merchantUpperDate->diff(new \DateTimeImmutable($ciStartDate))->days / 7)) . " week(s), since {$ciStartDate}'>{$this->Query->Fields["TransactionsConfidenceInterval"]}</span>" :
                $this->Query->Fields["TransactionsConfidenceInterval"];
        }
    }

    public function GetEditLinks()
    {
        $result = parent::GetEditLinks();

        $row = $this->OriginalFields;

        $result .= "<br/><a target='_blank' href='?Schema=Merchant&MerchantPatternID={$row['MerchantPatternID']}'>Merchants</a>";

        return $result;
    }

    public function GetFieldFilter($sField, $arField)
    {
        if ($sField === "Groups") {
            return " and mp.MerchantPatternID in (select MerchantPatternID from MerchantPatternGroup where MerchantGroupID = " . (int) $arField["Value"] . ")";
        }

        return parent::GetFieldFilter($sField, $arField);
    }
}
