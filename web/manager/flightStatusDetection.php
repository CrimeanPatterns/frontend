<?php

use AwardWallet\MainBundle\Service\ProviderNameResolver;

$schema = "flightStatusDetection";
require "start.php";
require_once $sPath . "/kernel/siteFunctions.php";
require_once( "$sPath/kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once __DIR__ . "/reports/common.php";
$bSecuredPage = False;
require __DIR__ . "/reports/paymentsCommon.php";

drawHeader("Flight status detection");

print "<h2>Flight Status Airline Name Detection</h2><br>";

$fields = array(
	"StartDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
	),
	"EndDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m") + 1, 0, date("Y"))),
	),
	"Button" => array(
		"Type" => "html",
		"Caption" => "",
		"HTML" => getTodayButtons(),
	),
	"TravelPlanLimit" => array(
		"Type" => 'integer',
		'Caption' => 'TravelPlan limit per provider',
		'Value' => '10',
		'Size' =>'4'
	)
);
$objForm = new TForm($fields);
$objForm->SubmitButtonCaption = "Show stats";

if($objForm->IsPost && $objForm->Check()){
	echo $objForm->HTML();
	$objForm->CalcSQLValues();
	echo "<div>{$objForm->Fields["StartDate"]["Value"]} <= Departure Date < {$objForm->Fields["EndDate"]["Value"]}</div><br>";

	print "<table border=1>";

	$grouConcatMax = $objForm->Fields['TravelPlanLimit']['Value'] * 100;
	$Connection->Execute("SET group_concat_max_len := {$grouConcatMax};");
	$q = new TQuery("
		SELECT
			stat.cnt AS TotalCount,
			ts.AirlineName,
			p.ProviderID,
			GROUP_CONCAT(DISTINCT CONCAT(tp.UserID, '-', ts.FlightNumber, '-', tp.TravelPlanID,'-', tp.Code) ORDER BY ts.DepDate ASC SEPARATOR ',') AS TravelPlanIDs,
			tp.UserID,
			count(*) AS CountByProvider,
			p.Name AS ProviderName
		FROM TripSegment ts
		JOIN Trip t
			ON ts.TripID = t.TripID
		JOIN TravelPlan tp
			ON tp.TravelPlanID = t.TravelPlanID
		LEFT JOIN Provider p
			ON p.ProviderID = t.ProviderID
		JOIN (
			SELECT
				COUNT(*) AS cnt,
				ts.AirlineName
			FROM TripSegment ts
			JOIN Trip t
				ON t.TripID = ts.TripID
			JOIN TravelPlan tp
        		ON tp.TravelPlanID = t.TravelPlanID
			WHERE
				t.TravelPlanID IS NOT NULL
				AND tp.Hidden = 0
				AND t.Hidden = 0
				AND ts.DepDate BETWEEN ". $objForm->Fields["StartDate"]["SQLValue"] ." AND ". $objForm->Fields["EndDate"]["SQLValue"] ."
				AND ts.AirlineName IS NOT NULL
			GROUP BY
				BINARY ts.AirlineName
		) stat ON BINARY stat.AirlineName = BINARY ts.AirlineName
		WHERE
			t.TravelPlanID IS NOT NULL
			AND t.Hidden = 0
			AND tp.Hidden = 0
			AND ts.DepDate BETWEEN". $objForm->Fields["StartDate"]["SQLValue"] ." AND ". $objForm->Fields["EndDate"]["SQLValue"] ."
			AND ts.AirlineName IS NOT NULL
		GROUP BY stat.cnt, ts.AirlineName, p.ProviderID, tp.UserID, p.Name
		ORDER BY
			stat.cnt DESC,
			ts.AirlineName,
			CountByProvider DESC");

	if(isset($_GET['Debug']))
		echo "<pre class='sqlDump'>" . nl2br($q->SQL) . "</pre>";
	$res = array();
	$lastName = null;

	$providerNameResolver = getSymfonyContainer()->get(ProviderNameResolver::class);
	$rows = array();

	$filters = array();
	$files = glob($sPath . '/../engine/*/extensionMobile.js');
	foreach ($files as $filename) {
		$provider = basename(dirname($filename));
		$content = file_get_contents($filename);
		if (preg_match('/match:\s*(\/.+\/[A-z]+),/U', $content, $matches)) {
			if (!empty($matches[1])) {
				$filters[$provider] = $matches[1];
			}
		}
	}

	foreach($q as $data){
		if($lastName != $data['AirlineName']){
			if(isset($row))
				$rows[] = $row;
			$lastName = $data['AirlineName'];

			$row = array_intersect_key($data, array(
				'TotalCount' => null,
				'AirlineName' => null,
			));
			$row['Provider'] = $providerNameResolver->resolve($data['AirlineName'], array_keys($filters));

		}

		if($lastName == $data['AirlineName']){
			$row['Childs'][] = array_intersect_key($data, array(
				'CountByProvider' => null,
				'ProviderID' => null,
				'ProviderName' => null,
				'TravelPlanIDs' => null,
				'SharingCode' => null,
				'UserID' => null
			));
		}
	}

	?>
	<style>
	/*subtables*/
	table.level1, table.level2, table.level3 {
		border-collapse: collapse;
	}
	table.level1 {
		width:900px;
		margin-right:200px;
	}
	table.level2, table.level3 {
		width: 100%;
	}
	table.level1 th {
		background:#ddd;
		padding:3px 6px;
		border:1px solid #ccc;
		font-size: 16px;
	}
	table.level1 td {
		padding:3px 6px;
		border:1px solid #ccc;
		text-align:center;
	}
	table.level2 th {
		font-size: 12px;
	}
	table.level3 th {
		font-size: 10px;
	}
	div.tableContainer {
		padding: 3px 0 0 0;
		display: none;
	}
	table.level1 td.tableContainer {
		padding: 3px 0 0 0;
	}
	.sqlDump {
		color:#0000aa;
		font-family: lucida;
	}
	.counter {
		vertical-align: top;
	}
	table.level1 .counter {
		width: 15%;
	}
	table.level2 .counter {
		width: 20%;
	}
	</style>

	<table class='level1'>
	    <tr>
			<th class="counter">Segments total</th>
			<th>Airline Name</th>
			<th>Detected Provider</th>
	    </tr>
	<?
	$lastRow = null;
	foreach($rows as $id => $row){
		// first items
		$lastRow = $row['AirlineName'];

		$style = '';
		$displayName = '';
		if(isset($row['Provider'])){
			$displayName = $row['Provider']['DisplayName'];
			//$displayName = "<a href='manager/edit.php?Schema=Provider&ID=" . $row['Provider']->getProviderid() ."'>". $row['Provider']->getDisplayname() . "</a>";
		}else{
			$style = 'background-color: #FF8888;';
		}

		?>
		<tr>
			<td class="counter"><a class='smallOpen' href="javascript:showChild('<?=$id?>')"><?=$row['TotalCount']?></a></td>
			<td  class="tableContainer">
				<div><?=htmlspecialchars($row['AirlineName'])?></div>
				<div id='<?=$id?>' class="tableContainer">
					<table class="level2">
						<tr>
							<th class="counter">by Provider</th>
							<th>Provider</th>
						</tr>
						<?
							foreach($row['Childs'] as $childId => $child){
								// parse travel plans from group concat
								$agregator = 'orphan';
								if(isset($child['ProviderName'])){
									$agregator = $child['ProviderName'];
								}

								?>
								<tr>
									<td class="counter"><a class='smallOpen' href='javascript:showChild("<?=($id . '_' . $childId)?>")'><?=$child['CountByProvider']?></a></td>
									<td class="tableContainer">
										<div><?=$agregator?></div>
										<div id="<?=$id . '_' . $childId?>" class="tableContainer">
											<table class='level3'>
												<tr>
													<th>UserID</th>
													<th>Flight Number</th>
													<th>TravelPlan</th>
												</tr>
												<?
													// parse travelplans from group_concat
													$travelPlans = array_filter(array_map(
														function($elem){
															if(!empty($elem)){
																$explode = explode('-', $elem);
																if(count($explode) == 4){
																	$res = [];
																	list($res['UserID'], $res['FlightNumber'], $res['TravelPlanID'], $res['SharingCode']) = $explode;
																	return $res;
																}
															}
															return null;
														},
														array_slice(
															explode(',', $child['TravelPlanIDs']),
															0,
															$objForm->Fields['TravelPlanLimit']['Value']
														)
													));

													foreach($travelPlans as $travelPlan){
														// grandchilds
														?>
														<tr>
															<td><a title="view by sharing code" href="/manager/impersonate?UserID=<?=$travelPlan['UserID']?>"><?=$travelPlan['UserID']?></a></td>
															<td><?=htmlspecialchars($travelPlan['FlightNumber'])?></td>
															<td><a title="impersonae" href="/trips/view.php?ID=<?=$travelPlan['TravelPlanID']?>&Code=<?=$travelPlan['SharingCode']?>">View travelplan</a></td>
														</tr>
														<?
													}
												?>
											</table>
										</div>
									</td>
								</tr>
								<?
							}
						?>
					</table>
				</div>
			</td>
			<td style="<?=$style?>"><?=$displayName?></td>
		</tr>
		<?
	}
	?>
	</table>
	<script type='text/javascript'>
	    function showChild(path){
			$('div.tableContainer#' + path).toggle();
	    }
	</script>
	<?
}else{
	echo $objForm->HTML();
}

drawFooter();
