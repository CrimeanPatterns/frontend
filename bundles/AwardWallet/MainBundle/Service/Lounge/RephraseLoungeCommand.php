<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\AIModel\AIModelService;
use AwardWallet\MainBundle\Service\AIModel\Deepseek\Response;
use AwardWallet\MainBundle\Service\AIModel\TokenCounter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RephraseLoungeCommand extends AbstractAICommand
{
    public static $defaultName = 'aw:rephrase-lounge';

    private EntityManagerInterface $em;

    private Logger $logger;

    private AIModelService $aiModelService;

    private Statement $changeLocationQuery;

    private int $lounges = 0;

    private int $calls = 0;

    private int $promptTokens = 0;

    private int $completionTokens = 0;

    private float $estimatedCost = 0;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $em,
        Logger $logger,
        AIModelService $aiModelService
    ) {
        parent::__construct($connection);

        $this->em = $em;
        $this->logger = $logger;
        $this->aiModelService = $aiModelService;

        $this->changeLocationQuery = $this->connection->prepare('
            UPDATE Lounge
            SET LocationParaphrased = ?
            WHERE LoungeID = ? AND Location = ?
        ');
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Rephrase lounge descriptions using AI API')
            ->addOption('airport', 'a', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Airport code')
            ->addOption('lounge', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Lounge ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filter = [];

        if (!empty($input->getOption('airport'))) {
            $airports = array_map(function (string $airport) {
                return strtoupper(trim($airport));
            }, $input->getOption('airport'));
            $this->logger->info('filtering by airports: ' . implode(', ', $airports));
            $filter[] = sprintf('AirportCode IN (%s)', implode(', ', array_map(function (string $airport) {
                return $this->connection->quote($airport);
            }, $airports)));
        } elseif (!empty($input->getOption('lounge'))) {
            $lounges = array_map(function (string $lounge) {
                return (int) $lounge;
            }, $input->getOption('lounge'));
            $this->logger->info('filtering by lounges: ' . implode(', ', $lounges));
            $filter[] = 'LoungeID IN (' . implode(', ', $lounges) . ')';
        }

        $popularAircodes = $this->getPriorityAircodes();
        $stmt = $this->connection->executeQuery("
            SELECT
                LoungeID
            FROM
                Lounge
            WHERE
                Location IS NOT NULL
                AND Location <> ''
                AND (LocationParaphrased IS NULL OR LocationParaphrased = '')
                " . (empty($filter) ? '' : 'AND ' . implode(' AND ', $filter)) . "
            ORDER BY
                CASE
                    WHEN AirportCode IN ('" . implode("', '", $popularAircodes) . "') THEN 1
                    ELSE 2
                END,
                LoungeID
            LIMIT 5000
        ");

        $batch = [];
        $batchTokens = 0;
        $maxBatchTokens = 3000;
        $maxLounges = 75;

        while ($row = $stmt->fetchAssociative()) {
            $lounge = $this->em->getRepository(Lounge::class)->find($row['LoungeID']);

            if (!$lounge) {
                continue;
            }

            $location = $lounge->getLocation();
            $tokens = TokenCounter::countDeepSeekTokens($location);

            if (($batchTokens + $tokens) > $maxBatchTokens || \count($batch) >= $maxLounges) {
                $this->processBatch($batch);
                $batch = [$lounge];
                $batchTokens = $tokens;
            } else {
                $batch[] = $lounge;
                $batchTokens += $tokens;
            }
        }

        if (!empty($batch)) {
            $this->processBatch($batch);
        }

        $this->logger->info(sprintf(
            'done. Lounges: %d, prompt tokens: %d, completion tokens: %d, api calls: %d, estimated cost: $%.4f',
            $this->lounges,
            $this->promptTokens,
            $this->completionTokens,
            $this->calls,
            $this->estimatedCost
        ));
    }

    private function processBatch(array $lounges)
    {
        if (\count($lounges) === 0) {
            $this->logger->info('processing empty batch');

            return;
        }

        $this->logger->info(sprintf('processing batch of %d lounges', count($lounges)));
        $json = [];

        foreach ($lounges as $lounge) {
            $this->em->refresh($lounge);

            if (empty($lounge->getLocation()) || !is_null($lounge->getLocationParaphrased())) {
                continue;
            }

            $json[$lounge->getId()] = $lounge->getLocation();
        }

        /** @var Response $response */
        $response = $this->aiModelService->sendPrompt(json_encode($json), 'deepseek', [
            'response_json' => true,
            'system_message' => 'The user will provide you with a JSON array of directions explaining how to get to a lounge at an airport. Do not modify the keys in the array. Your task is to change the values within this array by rephrasing these directions. Whenever possible, replace "&" with "and". Remove any non-location details unrelated to the lounge\'s directions. The output should provide clear instructions to find the airport lounge.',
        ]);

        $this->calls++;

        if (!$response->isHttpStatusCodeSuccessful()) {
            $this->logger->error(sprintf('AI API error: %d', $response->getHttpStatusCode()));

            return;
        }

        if (!is_array($response->getRawResponse())) {
            $this->logger->error('AI API response body is not an array');

            return;
        }

        $this->lounges += count($json);
        $this->promptTokens += $response->getPromptTokens() ?? 0;
        $this->completionTokens += $response->getCompletionTokens() ?? 0;
        $this->estimatedCost += $response->getCost() ?? 0;

        if ($response->isTruncated()) {
            $this->processBatch(array_slice($lounges, 0, count($lounges) / 2));
            $this->processBatch(array_slice($lounges, count($lounges) / 2));

            return;
        }

        if (!$response->isSuccessfulFinishReason()) {
            $this->logger->error(sprintf('AI API finish reason is not "stop": %s', $response->getFinishReason()));

            return;
        }

        $responseJson = $response->getContent();

        if (is_null($responseJson)) {
            $this->logger->error('AI API response is not an array');

            return;
        }

        $responseJson = json_decode($responseJson, true);

        if (!is_array($responseJson)) {
            $this->logger->error('AI API response is not an array');

            return;
        }

        $this->logger->info(sprintf('AI API response: %s', json_encode($responseJson)));

        if (\count($responseJson) !== \count($lounges)) {
            $this->logger->error(sprintf(
                'AI API response count (%d) does not match lounges count (%d)',
                \count($responseJson),
                \count($lounges)
            ));
        }

        foreach ($responseJson as $loungeId => $newLocation) {
            if (!isset($json[$loungeId])) {
                $this->logger->error(sprintf('AI API returned lounge ID %d which was not requested', $loungeId));

                continue;
            }

            if (empty($newLocation)) {
                $this->logger->error(sprintf('AI API returned empty location for lounge ID %d', $loungeId));
                unset($json[$loungeId]);

                continue;
            }

            if ($json[$loungeId] === $newLocation) {
                $this->logger->error(sprintf('AI API returned the same location for lounge ID %d', $loungeId));
                unset($json[$loungeId]);

                continue;
            }

            if (mb_strlen($newLocation) > 500) {
                $this->logger->error(sprintf('AI API returned too long location for lounge ID %d', $loungeId));
                unset($json[$loungeId]);

                continue;
            }

            $this->logger->info(sprintf('changing location for LoungeID %d, old: "%s", new: "%s"', $loungeId, $json[$loungeId], $newLocation));
            $this->changeLocationQuery->executeQuery([
                $newLocation,
                $loungeId,
                $json[$loungeId],
            ]);

            unset($json[$loungeId]);
        }

        if (!empty($json)) {
            $this->logger->error(sprintf('AI API did not return locations (%d) for lounges: %s', \count($json), implode(', ', array_keys($json))));
        }
    }
}
