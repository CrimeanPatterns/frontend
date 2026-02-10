<?php

namespace AwardWallet\MainBundle\Command;

use Aws\DynamoDb\DynamoDbClient;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPasswordsCommand extends Command
{
    protected static $defaultName = 'aw:check-passwords';

    private $found = [];
    private $insertQuery;
    private DynamoDbClient $dynamoDbClient;
    private Connection $connection;

    public function __construct(DynamoDbClient $dynamoDbClient, Connection $connection)
    {
        parent::__construct();

        $this->dynamoDbClient = $dynamoDbClient;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->addOption("login-only", "l", InputOption::VALUE_OPTIONAL, "check login only, do not check passwords")
            ->addOption("dump", null, InputOption::VALUE_OPTIONAL, "dump N records from database")
            ->setDescription('Check account passwords against hacked accounts database');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->dynamoDbClient;
        //		$client->setDefaultOption('debug', true);
        //		$client->addSubscriber(LogPlugin::getDebugPlugin());

        if ($input->getOption('dump')) {
            $this->dump($input->getOption('dump'), $client, $output);

            return 0;
        }

        $connection = $this->connection;
        $params = $connection->getParams();
        $params['driverOptions'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        $unbufferedConnection = new Connection($params, $connection->getDriver());
        $query = $unbufferedConnection->executeQuery("SELECT AccountID, Login, Pass FROM Account WHERE Pass <> '' AND Pass IS NOT NULL");
        $loginOnly = !empty($input->getOption("login-only"));
        $connection->exec("DELETE FROM BadPassword");
        $this->insertQuery = $connection->prepare("INSERT INTO BadPassword(AccountID, Login) values(?, ?)");

        // Each item will contain the attributes we added
        $count = 0;
        $batch = [];

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $batch[] = $row;

            if (count($batch) == 100) {
                $this->processBatch($batch, $client, $output, $loginOnly);
                $batch = [];
            }
            $count++;

            if (($count % 1000) == 0) {
                $output->writeln("processed $count items, found: " . count($this->found) . "..");
            }
        }

        if (count($batch)) {
            $this->processBatch($batch, $client, $output, $loginOnly);
        }
        $output->writeln("done, processed $count items, found: " . var_export($this->found, true));

        return 0;
    }

    private function dump($count, DynamoDbClient $client, OutputInterface $output)
    {
        $output->writeln("dumping $count records");
        $response = $client->scan(['TableName' => 'users', 'Limit' => intval($count)]);

        foreach ($response as $item) {
            $output->writeln(var_export($item, true));
        }
    }

    private function processBatch(array $batch, DynamoDbClient $client, OutputInterface $output, $loginOnly)
    {
        $keys = [];

        foreach ($batch as &$row) {
            $row['Pass'] = DecryptPassword($row['Pass']);

            if ($loginOnly) {
                if (!empty($row['Login'])) {
                    $keys[$row['Login']] = ['username' => ['S' => $row['Login']]];
                }
            } else {
                if (!empty($row['Pass']) && !empty($row['Login'])) {
                    $keys[$row['Login'] . $row['Pass']] = ['username' => ['S' => $row['Login']], 'password' => ['S' => $row['Pass']]];
                }
            }
        }
        $keys = [
            'users' => [
                'Keys' => array_values($keys),
                'ConsistentRead' => true,
            ],
        ];

        $packet = 0;

        while (!empty($keys) > 0 && $packet < 3) {
            $result = $client->batchGetItem([
                'RequestItems' => $keys,
            ]);
            //			$output->writeln(var_export($result, true));

            $items = $result->getPath("Responses/users");

            if (count($items) > 0) {
                foreach ($items as $item) {
                    $found = false;

                    foreach ($batch as $row) {
                        if ($row['Login'] == $item['username']['S'] && $row['Pass'] == $item['password']['S']) {
                            $this->insertQuery->execute([$row['AccountID'], $row['Login']]);
                            $this->found[] = $row;
                            $output->writeln("found: " . var_export($row, true));
                            $found = true;

                            break;
                        }
                    }

                    if (!$found) {
                        $output->writeln("item not found: " . var_export($item, true));
                    }
                }
            }

            $keys = $result->getPath("UnprocessedKeys");
            $packet++;
        }
    }
}
