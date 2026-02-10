<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\HistoryAnalyzer\Analyzer;
use AwardWallet\MainBundle\Service\HistoryAnalyzer\AnalyzerResponse;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CalcCrossAirlinesCommand extends Command
{
    protected static $defaultName = 'aw:stat:calc-cross-airlines';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $replicaConn;
    /**
     * @var Analyzer
     */
    private $analyzer;
    /**
     * @var S3Client
     */
    private $s3;
    /**
     * @var \Twig_Environment
     */
    private $twig;
    /**
     * @var Connection
     */
    private $connection;
    private string $awsS3Bucket;

    public function __construct(
        LoggerInterface $logger,
        $replicaUnbufferedConnection,
        Analyzer $analyzer,
        S3Client $s3Client,
        // Twig\Environment $twig,
        Connection $connection,
        $awsS3Bucket
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->replicaConn = $replicaUnbufferedConnection;
        $this->analyzer = $analyzer;
        $this->s3 = $s3Client;
        // $this->twig = $twig;
        $this->connection = $connection;
        $this->awsS3Bucket = $awsS3Bucket;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calc miles usage across airlines')
            ->addOption('sourceProviders', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Source provider IDs', [136]) // malaysia
            ->addOption('targetProviders', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Target provider IDs', [
                136, // malaysia
                71, // singapore
                33, // qantas
                48, // emirates
                31, // British
                35, // Cathay
            ])
            ->addOption("userId", null, InputOption::VALUE_REQUIRED, 'limit to this user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("opening query");
        $q = $this->replicaConn->executeQuery("
            select 
                h.*,
                ta.ProviderID,
                ta.UserID,
                ta.UserAgentID,
                ta.TotalBalance,
                ta.SuccessCheckDate,
                ta.SavePassword = 1 and ta.ErrorCode = " . ACCOUNT_CHECKED . " as CanUpdate
            from
                AccountHistory h
                join Account ta on h.AccountID = ta.AccountID
                join Account sa 
                    on ta.UserID = sa.UserID 
                    and (ta.UserAgentID = sa.UserAgentID or (ta.UserAgentID is null and sa.UserAgentID is null))
                    and sa.ProviderID in (:sourceProviders) 
            where
                h.PostingDate >= adddate(now(), interval -1 year)
                and ta.ProviderID in (:targetProviders)
                " . (!empty($input->getOption('userId')) ? " and ta.UserID = " . intval($input->getOption('userId')) : "") . "
            order by 
                ta.ProviderID, ta.AccountID
            ",
            ["sourceProviders" => $input->getOption("sourceProviders"), "targetProviders" => $input->getOption("targetProviders")],
            ["sourceProviders" => Connection::PARAM_INT_ARRAY, "targetProviders" => Connection::PARAM_INT_ARRAY]
        );
        $this->logger->info("got query");
        $rowCount = 0;
        $accountCount = 0;
        $updateTime = time();
        $lastRow = null;
        $accountRows = [];
        $rows = [];

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if ((time() - $updateTime) > 30) {
                $this->logger->info("processing", ["rows" => $rowCount]);
                $updateTime = time();
            }

            if (($lastRow['AccountID'] ?? null) != $row['AccountID']) {
                if (!empty($accountRows)) {
                    $rows[] = $this->processAccount($lastRow, $accountRows);
                    $accountRows = [];
                    $accountCount++;
                }
            }
            $accountRows[] = $row;
            $lastRow = $row;
            $rowCount++;
        }

        if (!empty($accountRows)) {
            $rows[] = $this->processAccount($lastRow, $accountRows);
            $accountCount++;
        }
        $this->logger->info("query done", ["rows" => $rowCount, "accounts" => $accountCount]);

        $data = json_encode([
            "rows" => $this->combineByUsers($rows, $input->getOption('targetProviders')),
            "sourceProviderNames" => join(", ", $this->loadProviders($input->getOption('sourceProviders'))),
            "targetProviders" => $this->loadProviders($input->getOption('targetProviders')),
            "created" => date("Y-m-d H:i:s"),
        ]);
        $this->s3->putObject(['Key' => 'cross_airlines_report.json', 'Bucket' => $this->awsS3Bucket, 'Body' => $data]);
        $this->logger->info("report created");

        return 0;
    }

    private function processAccount(array $info, array $rows)
    {
        $response = $this->analyzer->analyze($rows);

        if ($response->excluded > 0) {
            $this->logger->info("excluded matches", ["AccountID" => $info["AccountID"], "ProviderID" => $info["ProviderID"], "Excluded" => $response->excluded]);
        }
        $response->rows = []; // free memory

        return [
            'UserID' => $info['UserID'],
            'UserAgentID' => $info['UserAgentID'],
            'AccountID' => $info['AccountID'],
            'TotalBalance' => $info['TotalBalance'],
            'SuccessCheckDate' => $info['SuccessCheckDate'],
            'CanUpdate' => $info['CanUpdate'],
            'ProviderID' => $info['ProviderID'],
            'Response' => $response,
        ];
    }

    private function loadProviders(array $providers)
    {
        $result = $this->connection->executeQuery("select ProviderID, DisplayName from Provider where ProviderID in (?)", [$providers], [Connection::PARAM_INT_ARRAY])->fetchAll(\PDO::FETCH_KEY_PAIR);
        uksort($result, function ($a, $b) use ($providers) {
            return array_search($a, $providers) - array_search($b, $providers);
        });

        return $result;
    }

    private function combineByUsers(array $rows, array $targetProviders)
    {
        $this->logger->info("combining by users");
        $result = [];
        $skip = [];

        foreach ($rows as $row) {
            $key = $row['UserID'] . '_' . $row['UserAgentID'];

            if (!isset($result[$key])) {
                $result[$key] = [
                    'UserID' => $row['UserID'],
                    'UserAgentID' => $row['UserAgentID'],
                ];
            }
            $subKey = 'Provider_' . $row['ProviderID'];

            if (!isset($result[$key][$subKey])) {
                $result[$key][$subKey] = [
                    'Response' => $row['Response'],
                    'AccountID' => $row['AccountID'],
                    'TotalBalance' => $row['TotalBalance'],
                    'SuccessCheckDate' => $row['SuccessCheckDate'],
                    'CanUpdate' => $row['CanUpdate'],
                ];
            } else {
                $skip[$key] = $row['ProviderID'];
            }
        }

        if (count($skip) > 0) {
            $this->logger->info("skips", ["count" => count($skip)]);

            foreach ($skip as $key => $providerId) {
                $this->logger->info("skipped $key, double account of $providerId");
            }
        }

        foreach ($result as &$user) {
            foreach ($targetProviders as $providerId) {
                if (!isset($user['Provider_' . $providerId])) {
                    $account = $this->getAccount($user['UserID'], $user['UserAgentID'], $providerId);

                    if (!empty($account)) {
                        $user['Provider_' . $providerId] = array_merge(['Response' => new AnalyzerResponse()], $account);
                    }
                }
            }
        }

        return $result;
    }

    private function getAccount($userId, $userAgentId, $providerId)
    {
        $qb = $this->replicaConn->createQueryBuilder();
        $qb
            ->select("Account.AccountID", "Account.SuccessCheckDate", "Account.TotalBalance", "Account.SavePassword = 1 and Account.ErrorCode = " . ACCOUNT_CHECKED . " as CanUpdate")
            ->from("Account")
            ->where("Account.UserID = :userId")
            ->andWhere("Account.ProviderID = :providerId")
            ->setParameters(["userId" => $userId, "providerId" => $providerId])
        ;

        if (empty($userAgentId)) {
            $qb->andWhere("Account.UserAgentID is null");
        } else {
            $qb->andWhere("Account.UserAgentID = :userAgentId");
            $qb->setParameter("userAgentId", $userAgentId);
        }

        return $qb->execute()->fetch(\PDO::FETCH_ASSOC);
    }
}
