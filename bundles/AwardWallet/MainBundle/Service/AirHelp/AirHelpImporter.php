<?php

namespace AwardWallet\MainBundle\Service\AirHelp;

use AwardWallet\MainBundle\Globals\DateUtils;
use AwardWallet\MainBundle\Service\AirHelp\Model\CsvSource;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture\AirHelpData;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AirHelpImporter
{
    private const BATCH_SIZE = 200;

    private Connection $connection;
    private S3Client $s3Client;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        S3Client $s3Client,
        Logger $logger
    ) {
        $this->connection = $connection;
        $this->s3Client = $s3Client;
        $this->logger = $logger;
    }

    /**
     * @param CsvSource[] $csvSources
     */
    public function saveCsvToDbFromMigration(array $csvSources, AbstractMigration $migration): void
    {
        if (!$csvSources) {
            $this->logger->notice('No CSV sources provided');

            return;
        }

        $this->logger->pushProcessor(function (array $record) use ($migration) {
            $record['context'] = \array_merge(
                $record['context'] ?? [],
                ['migration' => $migration]
            );

            return $record;
        });

        try {
            foreach ($csvSources as $csvSource) {
                $this->logger->notice('Saving ' . $csvSource->getCsv() . ' to ' . $csvSource->getEpoch() . '...');
                $this->saveCsvToDb($csvSource->getCsv(), $csvSource->getEpoch());
            }

            $this->logger->notice('Updating userid...');
            $this->connection->executeUpdate("
                update AirHelpCompensation ahc
                join Trip t on
                    t.TripID = ahc.partner_travel_id
                set
                    ahc.UserID = t.UserID,
                    ahc.UserAgentID = t.UserAgentID
                where ahc.epoch in (?)
            ",
                [
                    it($csvSources)
                    ->map(fn (CsvSource $source) => $source->getEpoch())
                    ->toArray(),
                ],
                [Connection::PARAM_STR_ARRAY]
            );
        } finally {
            $this->logger->popProcessor();
        }
    }

    public function saveCsvToDb(string $fileName, string $epoch): void
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => 'aw-frontend-data',
                'Key' => $fileName,
            ]);
            $exists = true;
        } catch (\Throwable $e) {
            $exists = false;
        }

        if (!$exists) {
            return;
        }

        $data = (string) $this->s3Client->getObject([
            'Bucket' => 'aw-frontend-data',
            'Key' => $fileName,
        ])['Body'];
        $placeHolders = "(" . \substr(\str_repeat('?,', 54), 0, -1) . "),";

        /** @var AirHelpData[] $lines */
        foreach (
            it(explodeLazy("\n", $data))
            ->drop(1) // drop title
            ->filterNotEmptyString()
            ->map(function (string $line) { return new AirHelpData(...\str_getcsv($line)); })
            ->onNthAndLast(self::BATCH_SIZE, function (int $counter, $_, $__, bool $isTotal) use ($epoch) {
                $this->logger->notice(($isTotal ? "Total saved" : "Saved") . " for {$epoch}: {$counter} rows...");
            })
            ->chunk(self::BATCH_SIZE) as $lines
        ) {
            $linesCount = \count($lines);
            $this->connection->executeUpdate("insert into `AirHelpCompensation` (
                `epoch`,
                `partner_travel_id`,
                `airline_iata_code`,
                `flight_number`,
                `flight_date`,
                `flight_start`,
                `flight_end`,
                `segment_start`,
                `segment_end`,
                `flight_scheduled_departure`,
                `flight_actual_departure`,
                `flight_scheduled_arrival`,
                `flight_actual_arrival`,
                `flight_status`,
                `booking_reference`,
                `number_passengers`,
                `ec261_compensation_gross`,
                `ec261_compensation_currency`,
                `quote_compensation_net`,
                `quote_compensation_currency`,
                `url`,
                `email`,
                `first_name`,
                `last_name`,
                `campaign`,
                `locale`,
                `delay_mins`,
                `delay_info`,
                `departure_city`,
                `arrival_city`,
                `departure_date`,
                `departure_time`,
                `arrival_date`,
                `arrival_time`,
                `airline_name`,
                `flight_name`,
                `ahcid`,
                `uuid`,
                `mail_to`,
                `salutation`,
                `unsubscription_url`,
                `segment_departure_city`,
                `segment_arrival_city`,
                `localized_departure_city`,
                `localized_arrival_city`,
                `localized_segment_departure_city`,
                `localized_segment_arrival_city`,
                `segment_airport_start`,
                `segment_airport_end`,
                `flight_airport_start`,
                `flight_airport_end`,
                `ec261_compensation_currency_symbol`,
                `quote_compensation_currency_symbol`,
                `segment_departure_date`
            )
            VALUES " . \substr(\str_repeat($placeHolders, $linesCount), 0, -1) . "
            ",
                it($lines)
                ->flatMap(function (AirHelpData $airHelpData) use ($epoch) {
                    return [
                        $epoch,
                        self::nullable($airHelpData->partner_travel_id),
                        self::nullable($airHelpData->airline_iata_code),
                        self::nullable($airHelpData->flight_number),
                        self::nullable($airHelpData->flight_date),
                        self::nullable($airHelpData->flight_start),
                        self::nullable($airHelpData->flight_end),
                        self::nullable($airHelpData->segment_start),
                        self::nullable($airHelpData->segment_end),
                        self::nullableDate($airHelpData->flight_scheduled_departure),
                        self::nullableDate($airHelpData->flight_actual_departure),
                        self::nullableDate($airHelpData->flight_scheduled_arrival),
                        self::nullableDate($airHelpData->flight_actual_arrival),
                        self::nullable($airHelpData->flight_status),
                        self::nullable($airHelpData->booking_reference),
                        self::nullable($airHelpData->number_passengers),
                        self::nullable($airHelpData->ec261_compensation_gross),
                        self::nullable($airHelpData->ec261_compensation_currency),
                        self::nullable($airHelpData->quote_compensation_net),
                        self::nullable($airHelpData->quote_compensation_currency),
                        self::nullable($airHelpData->url),
                        self::nullable($airHelpData->email),
                        self::nullable($airHelpData->first_name),
                        self::nullable($airHelpData->last_name),
                        self::nullable($airHelpData->campaign),
                        self::nullable($airHelpData->locale),
                        self::nullable($airHelpData->delay_mins),
                        self::nullable($airHelpData->delay_info),
                        self::nullable($airHelpData->departure_city),
                        self::nullable($airHelpData->arrival_city),
                        self::nullable($airHelpData->departure_date),
                        self::nullable($airHelpData->departure_time),
                        self::nullable($airHelpData->arrival_date),
                        self::nullable($airHelpData->arrival_time),
                        self::nullable($airHelpData->airline_name),
                        self::nullable($airHelpData->flight_name),
                        self::nullable($airHelpData->ahcid),
                        self::nullable($airHelpData->uuid),
                        self::nullable($airHelpData->mail_to),
                        self::nullable($airHelpData->salutation),
                        self::nullable($airHelpData->unsubscription_url),
                        self::nullable($airHelpData->segment_departure_city),
                        self::nullable($airHelpData->segment_arrival_city),
                        self::nullable($airHelpData->localized_departure_city),
                        self::nullable($airHelpData->localized_arrival_city),
                        self::nullable($airHelpData->localized_segment_departure_city),
                        self::nullable($airHelpData->localized_segment_arrival_city),
                        self::nullable($airHelpData->segment_airport_start),
                        self::nullable($airHelpData->segment_airport_end),
                        self::nullable($airHelpData->flight_airport_start),
                        self::nullable($airHelpData->flight_airport_end),
                        self::nullable($airHelpData->ec261_compensation_currency_symbol),
                        self::nullable($airHelpData->quote_compensation_currency_symbol),
                        self::nullable($airHelpData->segment_departure_date),
                    ];
                })
                ->toArray()
            );
        }
    }

    private static function nullable(string $value): ?string
    {
        if ('' === $value) {
            return null;
        }

        return $value;
    }

    private static function nullableDate(?\DateTime $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return DateUtils::toSQLDateTime($value);
    }
}
