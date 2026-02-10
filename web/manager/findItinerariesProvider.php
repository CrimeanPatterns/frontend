<?
$schema = "findItinerariesProvider";
require "start.php";
//require_once "../trips/common.php";

drawHeader("Providers with Itineraries");

global $Interface;

$arKind = array(
    'T' => 'Trip',
    'L' => 'Rental (Car)',
    'R' => 'Reservation (Hotel)',
    'E' => 'Events',
    'P' => 'Parking'
);
$arTripCategory = array(
    TRIP_CATEGORY_AIR => '<abbr title="Air">A</abbr>',
    TRIP_CATEGORY_BUS => '<abbr title="Bus">B</abbr>',
    TRIP_CATEGORY_TRAIN => '<abbr title="Train">R</abbr>',
    TRIP_CATEGORY_CRUISE => '<abbr title="Cruise">C</abbr>',
    TRIP_CATEGORY_FERRY => '<abbr title="Ferry">F</abbr>',
    TRIP_CATEGORY_TRANSFER => '<abbr title="Transfer">T</abbr>',
);
$arEventType = array(
    EVENT_RESTAURANT => '<abbr title="Restaurant">ER</abbr>',
    EVENT_MEETING => '<abbr title="Meating">EM</abbr>',
    EVENT_SHOW => '<abbr title="Show">ES</abbr>',
    EVENT_EVENT => '<abbr title="Event">EE</abbr>'
);

$arTripCategoryDescr = array(
    TRIP_CATEGORY_AIR => 'Air (A)',
    TRIP_CATEGORY_BUS => 'Bus (B)',
    TRIP_CATEGORY_TRAIN => 'Train (R)',
    TRIP_CATEGORY_CRUISE => 'Cruise (C)',
    TRIP_CATEGORY_FERRY => 'Ferry (F)',
    TRIP_CATEGORY_TRANSFER => 'Transfer (T)',
);
$arEventTypeDescr = array(
    EVENT_RESTAURANT => 'Restaurant (ER)',
    EVENT_MEETING => 'Meating (EM)',
    EVENT_SHOW => 'Show (ES)',
    EVENT_EVENT => 'Event (EE)'
);

$limit = 100;
$filter = "
	(CanCheckItinerary = 1 OR CanCheckConfirmation > 0)
	AND State <> 0
	OR ProviderID IN (7, 16, 26, 145)
";

$sql = "
	SELECT ProviderID, DisplayName, CanCheckCancelled 
	FROM Provider
	WHERE $filter
	ORDER BY DisplayName";
$q = new TQuery($sql);
?>
    <div>Select Provider:</div>
    <form action="/manager/findItinerariesProvider.php" method="get" name="s">
        <select style="width: 200px;" name="ProviderID" onchange="document.forms['s'].elements['UnusedID'].value=this.value; refreshCancel();">
            <?
            while(!$q->EOF){
                echo "<option value='{$q->Fields['ProviderID']}' data-cancel-show='{$q->Fields['CanCheckCancelled']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['DisplayName']}</option>";
                $q->Next();
            }
            ?>
        </select>
        <select name="UnusedID" onchange="document.forms['s'].elements['ProviderID'].value=this.value; refreshCancel();" disabled="disabled">
            <?
            $sql = "
SELECT ProviderID, Code
FROM Provider
WHERE $filter
ORDER BY Code";
            $q = new TQuery($sql);
            while(!$q->EOF){
                echo "<option value='{$q->Fields['ProviderID']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['Code']}</option>";
                $q->Next();
            }
            ?>
        </select><br>
        <script>$(function() {
                $('#Kind').change(function(){
                    if($('#Kind').val() == 'T') {
                        $('#category').show();
                    } else {
                        $('#category').hide();
                        $('select[name="Category"]').prop('selectedIndex',0);
                    }
                    if($('#Kind').val() == 'E') {
                        $('#etype').show();
                    } else {
                        $('#etype').hide();
                        $('select[name="EType"]').prop('selectedIndex',0);
                    }
                });
            });
        </script>
        Kind: <select name="Kind" id="Kind">
            <option value="All">All</option>
            <?
            foreach ($arKind as $value => $text) {
                echo "<option value='{$value}' ".((isset($_GET['Kind']) && $value == $_GET['Kind'])?'selected="selected"':'')." >$text</option>";
            }
            $category = 'none';
            $etype = 'none';
            if (isset($_GET['Kind'])){
                if ($_GET['Kind'] === 'T')
                    $category = 'block';
                else
                    $category = 'none';
                if ($_GET['Kind'] === 'E')
                    $etype = 'block';
                else
                    $etype = 'none';
            }
            ?>
        </select><br>
        <?
        echo "<div id=\"category\" style=\"display: {$category};\">";
        ?>
        Category: <select name="Category">
            <option value="All">All</option>
            <?
            foreach ($arTripCategoryDescr as $value => $text) {
                echo "<option value='{$value}' ".((isset($_GET['Category']) && $value == $_GET['Category'])?'selected="selected"':'')." >$text</option>";
            }
            ?>
        </select><br>
        <?
        echo "</div>";
        echo "<div id=\"etype\" style=\"display: {$etype};\">";
        ?>
        Type: <select name="EType">
            <option value="All">All</option>
            <?
            foreach ($arEventTypeDescr as $value => $text) {
                echo "<option value='{$value}' ".((isset($_GET['EType']) && $value == $_GET['EType'])?'selected="selected"':'')." >$text</option>";
            }
            ?>
        </select><br>
        </div>
        <!--Limit:-->
        <input type="hidden" value="<?=$limit?>" disabled="disabled" size="6"><br>
        <?
        echo "<div id='cancel' style='display: none;'><input type=\"checkbox\" name=\"cancelled\" id=\"cancelled\" title=\"Cancelled\" ".
            ((isset($_GET['cancelled']) && 'on' == $_GET['cancelled'])?'checked=\"checked\"':'').
            " onchange=\"if (this.checked) this.value = 'on'; else this.value='off';\"/><label for=\"cancelled\">Cancelled</label><br></div><br>";
        ?>
        <input type="submit" name="action" value="Find accounts" title="Find accounts with itineraries"/>
        <input type="submit" name="action" value="Find retrieved" title="Find itineraries retrieved by conf #"/>
    </form>
    <script type="text/javascript">
        document.forms['s'].elements['UnusedID'].disabled = false;
        function copy(e) {
            var text = e.previousElementSibling.text;
            var input = document.createElement('input');
            document.body.appendChild(input);
            input.value = text;
            input.select();
            document.execCommand("copy");
            document.body.removeChild(input);
        }
        function  refreshCancel(){
            if($('select[name="ProviderID"]').find('option:selected').attr("data-cancel-show") == '1') {
                $('#cancel').show();
            } else {
                $('#cancel').find('input').removeProp('checked');
                $('#cancel').hide();
            }
        }
        $(document).ready(
            function () {
                refreshCancel();
            }
        );
    </script>
<?

if(isset($_GET['ProviderID'])){
    $providerID = intval($_GET['ProviderID']);

    $sql = "SELECT * FROM Provider WHERE ProviderID = $providerID;";
    $q = new TQuery($sql);
    ?>
    Accounts with itineraries for
    <a href="/manager/list.php?Schema=Provider&ProviderID=<?=$q->Fields['ProviderID']?>" title="LPs"><?=$q->Fields['Code']?></a> &mdash; <?=$q->Fields['DisplayName']?>
    <?
    function _ItenarariesSQL($arWhere = array(), $limit = 100)
    {
        return _TripsSQL($arWhere, $limit)
            . " union " . _RentalsSQL($arWhere, $limit)
            . " union " . _ReservationsSQL($arWhere, $limit)
            . " union " . _RestaurantsSQL($arWhere, $limit)
            . " order by Date(StartDate), SortIndex, StartDate";
    }

    function _TripsSQL($arWhere = array(), $limit = 100)
    {
        $checkArr = implode('', $arWhere);
        if (strpos($checkArr, 't.Cancelled > 0') !== false) {
            $needCancelled = true;
            $arWhere = array_merge($arWhere, ['t.Hidden = 1']);
        }
        $arWhereSub = [];
        foreach ($arWhere as $key => $value) {
            if (strpos($value, '[StartDate]') !== false
                || strpos($value, '[EndDate]') !== false
            ) {
                $arWhereSub[] = $value;
                unset($arWhere[$key]);
            }
        }
        $select = "SELECT 
      t.TripID AS ID,
      'T' AS Kind,
      t.Category,
      t.AccountID,
      t.UserID,
      t.Cancelled, 
      ts.DepDate AS StartDate, ts.ArrDate AS EndDate,
      t.RecordLocator AS ConfirmationNumber,
      a.Itineraries,
      IF(t.Direction = 1, 25, 10) AS SortIndex,
      t.ShareCode
      FROM Trip t ";
        $joinAccount = "
      LEFT OUTER JOIN Account a ON a.AccountID = t.AccountID
  	";
        $where = (count($arWhere) > 0 ? "WHERE " . implode(" AND ", $arWhere) : "");

        $s = "(" . $select . "
          INNER JOIN (
                SELECT tss.TripID, MAX(tss.DepDate) AS DepDate, MAX(tss.ArrDate) AS ArrDate FROM TripSegment tss " .
                (count($arWhereSub) > 0 ? "WHERE " . implode(" AND ", $arWhereSub) : "") . "
                GROUP BY tss.TripID) ts
           ON ts.TripID = t.TripID 
       " . $joinAccount . $where . " LIMIT {$limit})";

        $s = str_ireplace('[StartDate]', 'tss.DepDate', $s);
        $s = str_ireplace('[EndDate]', 'tss.ArrDate', $s);
        if (isset($needCancelled)) {
            $arWhere = array_merge($arWhere, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
            $where = (count($arWhere) > 0 ? "WHERE " . implode(" AND ", $arWhere) : "");
            $select = str_replace('ts.DepDate', 't.UpdateDate', $select);
            $select = str_replace('ts.ArrDate', 't.UpdateDate', $select);
            $s .= "
            UNION (" . $select . $joinAccount . $where . " LIMIT {$limit})";
        }
        return $s;
    }

    function _RentalsSQL($arWhere = array(), $limit = 100)
    {
        $checkArr = implode('', $arWhere);
        if (strpos($checkArr, 't.Cancelled > 0') !== false) {
            $needCancelled = true;
            $arWhere = array_merge($arWhere, ['t.Hidden = 1']);
            $arWhereCancelled = $arWhere;
            foreach ($arWhereCancelled as $key => $value) {
                if (strpos($value, '[StartDate]') !== false
                    || strpos($value, '[EndDate]') !== false
                ) {
                    unset($arWhereCancelled[$key]);
                }
            }
        }
        $select = "select
    t.RentalID as ID, 
    'L' as Kind, 
    0 as Category,
    t.AccountID, 
    t.UserID,
    t.Cancelled,
 	t.PickupDatetime as StartDate, t.DropoffDatetime as EndDate, 
	t.Number as ConfirmationNumber,
	a.Itineraries,
	20 as SortIndex,
    t.ShareCode
	from Rental t
	left outer join Account a on t.AccountID = a.AccountID
	";
        if (isset($needCancelled, $arWhereCancelled)) {
            $arWhere = array_merge($arWhereCancelled, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
        }
        $where = (count($arWhere) > 0 ? "where " . implode(" and ", $arWhere) : "");
        $s = "(" . $select . $where . " LIMIT {$limit})";
        $s = str_ireplace('[StartDate]', 't.PickupDatetime', $s);
        $s = str_ireplace('[EndDate]', 't.DropoffDatetime', $s);
        return $s;
    }

    function _ReservationsSQL($arWhere = array(), $limit = 100)
    {
        $checkArr = implode('', $arWhere);
        if (strpos($checkArr, 't.Cancelled > 0') !== false) {
            $needCancelled = true;
            $arWhere = array_merge($arWhere, ['t.Hidden = 1']);
            $arWhereCancelled = $arWhere;
            foreach ($arWhereCancelled as $key => $value) {
                if (strpos($value, '[StartDate]') !== false
                    || strpos($value, '[EndDate]') !== false
                ) {
                    unset($arWhereCancelled[$key]);
                }
            }
        }
        $select = "select 
    t.ReservationID as ID,
    'R' as Kind, 
    0 as Category, 
    t.AccountID,
    t.UserID,
    t.Cancelled,
	t.CheckInDate as StartDate, t.CheckOutDate as EndDate,
	t.ConfirmationNumber,
	a.Itineraries,
	40 as SortIndex,
    t.ShareCode
	from Reservation t
	left outer join Account a on t.AccountID = a.AccountID
	";
        if (isset($needCancelled, $arWhereCancelled)) {
            $arWhere = array_merge($arWhereCancelled, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
        }
        $where = (count($arWhere) > 0 ? "where " . implode(" and ", $arWhere) : "");
        $s = "(" . $select . $where . " LIMIT {$limit})";
        $s = str_ireplace('[StartDate]', 't.CheckInDate', $s);
        $s = str_ireplace('[EndDate]', 't.CheckOutDate', $s);


        return $s;
    }

    function _RestaurantsSQL($arWhere = array(), $limit = 100)
    {
        $checkArr = implode('', $arWhere);
        if (strpos($checkArr, 't.Cancelled > 0') !== false) {
            $needCancelled = true;
            $arWhere = array_merge($arWhere, ['t.Hidden = 1']);
            $arWhereCancelled = $arWhere;
            foreach ($arWhereCancelled as $key => $value) {
                if (strpos($value, '[StartDate]') !== false
                    || strpos($value, '[EndDate]') !== false
                ) {
                    unset($arWhereCancelled[$key]);
                }
            }
        }
        $select = "select 
    t.RestaurantID as ID, 
    'E' as Kind, 
    t.EventType as Category, 
    t.AccountID,
    t.UserID,
    t.Cancelled,
	t.StartDate as StartDate, t.EndDate as EndDate,
	t.ConfNo as ConfirmationNumber,
	a.Itineraries,
	50 as SortIndex,
    t.ShareCode
	from Restaurant t
	left outer join Account a on t.AccountID = a.AccountID
	";
        if (isset($needCancelled, $arWhereCancelled)) {
            $arWhere = array_merge($arWhereCancelled, ['t.UpdateDate >= NOW() - INTERVAL 14 DAY']);
        }
        $where = (count($arWhere) > 0 ? "where " . implode(" and ", $arWhere) : "");
        $s = "(" . $select . $where . " LIMIT {$limit})";
        $s = str_ireplace('[StartDate]', 't.StartDate', $s);
        $s = str_ireplace('[EndDate]', 't.EndDate', $s);
        return $s;
    }

    function getURL($shareCode)
    {
        if (empty($shareCode))
            return "";

        return getSymfonyContainer()->getParameter('requires_channel') . "://"
            . getSymfonyContainer()->getParameter('host')
            . getSymfonyContainer()->get('router')->generate('aw_timeline_shared', ['shareCode' => $shareCode]);

    }

    $arWhere = array("t.ProviderID = $providerID", "[StartDate] > NOW()", "t.Parsed = 1");
    // for local tests
//    $arWhere = array("t.ProviderID = $providerID", "t.Parsed = 1");
    if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'on') {
        $arWhere = array_merge($arWhere, ['t.Cancelled > 0']);
    }
    else
        $arWhere = array_merge($arWhere, ['t.Cancelled = 0']);
    if (isset($_GET['action']) && $_GET['action']=="Find retrieved"){
        $arWhere = array_merge($arWhere, ["t.AccountID is null", "not (t.ConfFields  is null)"]);
        $accountHeader = "Conf #";
    } else {
        $arWhere = array_merge($arWhere, ["a.SavePassword = ".SAVE_PASSWORD_DATABASE, "a.ProviderID = $providerID"]);
        $accountHeader = "Account";
    }
    if (isset($_GET['Kind'])) {
        if ($_GET['Kind'] === 'T' && isset($_GET['Category']) && $_GET['Category'] !== 'All') {
            $arWhere = array_merge($arWhere, ['t.Category = ' . $_GET['Category']]);
        }
        if ($_GET['Kind'] === 'E' && isset($_GET['EType']) && $_GET['EType'] !== 'All') {
            $arWhere = array_merge($arWhere, ['t.EventType = ' . $_GET['EType']]);
        }
        switch ($_GET['Kind']) {
            case 'T': $sql = _TripsSQL($arWhere, $limit); break;
            case 'L': $sql = _RentalsSQL($arWhere, $limit); break;
            case 'R': $sql = _ReservationsSQL($arWhere, $limit); break;
            case 'E': $sql = _RestaurantsSQL($arWhere, $limit); break;
            case 'P': $sql = 'Parking'; break;
            default: $sql = _ItenarariesSQL($arWhere, $limit);
        }
    } else
        $sql = _ItenarariesSQL($arWhere, $limit);
    if ($sql !== 'Parking') {
        $sql = "SELECT q.ID, q.AccountID, q.UserID, q.Category, q.Cancelled, q.StartDate, q.Kind, IF (q.Itineraries is NULL, 1, q.Itineraries) as Counter, q.ConfirmationNumber, q.ShareCode ".
            "FROM ({$sql}) as q ".
            "WHERE q.Itineraries is NULL or q.Itineraries > 0  " .
            "ORDER BY Counter, q.StartDate, q.AccountID";
        $q = new TQuery($sql);
        if (!$q->EOF) {
            $countTrips = [];
            $countAccounts = [];
            echo
            "
   		<table class=\"detailsTable\" style=\"border-collapse: collapse;\" cellpadding=\"2\">
			<tr>
				<th>Trip ID</th>
				<th title=\"Category\">?</th>
				<th title=\"Itineraries count\">#</th>
				<th>UserID</th>
				<th>{$accountHeader}</th>
				<th title=\"Tool for convenience\">v</th>
			</tr>

        ";
            while (!$q->EOF) {
                $category = isset($arTripCategory[$q->Fields['Category']]) ? $arTripCategory[$q->Fields['Category']] : '&nbsp;';
                $kind = isset($arKind[$q->Fields['Kind']]) ? "<abbr title='{$arKind[$q->Fields['Kind']]}'>{$q->Fields['Kind']}</abbr>" : "$q->Fields['Kind']";

                $id = $q->Fields['ID'];
                $tripColumn = $kind . $id;
                if (!empty($q->Fields['AccountID'])) {
                    $accountid = "<a title=\"Password request\" href=\"/manager/passwordVault/requestPassword.php?ID={$q->Fields['AccountID']}\" target=\"_blank\" style=\"float: left;color:gray;\">PR</a> &nbsp;&nbsp;";
                    $link = "<a href=\"/manager/loyalty/logs?AccountID={$q->Fields['AccountID']}\" title=\"See logs\" target=\"_blank\">{$q->Fields['AccountID']}</a> &nbsp;
                <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick=\"copy(this)\">&nbsp;❒ Copy</button> ";
                } else {
                    $accountid = "<span>{$q->Fields['ConfirmationNumber']}</span>&nbsp;";
                    $link = "<a href=\"/manager/loyalty/logs?ConfNo={$q->Fields['ConfirmationNumber']}&Partner=awardwallet\" title=\"See logs\" target=\"_blank\">log</a>";
                }
                if (!isset($_GET['cancelled']) || $_GET['cancelled']!=='on') {
                    // TODO: through Entity/Itinerary/getEncodeShareCode
                    $shareCode = base64_encode($q->Fields['Kind'] . '.' . $q->Fields['ID'] . '.' . $q->Fields['ShareCode']);
                    $url = getURL($shareCode);
                    $tripColumn = "<a href=\"{$url}\" title=\"timeline\" target=\"_blank\">{$tripColumn}</a>";
                }
                echo "<tr>
					<td>{$tripColumn}</td>
					<td>{$category}</td>
					<td>{$q->Fields['Counter']}</td>
					<td><a title=\"Impersonate\" href=\"/manager/impersonate?UserID={$q->Fields['UserID']}&AutoSubmit&AwPlus=1\" target=\"_blank\">{$q->Fields['UserID']}</a></td>
					<td style='text-align: end;'>{$accountid}{$link}</td>
					<td><input type=checkbox></td>
					</tr>";
                $countAccounts[] = $q->Fields['AccountID'];
                $countTrips[$q->Fields['AccountID']] = $q->Fields['Counter'];
                $q->Next();
            }
            echo "
        </table>
        ";
            $diffAccounts = count(array_filter(array_unique($countAccounts)));
            if ($diffAccounts > 0) {
                $countTrips = array_sum($countTrips);
                if ($countTrips > 0) {
                    echo "Total: " . $countTrips . " itinerar" . ($countTrips == 1 ? "y" : "ies") . " ";
                    echo "for " . $diffAccounts . " different account" . ($diffAccounts == 1 ? "" : "s") . ".<br/>";
                }
            }
        } else {
            $cancelled = '';
            if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'on') {
                $cancelled = 'Cancelled ';
            }
            $kind = isset($_GET['Kind']) && isset($arKind[$_GET['Kind']]) ? $arKind[$_GET['Kind']] : "";
            $kind = $cancelled . $kind;
            $type = isset($_GET['Category']) && $_GET['Category'] !== 'All' && isset($arTripCategory[$_GET['Category']]) ? " (" . $arTripCategory[$_GET['Category']] . ")" : "";
            if (empty($type)) {
                $type = isset($_GET['EType']) && $_GET['EType'] !== 'All' && isset($arEventType[$_GET['EType']]) ? " (" . $arEventType[$_GET['EType']] . ")" : "";
            }
            $kind .= $type;
            echo "<br/><span style='color:red;'>$kind accounts not found</span><br/>";
        }
    } else {
        echo "<br/><span style='color:red;'>Can't search yet. Look at</span>&nbsp;<a href='https://redmine.awardwallet.com/issues/16036' target='_blank'>#16036</a><br/>";
    }
}
else
    $Interface->FooterScripts[] = "setTimeout(function(){ document.forms['s'].elements['ProviderID'].value=1; }, 0);";
print "<br>";
drawFooter();
