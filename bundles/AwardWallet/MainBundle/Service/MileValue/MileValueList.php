<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\Airport\AirportTime;
use AwardWallet\Common\Entity\Aircode;
use AwardWallet\Common\Geo\Geo;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MileValueList extends \TBaseList
{
    private RouterInterface $router;

    private Connection $connection;

    private AirportTime $airportTime;

    private SerializerInterface $serializer;

    private AirlineRepository $airlineRepository;

    private EntityRepository $aircodeRepository;

    public function __construct(
        string $table,
        array $fields,
        RouterInterface $router,
        Connection $connection,
        AirportTime $airportTime,
        SerializerInterface $serializer,
        AirlineRepository $airlineRepository,
        EntityRepository $aircodeRepository
    ) {
        parent::__construct($table, $fields);
        unset($fields['SourceCheck']);

        $this->SQL = "select
            " . implode(", ",
            array_map(function (string $field) {
                return "MileValue.{$field}";
            },
                array_filter(array_keys($fields), function ($field) {
                    return !in_array($field, ['RecordLocator', 'UserID', 'LastParseDate', 'PMVStatus']);
                })
            )
        ) . ",
            MileValue.AlternativeBookingURL,
            MileValue.CustomTravelersCount,
            MileValue.CustomTotalMilesSpent,
            MileValue.CustomTotalTaxesSpent,
            MileValue.CustomAlternativeCost,
            Trip.LastParseDate,
            Trip.RecordLocator,
            Trip.UserID,
            Trip.CreateDate,
            Trip.FirstSeenDate,
            Trip.ReservationDate,
            coalesce(pmv.Status, 0) as PMVStatus
        from
            MileValue
            left join Trip on MileValue.TripID = Trip.TripID
            left join ProviderMileValue pmv on Trip.ProviderID = pmv.ProviderID and pmv.EndDate is null
        ";

        $this->router = $router;
        $this->connection = $connection;
        $this->airportTime = $airportTime;
        $this->serializer = $serializer;
        $this->airlineRepository = $airlineRepository;
        $this->aircodeRepository = $aircodeRepository;
    }

    public static function getRowColorByStatus(string $rowColor, string $status): string
    {
        switch ($status) {
            case CalcMileValueCommand::STATUS_GOOD:
                $rowColor = '#CFFAFF';

                break;

            case CalcMileValueCommand::STATUS_REVIEW:
                $rowColor = '#FFFD98';

                break;

            case CalcMileValueCommand::STATUS_AUTO_REVIEW:
                $rowColor = '#E8E190';

                break;

            case CalcMileValueCommand::STATUS_ERROR:
                $rowColor = '#FFCDCA';

                break;
        }

        return $rowColor;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if (!empty($this->Query->Fields["TripID"]) && $output === "html") {
            if (!empty($this->Query->Fields['UserID'])) {
                $this->Query->Fields["UpdateDate"] .= " Parsed: " . $this->Query->Fields["LastParseDate"];
                $targetUrl = $this->router->generate("aw_timeline_html5_itineraries",
                    ["itIds" => 'T.' . $this->Query->Fields["TripID"]]);
                $link = $this->router->generate("aw_manager_impersonate",
                    ["UserID" => $this->Query->Fields['UserID'], "Full" => 1, "Goto" => $targetUrl]);
                $this->Query->Fields["TripID"] = "<a target='_blank' href='{$link}'>{$this->Query->Fields["TripID"]}</a>";
            }
        }

        $this->Query->Fields["MileRoute"] = $this->formatRoute($this->Query->Fields["MileRoute"]);
        $this->Query->Fields["CashRoute"] = $this->formatRoute($this->Query->Fields["CashRoute"]);

        if (!empty($this->Query->Fields["History"]) && $output === "html") {
            $history = json_decode($this->Query->Fields["History"], true);
            $history = array_reverse($history);

            foreach ($history as $oldValues) {
                foreach ($this->Query->Fields as $field => &$value) {
                    if (isset($oldValues[$field])) {
                        $value = "<div style='text-decoration: line-through;'>" . $oldValues[$field] . "</div>" . $value;
                    }
                }
            }
        }

        if ($this->OriginalFields["MilesSource"] === Constants::MILE_SOURCE_ACCOUNT_HISTORY && isset($this->OriginalFields['TripID'])) {
            $this->Query->Fields["MilesSource"] = "<a href='#' onclick=\"$(this).parent().find('.details').show(); return false;\">{$this->Query->Fields["MilesSource"]}</a>
            <div class='details' style='display: none;'>" . $this->getHistoryRows($this->OriginalFields['TripID']) . "</div> 
            ";
        }

        if ($this->OriginalFields['AlternativeBookingURL'] !== null && $output === "html") {
            $this->Query->Fields['CashAirlines'] = "<a href=\"{$this->OriginalFields["AlternativeBookingURL"]}\" target='_blank'>{$this->Query->Fields['CashAirlines']}</a>";
        }

        if ($output === "html") {
            $this->Query->Fields['CashAirlines'] .= "<br/><a target=\"_blank\" href=\"" . $this->router->generate("aw_manager_milevalue_prices",
                ["tripId" => $this->OriginalFields['TripID'] ?? 0]) . "\">View all prices</a>";
        }

        if ($output === "html") {
            foreach (Constants::CUSTOM_FIELDS as $field) {
                if ($this->Query->Fields['Custom' . $field]) {
                    $this->Query->Fields[$field] .= "<div>(manual {$this->Query->Fields['Custom' . $field]})</div>";
                }
            }
        }

        $sources = $this->connection->fetchFirstColumn('SELECT Sources FROM TripSegment WHERE TripID = ' . (int) $this->OriginalFields['TripID']);
        $this->Query->Fields['SourceCheck'] = SourceLinksFormatter::formatSources($sources);
    }

    public function DrawButtonsInternal()
    {
        $triggers = parent::DrawButtonsInternal();

        echo "<input id=\"ExportSegmentsId\" class='button' type=button value=\"Export Segments\"  onclick=\"this.form.action.value = 'exportSegments'; form.submit();\"> ";
        $triggers[] = ['ExportSegmentsId', 'Export Segments'];

        return $triggers;
    }

    public function ProcessAction($action, $ids)
    {
        if ($action === 'exportSegments') {
            $this->Schema->ShowMethod = "exportSegments";
        }

        return parent::ProcessAction($action, $ids);
    }

    public function exportSegments()
    {
        ob_clean();
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=mile-value-segments.csv");
        $this->ExportCSVRow([
            "segment_id",
            "mv_id",
            "segment_type",
            "api_source",
            "1",
            "2",
            "airline",
            "flight_no",
            "operating_carrier",
            "operating_flight_no",
            "fare_class",
            "fare_basis",
            "txt_parsed_cabin",
            "txt_matched_cabin",
            "fare_class_matched_cabin",
            "search_cabin",
            "1_type",
            "2_type",
            "open_jaw",
            "stopover",
            "layover",
            "duration",
            "distance",
            "leg_group",
            "segment_order",
            "departure_date",
            "alt_cost_date",
            "ticketed_date",
            "first_detected_date",
            "days_before_departure",
            "1_airline_regions",
            "2_airline_regions",
            "1_other_regions",
            //            "1_other_regions_debug",
            "2_other_regions",
        ], ",");
        $this->UsePages = false;
        $this->InplaceEdit = false;
        $this->OpenQuery();

        while (!$this->Query->EOF) {
            if ($this->Query->Fields['TripID'] !== null) {
                $this->exportAwardSegments($this->Query->Fields['TripID']);
            }

            if ($this->Query->Fields['HavePrices']) {
                $this->exportCashSegments($this->Query->Fields['MileValueID']);
            }
            $this->Query->Next();
        }

        exit;
    }

    protected function getRowColor(): string
    {
        return self::getRowColorByStatus(parent::getRowColor(), $this->OriginalFields["Status"]);
    }

    private function formatRoute(string $route): string
    {
        $parts = explode(",", $route);
        $result = "";
        $needLineBreak = false;

        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $stopover = in_array(substr($part, 0, 3), ["rt:", "so:"]);

                if ($stopover) {
                    $part = "<b>$part</b>";
                }

                if ($stopover || $needLineBreak) {
                    $result .= "<br/>";
                } else {
                    $result .= ", ";
                }
                $needLineBreak = $stopover;
            }
            $result .= $part;
        }

        return $result;
    }

    private function getHistoryRows(int $tripId): string
    {
        /** @var Connection $connection */
        $rows = $this->connection->executeQuery("select 
            ah.*
        from
            AccountHistory ah
            join HistoryToTripLink HTTL on ah.UUID = HTTL.HistoryID
        where 
            HTTL.TripID = ?", [$tripId])->fetchAll(FetchMode::ASSOCIATIVE);

        if (count($rows) === 0) {
            return "No data";
        }

        $style = "style='white-space: nowrap; border: 1px solid gray; padding: 2px;'";

        return
            "<table>"
            .
            it($rows)
                ->map(function (array $row) use ($style) {
                    $row = array_intersect_key($row, ["Description" => false, "Miles" => false]);

                    return "<tr><td {$style}>" . implode("</td><td {$style}>",
                        array_map("htmlspecialchars", $row)) . "</td></tr>";
                })
                ->joinToString()
            .
            "</table>";
    }

    private function exportAwardSegments(int $tripId)
    {
        /** @var Statement $q */
        static $q;

        if ($q === null) {
            $q = $this->connection->prepare("
            select 
                ts.*,
                ma.Code as MACode,
                oa.Code as OACode,
                dgt.TimeZoneLocation as DepTimeZone,
                agt.TimeZoneLocation as ArrTimeZone,
                dgt.Lat as DepLat,
                dgt.Lng as DepLng,   
                agt.Lat as ArrLat,
                agt.Lng as ArrLng                      
            from 
                TripSegment ts 
                left join Airline ma on ts.AirlineID = ma.AirlineID
                left join Airline oa on ts.OperatingAirlineID = oa.AirlineID
                left join GeoTag dgt on ts.DepGeoTagID = dgt.GeoTagID
                left join GeoTag agt on ts.ArrGeoTagID = agt.GeoTagID
            where 
                ts.TripID = ? order by ts.DepDate");
        }

        $q->execute([$tripId]);
        $rows = $q->fetchAll(FetchMode::ASSOCIATIVE);

        $rows = array_map(function (array $row) {
            $row['DepTimestamp'] = (new \DateTime($row['DepDate'], new \DateTimeZone($row['DepTimeZone'])))->getTimestamp();
            $row['ArrTimestamp'] = (new \DateTime($row['ArrDate'], new \DateTimeZone($row['ArrTimeZone'])))->getTimestamp();

            return $row;
        }, $rows);

        $first = true;
        $legGroup = 0;

        foreach ($rows as $index => $row) {
            $last = $index === (count($rows) - 1);

            $connectType = "start";

            if (!$first) {
                if (($row['DepTimestamp'] - $rows[$index - 1]['ArrTimestamp']) < 86400) {
                    $connectType = "connect";
                } else {
                    $connectType = "resume";
                    $legGroup++;
                }
            }

            if ($last) {
                $openJaw = $row['ArrCode'] !== $rows[0]['DepCode'] && $legGroup > 0;
            } else {
                $openJaw = $row["ArrCode"] !== $rows[$index + 1]["DepCode"];
            }

            $secondType = $last ? "end" : (($rows[$index + 1]['DepTimestamp'] - $row['ArrTimestamp']) < 86400 ? "connect" : "stop");

            $arValues = [
                "segment_id" => $row["TripSegmentID"],
                "mv_id" => $this->Query->Fields["MileValueID"],
                "segment_type" => "award",
                "api_source" => "",
                "1" => $row["DepCode"],
                "2" => $row["ArrCode"],
                "airline" => $row['MACode'],
                "flight_no" => $row['FlightNumber'],
                "operating_carrier" => $row['OACode'],
                "operating_flight_no" => $row['OperatingAirlineFlightNumber'],
                "fare_class" => $row['BookingClass'],
                "fare_basis" => '',
                "txt_parsed_cabin" => $row['CabinClass'] ?? $this->Query->Fields['CabinClass'],
                "txt_matched_cabin" => $this->Query->Fields['ClassOfService'],
                "fare_class_matched_cabin" => '',
                "search_cabin" => $this->Query->Fields['ClassOfService'],
                "1_type" => $connectType,
                "2_type" => $secondType,
                "open_jaw" => $openJaw ? "true" : "false",
                "stopover" => $secondType !== "stop" ? "" : round(($rows[$index + 1]["DepTimestamp"] - $row["ArrTimestamp"]) / 86400, 1),
                "layover" => $secondType !== "connect" ? "" : date("H:i", $rows[$index + 1]["ArrTimestamp"] - $row["DepTimestamp"]),
                "duration" => date("H:i", $row["ArrTimestamp"] - $row["DepTimestamp"]),
                "distance" => round(Geo::distance($row['DepLat'], $row['DepLng'], $row['ArrLat'], $row['ArrLng'])),
                "leg_group" => chr(ord('A') + $legGroup),
                "segment_order" => $index + 1,
                "departure_date" => $connectType === "start" || $connectType === "resume" ? date("m/d/Y", strtotime($row['DepDate'])) : "",
                "alt_cost_date" => '',
                "ticketed_date" => !empty($this->Query->Fields['ReservationDate']) ? date("m/d/Y", strtotime($this->Query->Fields['ReservationDate'])) : '',
                "first_detected_date" => date("m/d/Y", strtotime($this->Query->Fields['FirstSeenDate'])),
                "days_before_departure" => round(($row['DepTimestamp'] - strtotime(!empty($this->Query->Fields['ReservationDate']) ? $this->Query->Fields['ReservationDate'] : $this->Query->Fields['FirstSeenDate'])) / 86400),
            ];
            $arValues = array_merge($arValues, $this->addRegions($arValues["1"], $arValues["2"]));
            $this->ExportCSVRow($arValues, ",");
            $first = false;
        }
    }

    private function exportCashSegments(int $mileValueId)
    {
        /** @var Statement $q */
        static $q;

        if ($q === null) {
            $q = $this->connection->prepare("select FoundPrices from MileValue where MileValueID = ?");
        }

        $q->execute([$mileValueId]);
        $foundPrices = $q->fetchColumn();

        if (substr($foundPrices, 0, '3') !== 'V2:') {
            return;
        }

        $json = substr($foundPrices, 3);

        /** @var FoundPrices $foundPrice */
        $foundPrice = $this->serializer->deserialize($json, FoundPrices::class, 'json');
        $priceInfo = reset($foundPrice->priceInfos);

        $legGroup = 0;
        $first = true;
        $rows = $priceInfo->price->routes;

        foreach ($rows as $index => $row) {
            $last = $index === (count($rows) - 1);

            $connectType = "start";

            if (!$first) {
                if (($this->airportTime->convertToGmt($row->depDate, $row->depCode) - $this->airportTime->convertToGmt($rows[$index - 1]->arrDate, $rows[$index - 1]->arrCode)) < 86400) {
                    $connectType = "connect";
                } else {
                    $connectType = "resume";
                    $legGroup++;
                }
            }

            if ($last) {
                $openJaw = $row->arrCode !== $rows[0]->depCode && $legGroup > 0;
            } else {
                $openJaw = $row->arrCode !== $rows[$index + 1]->depCode;
            }

            /** @var Aircode $depAirport */
            $depAirport = $this->aircodeRepository->findOneBy(['aircode' => $row->depCode]);
            /** @var Aircode $arrAirport */
            $arrAirport = $this->aircodeRepository->findOneBy(['aircode' => $row->arrCode]);

            $secondType = $last ? "end" : (($this->airportTime->convertToGmt($rows[$index + 1]->depDate, $rows[$index + 1]->depCode) - $this->airportTime->convertToGmt($row->arrDate, $row->arrCode)) < 86400 ? "connect" : "stop");

            $arValues = [
                "segment_id" => "",
                "mv_id" => $this->Query->Fields["MileValueID"],
                "segment_type" => "cash",
                "api_source" => $priceInfo->price->source,
                "1" => $row->depCode,
                "2" => $row->arrCode,
                "airline" => $row->airline,
                "flight_no" => $row->flightNumber,
                "operating_carrier" => $row->operatingAirline,
                "operating_flight_no" => $row->operatingFlightNumber,
                "fare_class" => $row->fareClass,
                "fare_basis" => $row->fareBasis,
                "txt_parsed_cabin" => '',
                "txt_matched_cabin" => '',
                "fare_class_matched_cabin" => $this->getFareClassCabin($row->fareClass, $row->airline),
                "search_cabin" => $this->Query->Fields['ClassOfService'],
                "1_type" => $connectType,
                "2_type" => $secondType,
                "open_jaw" => $openJaw ? "true" : "false",
                "stopover" => $secondType !== "stop" ? "" : round(($this->airportTime->convertToGmt($rows[$index + 1]->arrDate, $rows[$index + 1]->arrCode) - $this->airportTime->convertToGmt($row->depDate, $row->depCode)) / 86400, 1),
                "layover" => $secondType !== "connect" ? "" : date("H:i", $this->airportTime->convertToGmt($rows[$index + 1]->arrDate, $rows[$index + 1]->arrCode) - $this->airportTime->convertToGmt($row->depDate, $row->depCode)),
                "duration" => date("H:i", $this->airportTime->convertToGmt($row->arrDate, $row->arrCode) - $this->airportTime->convertToGmt($row->depDate, $row->depCode)),
                "distance" => round(Geo::distance($depAirport->getLat(), $depAirport->getLng(), $arrAirport->getLat(), $arrAirport->getLng())),
                "leg_group" => chr(ord('A') + $legGroup),
                "segment_order" => $index + 1,
                "departure_date" => $connectType === "start" || $connectType === "resume" ? date("m/d/Y", $row->depDate) : "",
                "alt_cost_date" => date("m/d/Y", strtotime($this->Query->Fields['UpdateDate'])),
                "ticketed_date" => '',
                "first_detected_date" => '',
                "days_before_departure" => round(($this->airportTime->convertToGmt($row->depDate, $row->depCode) - strtotime($this->Query->Fields['UpdateDate'])) / 86400),
            ];
            $arValues = array_merge($arValues, $this->addRegions($arValues["1"], $arValues["2"]));
            $this->ExportCSVRow($arValues, ",");
            $first = false;
        }
    }

    private function getFareClassCabin(?string $fareClass, string $airlineCode): string
    {
        if (empty($fareClass) || empty($airlineCode)) {
            return "";
        }

        static $fareClassMapper;

        if ($fareClassMapper === null) {
            $fareClassMapper = getSymfonyContainer()->get(FareClassMapper::class);
        }

        $airline = $this->airlineRepository->findOneBy(['code' => $airlineCode]);

        if ($airline === null) {
            return "";
        }

        return $fareClassMapper->map($airline->getAirlineid(), $fareClass ?? '') ?? '';
    }

    private function addRegions(string $sourceAirport, string $destAirport): array
    {
        $oneAirlineRegions = $this->searchRegions($sourceAirport, true);
        $twoAirlineRegions = $this->searchRegions($destAirport, true);
        $oneOtherRegions = $this->searchRegions($sourceAirport, false);
        $twoOtherRegions = $this->searchRegions($destAirport, false);

        return [
            "1_airline_regions" => $oneAirlineRegions["result"],
            //            "1_airline_regions_debug" => $oneAirlineRegions["debug"],
            "2_airline_regions" => $twoAirlineRegions["result"],
            //            "2_airline_regions_debug" => $twoAirlineRegions["debug"],
            "1_other_regions" => $oneOtherRegions["result"],
            //            "1_other_regions_debug" => $oneOtherRegions["debug"],
            "2_other_regions" => $twoOtherRegions["result"],
            //            "2_other_regions_debug" => $twoOtherRegions["debug"],
        ];
    }

    private function searchRegions(string $airport, bool $airportRegions): array
    {
        global $regionKindOptions;

        $regionIds = it($this->connection->executeQuery("
            select RegionID, Kind, 'Airport ' + AirCode as SubRegionID from Region where AirCode = :airCode and Kind = " . REGION_KIND_AIRPORT . "
            union select r.RegionID, r.Kind, 'Country ' + ac.CountryCode as SubRegionID from Region r 
                join Country c on r.CountryID = c.CountryID
                join AirCode ac on ac.CountryCode = c.Code
                where ac.AirCode = :airCode and r.Kind = " . REGION_KIND_COUNTRY . "
            union select r.RegionID, r.Kind, 'State ' + s.Code as SubRegionID from Region r 
                join State s on r.StateID = s.StateID
                join Country c on s.CountryID = c.CountryID
                join AirCode ac on ac.State = s.Code
                where ac.AirCode = :airCode and r.Kind = " . REGION_KIND_STATE . "
        ", ["airCode" => $airport])->fetchAll(FetchMode::ASSOCIATIVE))->reindexByColumn('RegionID')->toArrayWithKeys();

        if (count($regionIds) === 0) {
            return "";
        }

        $result = array_keys($regionIds);
        $processed = $regionIds;
        $parentRegions = $regionIds;
        $debug = [];

        do {
            $parentRegions = it($this->connection->executeQuery("
                select rc.RegionID, r.Kind, rc.SubRegionID from RegionContent rc join Region r on rc.RegionID = r.RegionID 
                where SubRegionID in (?) and rc.Exclude <> 2" . ($airportRegions ? " and r.Kind = " . REGION_KIND_AIRLINE_REGION : " and r.Kind <> " . REGION_KIND_AIRLINE_REGION), [array_keys($parentRegions)], [Connection::PARAM_INT_ARRAY])->fetchAll(FetchMode::ASSOCIATIVE))
                ->reindexByColumn("RegionID")
                ->toArrayWithKeys()
            ;
            $parentRegions = array_diff_key($parentRegions, $processed);
            $processed += $parentRegions;

            $new = $parentRegions;

            $debug = array_merge(
                $debug,
                it($new)
                    ->map(fn (array $row) => $regionKindOptions[$row['Kind']] . ' ' . $row['RegionID'] . ' - found by ' . $row['SubRegionID'])
                    ->toArray()
            );

            $result = array_merge($result, array_keys($new));
        } while (count($parentRegions) > 0);

        return ["result" => implode(",", $result), "debug" => implode(", ", $debug)];
    }
}
