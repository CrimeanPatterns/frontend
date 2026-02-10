<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\Common\Geo\Geo;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FillHotelsCommand extends Command
{
    public static $defaultName = 'aw:fill-hotels';

    private Connection $connection;

    private GoogleGeo $googleGeo;

    private EntityManagerInterface $entityManager;

    private BrandMatcher $brandMatcher;

    private LoggerInterface $logger;

    private ?OutputInterface $output;

    public function __construct(
        Connection $connection,
        GoogleGeo $googleGeo,
        EntityManagerInterface $entityManager,
        BrandMatcher $brandMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->googleGeo = $googleGeo;
        $this->entityManager = $entityManager;
        $this->brandMatcher = $brandMatcher;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('show', null, InputOption::VALUE_NONE, 'show found hotels')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where')
            ->addOption('mark-mismatched-brands-as-errors', null, InputOption::VALUE_NONE)
            ->addOption('test-duplicate', null, InputOption::VALUE_NONE, 'show deleted duplicates')
            ->addOption('remove-duplicate', null, InputOption::VALUE_NONE, 'remove duplicate')
            ->addOption('merge', null, InputOption::VALUE_NONE, 'merge records')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('test-duplicate')) {
            return $this->fixDuplicate(true, $output);
        }

        if ($input->getOption('remove-duplicate')) {
            return $this->fixDuplicate(false, $output);
        }

        if ($input->getOption('merge')) {
            return $this->mergeHotels($output);
        }

        $this->output = $output;
        $show = $input->getOption('show');

        $output->writeln("aggregating HotelPointValue");

        $sql = "select
            r.ProviderID,
            hpv.HotelPointValueID,
            hpv.BrandID,
            r.HotelName,
            hpv.AlternativeHotelName,
            r.Address,
            gt.Lat,
            gt.Lng,
            gt.CountryCode,
            gt.GeoTagID,
            r.Phone,
            b.Name as BrandName,
            hpv.PointValue as PointValue,
            hpv.AlternativeCost / DATEDIFF(DATE(hpv.CheckOutDate), DATE(hpv.CheckInDate)) / case when hpv.RoomCount = 0 then 1 else hpv.RoomCount end as CashPrice,
            hpv.TotalPointsSpent / DATEDIFF(DATE(hpv.CheckOutDate), DATE(hpv.CheckInDate)) / case when hpv.RoomCount = 0 then 1 else hpv.RoomCount end as PointPrice
        from
            Reservation r
            join Provider p on r.ProviderID = p.ProviderID
            join GeoTag gt on r.GeoTagID = gt.GeoTagID
            join HotelPointValue hpv on r.ReservationID = hpv.ReservationID
            join HotelBrand b on hpv.BrandID = b.HotelBrandID
        where
            hpv.Status not in (:statuses)";

        if ($where = $input->getOption('where')) {
            $sql .= " and $where";
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit $limit";
        }

        $existingHotelClusters = $this->loadExistingHotels();
        $updated = 0;
        $updatedDuplicate = 0;
        $inserted = 0;

        $output->writeln($sql);
        $q = $this->connection->executeQuery($sql, ["statuses" => CalcMileValueCommand::EXCLUDED_STATUSES],
            ["statuses" => Connection::PARAM_STR_ARRAY]);
        $output->writeln("query opened");
        $rows = $q->fetchAll(FetchMode::ASSOCIATIVE);

        $processedHotelIds = it($rows)
            ->onNthMillis(30000, function ($time, $ticksCounter, $value, $key) use ($output) {
                $output->writeln("processed " . number_format($ticksCounter,
                    0) . " records in " . number_format($time / 1000, 0) . " seconds..");
            })
            ->filter(function (array $row) use ($output, $input) {
                $brand = $this->brandMatcher->match($row['AlternativeHotelName'], $row['ProviderID']);

                if ($brand !== null && $row['BrandID'] != $brand->getId()) {
                    $output->writeln("brand mismatch for {$row['HotelPointValueID']}, hotel: {$row['HotelName']} ({$row['BrandName']}), alt hotel: {$row['AlternativeHotelName']} ({$brand->getName()})");

                    if ($input->getOption('mark-mismatched-brands-as-errors')) {
                        $output->writeln("marking as error");
                        $this->connection->executeStatement(
                            "update HotelPointValue 
                            set Status = :status, Note = :note
                            where HotelPointValueID = :id",
                            [
                                "status" => CalcMileValueCommand::STATUS_ERROR,
                                "note" => 'brands mismatch in hotels',
                                "id" => $row['HotelPointValueID'],
                            ]
                        );
                    }

                    return false;
                }

                return true;
            })
            ->reindex(function (array $row) {
                return $row['ProviderID'] . ":" . $row['BrandID'] . ':' . $row['AlternativeHotelName'];
            })
            ->collapseByKey()
            ->ksort()
            ->flatMapIndexed(function (array $rows, string $key) use ($output, $show) {
                $hotels = [];
                [$providerId, $hotelBrandId, $name] = explode(":", $key);
                it($rows)
                    ->onNthMillis(15000, function ($time, $ticksCounter, $value, $key) use ($output) {
                        $output->writeln("processed " . number_format($ticksCounter,
                            0) . " hotels in " . number_format($time / 1000, 0) . " seconds..");
                    })
                    ->apply(function (array $candidate) use (&$hotels, $providerId, $hotelBrandId, $name) {
                        $nearest = $this->findNearest($hotels, $candidate);

                        if ($nearest === null) {
                            $nearest = [
                                'Matches' => [],
                                'Index' => count($hotels),
                                'ProviderID' => $providerId,
                                'HotelBrandID' => $hotelBrandId,
                                'Name' => $name,
                            ];
                            $hotels[] = $nearest;
                        }

                        $nearest['Matches'][] = $candidate;
                        $nearest['Lat'] = round($this->averageColumn($nearest['Matches'], 'Lat'), 4);
                        $nearest['Lng'] = round($this->averageColumn($nearest['Matches'], 'Lng'), 4);

                        $hotels[$nearest['Index']] = $nearest;
                    })
                ;

                if ($show) {
                    $output->writeln($key . " -> " . count($hotels));
                }

                return $hotels;
            })
            ->map(function (array $hotel) use ($show, $output) {
                unset($hotel['Index']);
                $hotel['Phones'] = $this->formatPhones($hotel['Matches']);
                $hotel['Address'] = $hotel['Matches'][0]['Address'];
                $hotel['GeoTagID'] = $hotel['Matches'][0]['GeoTagID'];
                // TODO: Category - grab from API when creating record in HotelPointValue ?
                $hotel['PointValue'] = round($this->averageColumn($hotel['Matches'], 'PointValue'), 2);
                $hotel['CashPrice'] = round($this->averageColumn($hotel['Matches'], 'CashPrice'), 2);
                $hotel['PointPrice'] = round($this->averageColumn($hotel['Matches'], 'PointPrice'), 2);
                $hotel['MatchCount'] = count($hotel['Matches']);
                $hotel['Matches'] = json_encode($hotel['Matches']);

                if ($show) {
                    $output->writeln(json_encode($hotel, JSON_PRETTY_PRINT));
                }

                return $hotel;
            })
            ->map(function (array $hotel) use ($existingHotelClusters, &$updated, &$inserted, &$updatedDuplicate) {
                $key = $this->getHotelClusterKey($hotel);
                $existing = $this->findNearest($existingHotelClusters[$key] ?? [], $hotel);

                $alternativeKeyProvider = 'alternative:' . $hotel['ProviderID'];

                if (null === $existing && array_key_exists($alternativeKeyProvider, $existingHotelClusters)) {
                    $existing = $this->findNearest($existingHotelClusters[$alternativeKeyProvider], $hotel);

                    if (!empty($existing)) {
                        $existing['isMergedDiffBrand'] = true;
                    }
                }

                unset($hotel['Lat'], $hotel['Lng']);

                $needInsert = false;

                if ($existing !== null) {
                    if (array_key_exists('isMerged', $existing) && true === $existing['isMerged']) {
                        $altData = json_decode($existing['AlternativeData'], true);
                        $pos = array_search($hotel['Name'], array_column($altData, 'Name'));

                        if (false !== $pos) {
                            $altData[$pos]['Matches'] = $hotel['Matches'];

                            $this->logger->info('FillHotelsCommand: ReplaceMatches', ['old' => $altData[$pos]['Matches'], 'new' => $hotel['Matches']]);
                            $this->connection->update('Hotel',
                                [
                                    'AlternativeData' => json_encode($altData),
                                    'UpdateDate' => date("Y-m-d H:i:s"),
                                ],
                                ["HotelID" => $existing["HotelID"]]
                            );
                            $updatedDuplicate++;
                        } else {
                            $needInsert = true;
                            $this->logger->info('FillHotelsCommand: AlternativeData NotFoundName (need insert)', ['hotel' => $hotel, 'existing' => $existing]);
                            // throw new \Exception('Alternative name not found, existing: ' . $existing['HotelID'] . ', found: ' . $hotel['Name']);
                        }
                    } else {
                        $this->connection->update("Hotel", array_merge($hotel, ["UpdateDate" => date("Y-m-d H:i:s")]), ["HotelID" => $existing["HotelID"]]);
                    }

                    if (!$needInsert) {
                        $updated++;

                        return $existing['HotelID'];
                    }
                }

                $this->connection->insert("Hotel", $hotel);
                $inserted++;

                return $this->connection->lastInsertId();
            })
            ->toArray()
        ;

        $output->writeln("updated $updated hotels (duplicates $updatedDuplicate), inserted $inserted hotels");

        $existingHotelIds = it($existingHotelClusters)
            ->flatMap(function (array $hotels) {
                return $hotels;
            })
            ->map(function (array $hotel) {
                return $hotel['HotelID'];
            })
            ->toArray()
        ;

        $toDelete = array_diff($existingHotelIds, $processedHotelIds);

        if (count($toDelete) > 0 && (count($toDelete) / count($existingHotelIds)) > 0.1) {
            throw new \Exception("Too many hotels to delete. We have loaded " . count($existingHotelIds) . " hotels, and want to delete " . count($toDelete));
        }

        $deleted = it($toDelete)
            ->map(function (int $hotelId) {
                $this->connection->delete("Hotel", ["HotelID" => $hotelId]);

                return true;
            })
            ->count()
        ;

        $output->writeln("done, loaded: " . count($existingHotelIds) . ", updated: {$updated}, inserted: {$inserted}, deleted: {$deleted}");
    }

    public function fixAddress(OutputInterface $output)
    {
        $wrongAddress = [
            217 => [
                'oldAddress' => '360 Kodaiji Masuyacho, Higashiyama-ku, Kyoto-shi Kyoto, 605-0826 Japan',
                'newAddress' => '1-13-11 Nanko-Kita, Suminoe-Ku Osaka, Japan, 559-0034',
            ],

            1187 => [
                'oldAddress' => 'NA Taiwan',
                'newAddress' => 'No. 69, Zhongxing Rd., West District , Chiayi County, Taiwan',
                // 'geoTagId' => 34232202,
            ],
            2434 => [
                'oldAddress' => 'NA Taiwan',
                'newAddress' => 'No. 4 Zhongshan 1st Road, Xinxing District, Kaohsiung, Taiwan',
                // 'geoTagId' => 34232202,
            ],

            1732 => [
                'oldAddress' => 'OrlandoFL 32821 United States',
                'newAddress' => '12005 Regency Village Drive, Orlando, Florida, 32821, United States',
                // 'geoTagId' => 34425283,
            ],
            11438 => [
                'oldAddress' => 'OrlandoFL 32821 United States',
                'newAddress' => '6985 Sea Harbor Drive Orlando, Florida, 32821, United States',
                // 'geoTagId' => 34425283,
            ],
        ];

        $output->writeln('----');

        foreach ($wrongAddress as $hotelId => $data) {
            $this->connection->executeQuery('
                UPDATE Hotel
                SET Address = REPLACE(Address, :old, :new),
                    Matches = REPLACE(Matches, :old, :new)
                WHERE HotelID = :id
            ', [
                'old' => $data['oldAddress'],
                'new' => $data['newAddress'],
                'id' => $hotelId,
            ], [
                'old' => \PDO::PARAM_STR,
                'new' => \PDO::PARAM_STR,
                'id' => \PDO::PARAM_INT,
            ]);

            $this->logger->info('FillHotelsCommand: updated address', [
                'HotelID' => $hotelId,
                'oldAddress' => $data['oldAddress'],
                'newAddress' => $data['newAddress'],
            ]);
        }
    }

    private function findNearest(array $hotels, array $candidate): ?array
    {
        $result = it($hotels)
            ->map(function (array $hotel) use ($candidate) {
                $hotel['Distance'] = Geo::distance($candidate['Lat'], $candidate['Lng'], $hotel['Lat'],
                    $hotel['Lng']);

                return $hotel;
            })
            ->filter(function (array $hotel) {
                return $hotel['Distance'] < 5;
            })
            ->usort(function (array $a, array $b) {
                return $a['Distance'] <=> $b['Distance'];
            })
            ->first();

        if ($result !== null) {
            unset($result['Distance']);
        }

        return $result;
    }

    private function averageColumn(array $matches, string $column): float
    {
        return it($matches)
            ->map(function (array $row) use ($column) {
                return $row[$column];
            })
            ->average();
    }

    private function loadExistingHotels(): array
    {
        $rows = $this->connection->executeQuery(
            "select 
                h.HotelID, 
                h.ProviderID, 
                h.HotelBrandID, 
                h.Name, 
                h.AlternativeData,
                gt.Lat, 
                gt.Lng 
            from 
                Hotel h 
                join GeoTag gt on h.GeoTagID = gt.GeoTagID"
        )->fetchAll(FetchMode::ASSOCIATIVE);

        $alternativeCluster = [];

        foreach ($rows as $row) {
            if (empty($row['AlternativeData'])) {
                continue;
            }

            $data = json_decode($row['AlternativeData'], true);

            foreach ($data as $hotel) {
                $altHotel = array_merge($row, [
                    'Name' => $hotel['Name'],
                    'isMerged' => true,
                ]);
                $rows[] = $altHotel;
                $alternativeCluster['alternative:' . $row['ProviderID']][] = $altHotel;
            }
        }

        $indexed = it($rows)
            ->reindex(function (array $row) {
                return $this->getHotelClusterKey($row);
            })
            ->collapseByKey()
            ->toArrayWithKeys()
        ;
        $indexed = array_merge($indexed, $alternativeCluster);

        return $indexed;
    }

    private function getHotelClusterKey(array $hotel): string
    {
        return implode(':', [
            $hotel['ProviderID'],
            $hotel['HotelBrandID'],
            $hotel['Name'],
        ]);
    }

    private function formatPhones(array $matches): string
    {
        return it($matches)
            ->flatMap(function (array $match) {
                $phones = preg_split('#(,|;| or )#ims', $match['Phone']);
                $countryCode = $match['CountryCode'];

                return it($phones)
                    ->flatMap(function (string $phone) use ($countryCode) {
                        $phone = trim($phone);

                        if ($phone === "") {
                            return [];
                        }

                        static $phoneNumberUtil;

                        if ($phoneNumberUtil === null) {
                            $phoneNumberUtil = PhoneNumberUtil::getInstance();
                        }

                        try {
                            $phoneObject = $phoneNumberUtil->parse($phone, $countryCode);
                        } catch (NumberParseException $exception) {
                            return [$phone];
                        }

                        return [$phoneNumberUtil->format($phoneObject, PhoneNumberFormat::INTERNATIONAL)];
                    })
                    ->toArray()
                ;
            })
            ->unique()
            ->joinToString(", ");
    }

    private function fixDuplicate(bool $isTest, OutputInterface $output): void
    {
        $duplicatedHotels = $this->connection->fetchAllAssociative('
            SELECT h.HotelID, h.Name, h.Address, h.Phones, h.Website, h.Matches,
                   p.DisplayName,
                   hb.Name AS BrandName
            FROM Hotel h
            LEFT JOIN Provider p ON p.ProviderID = h.ProviderID
            LEFT JOIN HotelBrand hb ON hb.HotelBrandID = h.HotelBrandID
            WHERE h.Address IN (
                SELECT Address
                FROM Hotel
                GROUP BY Address HAVING COUNT(*) > 1
             )
            ORDER BY h.Address ASC, HotelID ASC
        ');

        $grouped = [];

        foreach ($duplicatedHotels as $hotel) {
            $addr = $hotel['Address'];
            $grouped[$addr][] = $hotel;
        }
        $output->writeln(count($grouped) . '/' . count($duplicatedHotels) . ' records found with the same address');

        foreach ($grouped as $addr => $hotels) {
            $provider = $hotels[0]['DisplayName'];
            $brandName = $hotels[0]['BrandName'];

            foreach ($hotels as $key => $hotel) {
                if ($hotel['DisplayName'] !== $provider
                    || $hotel['BrandName'] !== $brandName) {
                    unset($grouped[$addr][$key]);
                }
            }
        }

        foreach ($grouped as $addr => $hotels) {
            if (count($hotels) < 2) {
                continue;
            }

            $mainHotel = $hotels[0];
            $alternativeData = [];
            $updateData = [];
            $removeIds = [];

            for ($i = 1; $i < count($hotels); $i++) {
                $hotel = $hotels[$i];

                if (empty($mainHotel['Phones']) && !empty($hotel['Phones'])) {
                    $updateData['Phones'] = $hotel['Phones'];
                }

                if (empty($mainHotel['Website']) && !empty($hotel['Website'])) {
                    $updateData['Website'] = $hotel['Website'];
                }
                $removeIds[] = $hotel['HotelID'];
                $alternativeData[] = [
                    'Name' => $hotel['Name'],
                    'Matches' => $hotel['Matches'],
                ];
            }

            if (!empty($alternativeData)) {
                $hotelId = (int) $mainHotel['HotelID'];
                $updateData['AlternativeData'] = json_encode($alternativeData);

                if ($isTest) {
                    $output->writeln('Update MAIN Hotel [id = ' . $hotelId . '], alternativeData: '
                        . PHP_EOL . ' - '
                        . implode(PHP_EOL . ' - ', array_column($alternativeData, 'Name'))
                    );
                } else {
                    $this->connection->update('Hotel', $updateData, ['HotelID' => $hotelId]);
                }

                if ($isTest) {
                    $output->write('TEST Removed list ');
                } else {
                    $this->logger->info('Hotel merge duplicate hotelId: ' . $hotelId, [
                        'RemoveHotelId' => $removeIds,
                    ]);
                    $this->connection->executeQuery('
                        DELETE FROM Hotel WHERE HotelID IN (' . implode(',', $removeIds) . ')
                    ');
                }

                $output->writeln('Main hotelId = ' . $hotelId . ', removed hotelIds: ['
                    . implode(',', $removeIds)
                    . ']');
                $output->writeln('');
            }
        }

        $output->writeln('done.');
    }

    private function mergeHotels(OutputInterface $output): void
    {
        // sourceId => destinationId
        $merge = [
            /* #1
            6760 => 3403,
            4970 => 4600,
            15196 => 13204,
            15215 => 951,
            2868 => 15111,
            15182 => 1010,
            15183 => 2306,
            */

            // 2
            15394 => 4213,
            15454 => 5934,
            15589 => 6015,
            15407 => 11716,
            14827 => 1250,
            12525 => 15338,
            15456 => 10331,
            15476 => 12056,
            15570 => 2078,
            15439 => 816,
            15574 => 13399,
            15477 => 6902,
            15492 => 1854,
            15339 => 9509,
        ];

        $mergeIds = array_merge(array_keys($merge), $merge);
        $mergeData = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    HotelID, Name, Matches, AlternativeData
            FROM Hotel
            WHERE
                    HotelID IN (' . implode(',', $mergeIds) . ')
        ');
        $mergeData = array_column($mergeData, null, 'HotelID');

        foreach ($mergeData as $hotelId => $hotel) {
            if (!array_key_exists($hotelId, $merge)) {
                continue;
            }
            $destinationId = $merge[$hotelId];

            if (empty($mergeData[$destinationId])) {
                continue;
            }

            $sourceData = $hotel;
            $destData = $mergeData[$destinationId];

            $alternativeData = (array) json_decode($destData['AlternativeData'], true);

            if (false !== array_search(
                $sourceData['Name'],
                array_column($alternativeData, 'Name'))) {
                continue;
            }

            $alternativeData[] = [
                'Name' => $sourceData['Name'],
                'AlternativeData' => $sourceData['Matches'],
            ];

            $this->connection->update('Hotel',
                ['AlternativeData' => json_encode($alternativeData)],
                ['HotelID' => $destData['HotelID']]
            );
            $output->writeln('Set alternativeData to ' . $destinationId . ' from ' . $hotelId);

            if ($this->connection->delete('Hotel', ['HotelID' => $hotelId])) {
                $this->logger->info(
                    'FillHotelsCommand: merge and delete duplicated Hotel: ' . $hotel['Name'],
                    ['HotelID' => $hotelId]
                );
            }
        }

        $this->fixAddress($output);

        $output->writeln('done');
    }

    private function findWrongAddress(OutputInterface $output): void
    {
        $result = $this->connection->fetchAllAssociative("
            select
                r.ReservationID,
                r.ProviderID,
                hpv.HotelPointValueID,
                hpv.BrandID,
                r.HotelName,
                hpv.AlternativeHotelName,
                r.Address,
                gt.CountryCode,
                gt.GeoTagID,
                b.Name as BrandName
            from
                Reservation r
                join Provider p on r.ProviderID = p.ProviderID
                join GeoTag gt on r.GeoTagID = gt.GeoTagID
                join HotelPointValue hpv on r.ReservationID = hpv.ReservationID
                join HotelBrand b on hpv.BrandID = b.HotelBrandID
            where
                   r.Address LIKE 'NA Taiwan'
                OR r.Address LIKE 'OrlandoFL 32821 United States'
            limit 50
        ");

        if (empty($result)) {
            $output->writeln('findReservationAddress: not found');

            return;
        }

        $output->writeln('findReservationAddress: ' . var_export($result, true));
    }
}
