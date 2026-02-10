<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Globals\DateUtils;
use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture\AirHelpData;
use Aws\S3\S3Client;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210416162115 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 200;

    public function getDescription(): string
    {
        return 'brazil 2021-04-07 batch';
    }

    public function up(Schema $schema): void
    {
        $this->saveCsvToDb('awardwallet-brazil-initial_campaign-2021-04-07.csv', 'br_04_07');
        $this->addSql("
            update AirHelpCompensation ahc
            join Trip t on
                t.TripID = ahc.partner_travel_id
            set
                ahc.UserID = t.UserID,
                ahc.UserAgentID = t.UserAgentID
            where ahc.epoch = 'br_04_07'
        ");
    }

    public function saveCsvToDb(string $fileName, string $epoch)
    {
        $s3Client = $this->container->get(S3Client::class);

        try {
            $s3Client->headObject([
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

        $data = (string) $s3Client->getObject([
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
                $this->write(($isTotal ? "Total saved" : "Saved") . " for {$epoch}: {$counter} rows...");
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

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
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
