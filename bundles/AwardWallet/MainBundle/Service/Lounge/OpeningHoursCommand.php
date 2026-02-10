<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\AIModel\AIModelService;
use AwardWallet\MainBundle\Service\AIModel\Deepseek\Response;
use AwardWallet\MainBundle\Service\AIModel\TokenCounter;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidTimezone;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class OpeningHoursCommand extends AbstractAICommand
{
    public static $defaultName = 'aw:structuring-opening-hours';

    private EntityManagerInterface $em;

    private Logger $logger;

    private AIModelService $aiModelService;

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
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Structure opening hours for lounges using AI API')
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
                OpeningHours IS NOT NULL
                AND OpeningHours->'$.data.type' = 'raw'
                AND OpeningHoursAi IS NULL
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
        $maxLounges = 50;

        while ($row = $stmt->fetchAssociative()) {
            $lounge = $this->em->getRepository(Lounge::class)->find($row['LoungeID']);

            if (!$lounge) {
                continue;
            }

            $openingHours = $lounge->getOpeningHours();

            if (!$openingHours instanceof RawOpeningHours) {
                continue;
            }

            $rawOpeningHours = $openingHours->getRaw();
            $tokens = TokenCounter::countDeepSeekTokens($rawOpeningHours);

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

    /**
     * @param Lounge[] $lounges
     */
    private function processBatch(array $lounges)
    {
        if (\count($lounges) === 0) {
            $this->logger->info('processing empty batch');

            return;
        }

        $this->logger->info(sprintf('processing batch of %d lounges', count($lounges)));
        $json = [];
        $airports = [];
        $lounges = it($lounges)->reindex(function (Lounge $lounge) {
            return $lounge->getId();
        })->toArrayWithKeys();

        foreach ($lounges as $lounge) {
            $this->em->refresh($lounge);

            if (
                empty($lounge->getOpeningHours())
                || !($lounge->getOpeningHours() instanceof RawOpeningHours)
                || !is_null($lounge->getOpeningHoursAi())
            ) {
                continue;
            }

            $airports[$lounge->getAirportCode()] = true;
            $json[$lounge->getId()] = [
                'id' => $lounge->getId(),
                'value' => $lounge->getOpeningHours()->getRaw(),
            ];
        }

        /** @var Response $response */
        $response = $this->aiModelService->sendPrompt('Input: ' . json_encode(array_values($json)), 'deepseek', [
            'response_json' => true,
            'temperature' => 1.0,
            'system_message' => "I will pass you a JSON array of data. Each element of this array has 2 keys: 'id' and 'value'. Your task is to process the 'value' while keeping 'id' unchanged. 'value' may contain text or JSON and includes information about airport lounge operating hours. The data format for 'value' should be as follows: each 'value' (let's call it item) must contain exactly 7 objects under the keys of the days of the week: 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'. Each item is an array of 'range' objects representing the time span the establishment is open. 'Range' contains 3 keys: 'open', 'close', and 'note'. 'open' contains the start time, 'close' the end time. 'note' is a comment about the time range. 'open' and 'close' are in the format 'HHMM' (only numbers, e.g., '0900' for 9:00 AM, '0000' for midnight, '2359' for 11:59 PM) or an event (e.g., 'First flight', 'Last flight'). If open 24 hours on a particular day, 'open' and 'close' should both be '0000'. If closed on a certain day, 'open' and 'close' are not specified, and 'note' should say 'closed'. If lounge hours vary on a certain day, 'open' and 'close' are not specified, and 'note' should say 'Lounge Hours Vary'. If the hours are unknown on a certain day, 'open' and 'close' are not specified, and 'note' should say 'Hours Unknown'. If part or all of the time range is an event, besides 'open' and 'close' in 'note' add a comment. If part of the range is known and another part is not, in 'note' add a range like 'First flight - 2000' or '0830 - Last flight'. In any uncertainty, indicate in 'note' a comment or range. For example, a value 'First flight - 20:30' should be transformed to an object with daily entries each containing a single 'range' with 'open' as 'First flight', 'close' as '2030', and 'note' as 'First flight - 20:30'",
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

        if (isset($responseJson['output']) && is_array($responseJson['output'])) {
            $responseJson = $responseJson['output'];
        } elseif (isset($responseJson['data']) && is_array($responseJson['data'])) {
            $responseJson = $responseJson['data'];
        }

        if (isset($responseJson['id'], $responseJson['value'])) {
            $responseJson = [$responseJson];
        }

        if (\count($responseJson) !== \count($lounges)) {
            $this->logger->error(sprintf(
                'AI API response count (%d) does not match lounges count (%d)',
                \count($responseJson),
                \count($lounges)
            ));
        }

        $timezones = $this->connection->fetchAllKeyValue('
            SELECT
                AirCode,
                TimeZoneLocation
            FROM
                AirCode
            WHERE
                AirCode IN (:airports)
        ', [
            'airports' => array_keys($airports),
        ], ['airports' => Connection::PARAM_STR_ARRAY]);

        foreach ($responseJson as $newOpeningHours) {
            if (
                !is_array($newOpeningHours)
                || \count($newOpeningHours) !== 2
                || !isset($newOpeningHours['id'])
                || !isset($newOpeningHours['value'])
                || empty($newOpeningHours['id'])
                || empty($newOpeningHours['value'])
            ) {
                $this->logger->error(sprintf('AI API returned invalid value: %s', json_encode($newOpeningHours)));

                continue;
            }

            $id = $newOpeningHours['id'];
            $value = $newOpeningHours['value'];

            if (!is_array($value)) {
                $this->logger->error(sprintf('AI API returned invalid JSON for lounge ID %d', $id));

                continue;
            }

            if (
                !isset($value['monday'])
                || !isset($value['tuesday'])
                || !isset($value['wednesday'])
                || !isset($value['thursday'])
                || !isset($value['friday'])
                || !isset($value['saturday'])
                || !isset($value['sunday'])
            ) {
                $this->logger->error(sprintf('AI API returned invalid JSON for lounge ID %d', $id));

                continue;
            }

            // Ensure that all ranges have 'open' and 'close' keys
            foreach ($value as $day => $ranges) {
                if (!is_string($day) || !is_array($ranges)) {
                    continue 2;
                }

                foreach ($ranges as $i => $range) {
                    if (!is_array($range)) {
                        $this->logger->error(sprintf('AI API returned invalid range for lounge ID %d', $id), [
                            'day' => $day,
                            'range' => $range,
                        ]);

                        continue 3;
                    }

                    if (!isset($range['open'])) {
                        $value[$day][$i]['open'] = '';
                    }

                    if (!isset($range['close'])) {
                        $value[$day][$i]['close'] = '';
                    }
                }
            }

            if (!isset($json[$id])) {
                $this->logger->error(sprintf('AI API returned lounge ID %d which was not requested', $id));

                continue;
            }

            /** @var Lounge $lounge */
            $lounge = $lounges[$id];
            $tz = $timezones[$lounge->getAirportCode()] ?? null;

            if (empty($tz)) {
                $this->logger->error(sprintf('Timezone is missing for airport code %s', $lounge->getAirportCode()));

                continue;
            }

            try {
                // Sort the days of the week in the correct order
                $sorted = [
                    $value['sunday'],
                    $value['monday'],
                    $value['tuesday'],
                    $value['wednesday'],
                    $value['thursday'],
                    $value['friday'],
                    $value['saturday'],
                ];

                $parsedOpeningHours = OpeningHoursParser::parse($sorted);
                $structuredOpeningHours = new StructuredOpeningHours($tz, $parsedOpeningHours);
                $structuredOpeningHours->build();

                $this->logger->info(sprintf('changing opening hours for LoungeID %d', $id), [
                    'old' => $json[$id]['value'],
                    'parsed' => $parsedOpeningHours,
                    'new' => $value,
                    'tz' => $tz,
                ]);
                $lounge->setOpeningHoursAi($structuredOpeningHours);
                $this->em->flush();

                unset($json[$id]);
            } catch (Exception $e) {
                $this->logger->warning(sprintf('Error while building structured opening hours: %s', $e->getMessage()), [
                    'tz' => $tz,
                    'openingHours' => $newOpeningHours,
                ]);
            } catch (InvalidTimezone $e) {
                $this->logger->error(sprintf('Invalid timezone: %s', $e->getMessage()));
            } catch (\InvalidArgumentException $e) {
                $this->logger->error(sprintf('Invalid opening hours data: %s', $e->getMessage()), [
                    'tz' => $tz,
                    'openingHours' => $newOpeningHours,
                ]);
            }
        }

        if (!empty($json)) {
            $this->logger->error(
                sprintf('AI API did not return opening hours for lounges: %s', implode(', ', array_keys($json)))
            );
        }
    }
}
