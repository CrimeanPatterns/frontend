<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\MileValue\MileValueList;
use AwardWallet\MainBundle\Service\MileValue\SourceLinksFormatter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Routing\RouterInterface;

class HotelPointValueList extends \TBaseList
{
    private BrandMatcher $brandMatcher;

    private Connection $connection;

    public function __construct($table, $fields, BrandMatcher $brandMatcher, Connection $connection)
    {
        parent::__construct($table, $fields);
        unset($fields['SourceCheck']);

        $this->SQL = "select
            HotelPointValue.*,
            Reservation.LastParseDate,
            Reservation.ConfirmationNumber,
            Reservation.UserID,
            Reservation.SpentAwards,
            Reservation.Total,  
            Reservation.CurrencyCode       
        from
            HotelPointValue
            left join Reservation on HotelPointValue.ReservationID = Reservation.ReservationID
        ";

        $this->brandMatcher = $brandMatcher;
        $this->connection = $connection;

        // test for check-see duplicate
        if (isset($_GET['_check'])) {
            // https://docs.google.com/spreadsheets/d/1ffpU3C-RMpF0r5oUEnp7F982lBX5_Iu1jgmVyvHAaWA/edit?usp=sharing
            $reviewed_1 = [29, 724, 4407, 3885, 6213, 14187, 12138, 14086, 721, 11562, 12931, 5758, 15003, 6665, 6156, 3842, 12159, 13359, 6760, 3403, 13542, 5961, 4970, 4600, 4304, 5188, 5128, 11522, 5551, 4229, 11466, 12207, 11213, 12188, 13204, 15196, 6265, 6229, 4769, 3934, 4426, 5033, 15215, 951, 3974, 6624, 2868, 15111, 5669, 3723, 217, 69, 10782, 8929, 13938, 14944, 3789, 6103, 5940, 3839, 4073, 3670, 4240, 14127, 4317, 427, 12322, 727, 11859, 15182, 1010, 9223, 13207, 4403, 3882, 4438, 5352, 4313, 5202, 8900, 10428, 9507, 10110, 60, 125, 13804, 11190, 1187, 2434, 1732, 11438, 10584, 8459, 2306, 15183];
            $reviewed_2 = [15394, 4213, 15454, 5934, 15589, 6015, 15407, 11716, 14827, 1250, 12525, 15338, 15456, 10331, 15476, 12056, 15570, 2078, 15439, 816, 15574, 13399, 15477, 6902, 15492, 1854, 15339, 9509];

            if ('addr' === $_GET['_check']) {
                $sql = '
                    SELECT h.HotelID, h.Name, h.Address, h.Phones, h.Website,
                           p.DisplayName,
                           hb.Name AS BrandName,
                           gt.Lat, gt.Lng, gt.Address AS GeoAddress
                    FROM Hotel h
                    LEFT JOIN Provider p ON p.ProviderID = h.ProviderID
                    LEFT JOIN HotelBrand hb ON hb.HotelBrandID = h.HotelBrandID
                    LEFT JOIN GeoTag gt ON h.GeoTagID = gt.GeoTagID
                    WHERE h.Address IN (
                        SELECT Address
                        FROM Hotel
                        GROUP BY Address HAVING COUNT(*) > 1
                     )
                    ORDER BY h.Address ASC
                ';
                $rows = $connection->fetchAllAssociative($sql);
            } elseif ('phones' === $_GET['_check']) {
                $sql = '
                    SELECT h.HotelID, h.Name, h.Address, h.Phones, h.Website,
                           p.DisplayName,
                           hb.Name AS BrandName,
                           gt.Lat, gt.Lng, gt.Address AS GeoAddress
                    FROM Hotel h
                    LEFT JOIN Provider p ON p.ProviderID = h.ProviderID
                    LEFT JOIN HotelBrand hb ON hb.HotelBrandID = h.HotelBrandID
                    LEFT JOIN GeoTag gt ON h.GeoTagID = gt.GeoTagID
                    WHERE h.Phones IN (
                        SELECT Phones
                        FROM Hotel
                        WHERE Phones IS NOT NULL AND Phones <> \'\'
                        GROUP BY Phones HAVING COUNT(*) > 1
                     )
                    ORDER BY h.Phones ASC
                ';
                $rows = $connection->fetchAllAssociative($sql);
            }
            $html = '<style>td {padding:4px 5px;border-color: #a0a0a0;}</style><table border="1" style="border-spacing: 0;border-collapse: collapse;">';
            $html .= '<tr><th></th><th>#</th><th>HotelID</th><th>Name</th><th>Address</th><th>Phones</th><th>Provider</th><th>Brand</th><th>Website</th><th>Geo</th><th>Geo Address</th></tr>';
            $i = 0;

            foreach ($rows as $row) {
                $isReviewed = in_array($row['HotelID'], $reviewed_1) ? 1 : '';
                !empty($isReviewed) ? null : $isReviewed = in_array($row['HotelID'], $reviewed_2) ? 2 : '';

                $html .= '<tr' . ($isReviewed ? ' style="opacity:.6"' : '') . '>';
                $html .= '<td ' . ($isReviewed ? 'style="background:green;" title="reviewed"' : '') . '>' . $isReviewed . '</td>';
                $html .= '<td>' . ++$i . '</td>';
                $html .= '<td><a href="/manager/list.php?Schema=HotelPointValue&HotelPointValueID=">' . $row['HotelID'] . '</a></td>';
                $html .= '<td>' . $row['Name'] . '</td>';
                $html .= '<td>' . $row['Address'] . '</td>';
                $html .= '<td>' . $row['Phones'] . '</td>';
                $html .= '<td>' . $row['DisplayName'] . '</td>';
                $html .= '<td>' . $row['BrandName'] . '</td>';
                $html .= '<td><a href="' . $row['Website'] . '">' . $row['Website'] . '</a></td>';
                $html .= '<td>' . $row['Lat'] . ' / ' . $row['Lng'] . '</td>';
                $html .= '<td>' . $row['GeoAddress'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            exit($html);
        }
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output !== "html") {
            return;
        }

        if (!empty($this->Query->Fields["ReservationID"])) {
            /** @var RouterInterface $router */
            $router = getSymfonyContainer()->get("router");

            if (!empty($this->Query->Fields['UserID'])) {
                $this->Query->Fields["UpdateDate"] .= " Parsed: " . $this->Query->Fields["LastParseDate"];
                $targetUrl = $router->generate("aw_timeline_html5_itineraries", ["itIds" => 'R.' . $this->Query->Fields["ReservationID"]]);
                $link = $router->generate("aw_manager_impersonate", ["UserID" => $this->Query->Fields["UserID"], "Full" => 1, "Goto" => $targetUrl]);
                $this->Query->Fields["ReservationID"] = "<a target='_blank' href='{$link}'>{$this->Query->Fields["ReservationID"]}</a>";
            }
        }

        $checkInDate = strtotime($this->Query->Fields['CheckInDate']);
        $checkOutDate = strtotime($this->Query->Fields['CheckOutDate']);
        $this->Query->Fields["AlternativeCost"] = "<a href='https://www.booking.com/searchresults.html?ss=" . urlencode($this->Query->Fields["AlternativeHotelName"]) . "&checkin_monthday=" . date("d", $checkInDate) . "&checkin_year_month=" . date("Y-m", $checkInDate) . "&checkout_monthday=" . date("d", $checkOutDate) . "&checkout_year_month=" . date("Y-m", $checkInDate) . "&timelineForm=true' target='_blank'>{$this->Query->Fields['AlternativeCost']}</a>";

        $this->Query->Fields["AlternativeHotelName"] = "<a href='{$this->Query->Fields['AlternativeHotelURL']}' target='_blank'>{$this->Query->Fields['AlternativeHotelName']}</a>";

        $sources = $this->connection->fetchFirstColumn('SELECT Sources FROM Reservation WHERE ReservationID = ' . (int) $this->OriginalFields['ReservationID']);
        $this->Query->Fields['SourceCheck'] = SourceLinksFormatter::formatSources($sources);
    }

    public function DrawButtonsInternal()
    {
        $result = parent::DrawButtonsInternal();

        echo "<input id=\"MatchBrandsId\" class='button' type=button value=\"Match Brands\" onclick=\"this.form.action.value = 'matchBrands'; form.submit();\"> ";
        $result[] = ['MatchBrandsId', 'Match Brands'];

        echo '<script>if (0 == $("#checkTest").length) $("#extendFixedMenu").append("<div id=\'checkTest\' style=\'padding: 5px 0 0 5px;\'>Group duplicated by: <a href=\'/manager/list.php?Schema=HotelPointValue&_check=addr\'>Address</a> / <a href=\'/manager/list.php?Schema=HotelPointValue&_check=phones\'>Phones</a></div>")</script>';

        return $result;
    }

    public function ProcessAction($action, $ids)
    {
        parent::ProcessAction($action, $ids);

        switch ($action) {
            case "matchBrands":
                $updated = 0;

                foreach ($ids as $id) {
                    $hotel = $this->connection->executeQuery("select HotelName, ProviderID, BrandID from HotelPointValue where HotelPointValueID = ?", [$id])->fetch(FetchMode::ASSOCIATIVE);
                    $brand = $this->brandMatcher->match($hotel['HotelName'], $hotel['ProviderID']);
                    $brandId = $brand ? $brand->getId() : null;

                    if ($brandId !== $hotel['BrandID']) {
                        $this->connection->executeUpdate("update HotelPointValue set BrandID = ? where HotelPointValueID = ?", [$brandId, $id]);
                        $updated++;
                    }
                }

                echo "update brand of $updated records<br/>";

                break;
        }
    }

    protected function getRowColor(): string
    {
        return MileValueList::getRowColorByStatus(parent::getRowColor(), $this->OriginalFields["Status"]);
    }
}
