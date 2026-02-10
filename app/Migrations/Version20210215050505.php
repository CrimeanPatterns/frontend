<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\PriceWithInfo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20210215050505 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        $unbuffConn = $this->container->get('doctrine.dbal.read_replica_unbuffered_connection');
        $serializer = $this->container->get('jms_serializer');

        $foundCounter = 1000;
        $isFounded = true;

        while ($isFounded && $foundCounter > 0) {
            $foundCounter--;
            $foundPricesV1 = $unbuffConn->fetchAll(
                "
                SELECT
                        mv.MileValueID, mv.FoundPrices, mv.DepDate, mv.MileRoute,
                        GROUP_CONCAT(DISTINCT ts.FlightNumber SEPARATOR ';') AS _flightNumbers,
                        GROUP_CONCAT(DISTINCT ts.OperatingAirlineFlightNumber SEPARATOR ';') AS _operatingFlightNumbers
                FROM MileValue mv
                LEFT JOIN TripSegment ts ON (mv.TripID = ts.TripID)
                WHERE
                        FoundPrices IS NOT NULL
                    AND SUBSTR(FoundPrices, 1, 3) = 'V1:'
                GROUP BY mv.TripID, mv.MileValueID
                LIMIT 50000
                "
            );

            $isFounded = count($foundPricesV1) > 0;

            foreach ($foundPricesV1 as $mileValue) {
                $json = substr($mileValue['FoundPrices'], 3);
                /** @var PriceWithInfo[] $priceInfos */
                $priceInfos = $serializer->deserialize($json, 'array<' . PriceWithInfo::class . '>', 'json');

                $foundPricesV2 = CalcMileValueCommand::fetchFoundPrice($mileValue, $priceInfos);
                $serializeFoundPriceV2 = $serializer->serialize($foundPricesV2, 'json');

                $unbuffConn->update('MileValue', ['FoundPrices' => 'V2:' . $serializeFoundPriceV2], ['MileValueID' => $mileValue['MileValueID']]);
            }
        }

        echo 'Converter foundPricesV1 to V2, counter limit: ' . $foundCounter . PHP_EOL;
    }

    public function down(Schema $schema): void
    {
    }
}
