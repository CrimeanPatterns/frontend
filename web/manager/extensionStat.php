<?php

use AwardWallet\MainBundle\Entity\Extensionstat;

$schema = "extensionStat";

require "start.php";

$platforms = [
    'desktop',
    'mobile-autologin',
    'mobile-update',
];

$sortings = [
    'popularity' => function (array $x, array $y) {
        return $x['Accounts'] - $y['Accounts'];
    },
    'rate' => function (array $x, array $y) {
        return $x['Rate'] - $y['Rate'];
    },
    'failCount' => function (array $x, array $y) {
        return $x['FailCount'] - $y['FailCount'];
    },
];

$standardErrors = [
    "Can't determine login state" => "standardErrors1",
    "Login form not found" => "standardErrors2",
    "Password form not found" => "standardErrors3",
    "Itinerary form not found" => "standardErrors4",
    "Itinerary not found" => "standardErrors5",
    "Timed out" => "standardErrors6",
    "We could not recognize captcha. Please try again later." => "standardErrors7",
];

function invertSort(callable $comparator)
{
    return function ($x, $y) use ($comparator) {
        return -$comparator($x, $y);
    };
}

if (
    !($platform = ArrayVal($_GET, 'platform'))
    || !in_array($platform, $platforms, true)
) {
    header('Location: /manager/extensionStat.php?platform=desktop');

    exit;
}

$origSorting = ArrayVal($_GET, 'sorting');
$provId = (int) ArrayVal($_GET, 'id');

if ($origSorting === '') {
    $origSorting = '!popularity';
}

$sortAsc = ('!' !== $origSorting[0]);
$sorting = ltrim($origSorting, '!');

if (!array_key_exists($sorting, $sortings)) {
    $origSorting = '!popularity';
    $sorting = 'popularity';
    $sortAsc = false;
}
$from = ArrayVal($_GET, 'from');
$to = ArrayVal($_GET, 'to');

if (!$from || !$to
    || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $from) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $to)
    || strtotime($from) > strtotime($to)) {
    $from = date('Y-m-d', strtotime("-3 months"));
    $to = date('Y-m-d');

    $additionalHeaderText = "last 3 months";
    $dateFilterSQL = '';
    $period = '';
} else {
    if ($from === $to) {
        $additionalHeaderText = $from;
    } else {
        $additionalHeaderText = "period from " . $from . " to " . $to;
    }
    $dateFilterSQL = sprintf(" AND es.ErrorDate >= '%s' AND es.ErrorDate < '%s' ",
        $from,
        date('Y-m-d', strtotime("+1 day", strtotime($to)))
    );
    $period = '&from=' . $from . '&to=' . $to;
}

drawHeader("Extension autologin statistics", "Extension autologin statistics for " . $additionalHeaderText);
global $Interface;
$arrow = $sortAsc ? '⇧' : '⇩';
$commonSortPrefix = '?platform=' . $platform . $period . '&sorting=';
?>
<style>
    .btn-link {
        font: 11px Arial;
        text-decoration: none;
        background-color: #EEEEEE;
        color: #333333;
        padding: 2px 6px 2px 6px;
        border-top: 1px solid #CCCCCC;
        border-right: 1px solid #333333;
        border-bottom: 1px solid #333333;
        border-left: 1px solid #CCCCCC;
    }
    li.tab-item {
        -moz-border-radius: 8px 8px 0 0; /* FF1+ */
        -webkit-border-radius: 8px 8px 0 0; /* Saf3-4 */
        border-radius: 8px 8px 0 0; /* Opera 10.5, IE9, Saf5, Chrome */
        border: 1px solid black;
        display: inline;
        list-style-type: none;
        padding: 5px;
        margin-right: 5px;
    }

    li.tab-item:hover:not(.selected-tab) {
        background: grey !important;
    }

    li.tab-item a {
        text-decoration: none;
        color: black;
        font-weight: bold;
    }

    li.tab-item.selected-tab {
        background: gold;
    }

    ul.tab-group {
        border-bottom: 1px solid black;
        padding: 5px;
    }

    .descriptionProviderStatus {
        padding: 20px 50px 20px 0;
    }

    .descriptionProviderStatus table {
        border-collapse: collapse;
    }

    .descriptionProviderStatus table td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    .descriptionProviderStatus table td.titleProvStat {
        font-weight: bold;
    }

    table#mainTable td.caption {
        background: #A9F5F2;
    }

    i[class^="icon-"], i[class*=" icon-"] {
        display: inline-block;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        background-image: url(../assets/awardwalletnewdesign/img/sprite.png?v=2);
        background-repeat: no-repeat;
    }
    .icon-blue-info {
        width: 13px;
        height: 13px;
        background-position: -111px -99px;
    }
</style>
<link rel="stylesheet" type="text/css" href="/../design/mainStyle.css" />
<link rel="stylesheet" type="text/css" href="/../design/adminStyle.css" />
    <div style="float: right;">
        <input type="date" name="from" value="<?php echo $from; ?>">
        <span>-</span>
        <input type="date" name="to" value="<?php echo $to; ?>">
        <a class="btn-link" onclick="refreshPeriod(0);"> search </a>
        <a class="btn-link" onclick="refreshPeriod(1);"> today </a>
        <a class="btn-link" onclick="refreshPeriod(2);"> clear </a>
    </div>
<ul class="tab-group">
    <?php foreach ($platforms as $platformName) { ?>
        <li class="tab-item <?php echo $platformName === $platform ? 'selected-tab' : ''; ?>">
            <?php if (empty($provId)) { ?>
                <a href="?platform=<?php echo $platformName . $period; ?>"><?php echo ucfirst($platformName); ?></a>
            <?php } else { ?>
                <a href="?platform=<?php echo $platformName . $period; ?>&id=<?php echo $provId; ?>"><?php echo ucfirst($platformName); ?></a>
            <?php } ?>
        </li>
    <?php } ?>
</ul>
<table cellpadding='5' cellspacing='0' class="detailsTable" id="mainTable">
	<tr>
		<td style="font-weight: bold;">Code</td>
		<td style="font-weight: bold;">Provider</td>
		<td style="font-weight: bold;"><a href="<?php echo $commonSortPrefix . (('rate' === $sorting && $sortAsc) ? '!' : ''); ?>rate" rel="SortBySuccess"><b><?php echo 'rate' === $sorting ? $arrow : ''; ?>Success %</b></a></td>
		<td style="font-weight: bold;">Total count</td>
		<td style="font-weight: bold;">Success count</td>
		<td style="font-weight: bold;"><a href="<?php echo $commonSortPrefix . (('failCount' === $sorting && $sortAsc) ? '!' : ''); ?>failCount" rel="SortByErrorCount"><b><?php echo 'failCount' === $sorting ? $arrow : ''; ?>Error count</b></a></td>
		<td style="font-weight: bold;" rel="ErrorCode">Error code</td>
		<td style="font-weight: bold;" rel="ErrorMessage">Error message</td>
		<td style="font-weight: bold;"><a href="<?php echo $commonSortPrefix . (('popularity' === $sorting && $sortAsc) ? '!' : ''); ?>popularity" rel="SortByPopularity"><b><?php echo 'popularity' === $sorting ? $arrow : ''; ?>Popularity</b></a></td>
		<td class="providerStatus"><a rel="RememberAll" href="?remember=-1&platform=<?php echo $platform . $period; ?>">Remember All</a></td>

	<tr>
<?php
$platformSql = addslashes($platform);
$clear = (int) ArrayVal($_GET, "clear", 0);

if ($clear !== 0) {
    $Connection->Execute("delete from ExtensionStat es where es.ProviderID = $clear AND es.Platform = '{$platformSql}'" . $dateFilterSQL);
    Redirect("/manager/extensionStat.php?platform={$platform}{$period}");
}
$forget = (int) ArrayVal($_GET, "forget", 0);

if ($forget !== 0) {
    $Connection->Execute("update ExtensionStat es set es.Forget = 1 where es.ProviderID = $forget AND es.Platform = '{$platformSql}'" . $dateFilterSQL);
    Redirect("/manager/extensionStat.php?platform={$platform}{$period}");
}
$remember = (int) ArrayVal($_GET, "remember", 0);

if ($remember !== 0) {
    if ($remember !== -1) {
        $filter = "where es.ProviderID = $remember AND es.Platform = '{$platformSql}'";
    } else {
        $filter = "where es.Platform = '{$platformSql}'";
    }
    $Connection->Execute("update ExtensionStat es set es.Forget = 0 $filter" . $dateFilterSQL);
    Redirect("/manager/extensionStat.php?platform={$platform}{$period}");
}
$providerId = intval(ArrayVal($_GET, 'id', 0));
$providerSql = "";

if (!empty($providerId)) {
    $providerSql = " AND p.ProviderID = {$providerId}";
}
$q = new TQuery(
    "SELECT
    p.ProviderID,
    p.Code,
    p.DisplayName,
    p.State,
    p.MobileAutoLogin,
    p.Accounts,
    es.Status,
    es.Count,
    es.ErrorText,
    es.ErrorCode,
    es.Forget
FROM ExtensionStat es
    JOIN Provider p ON es.ProviderID = p.ProviderID
WHERE
    es.Platform = '{$platformSql}'
    AND (p.State >= " . PROVIDER_ENABLED . " OR p.State = " . PROVIDER_TEST . ")
    {$providerSql}
    {$dateFilterSQL}
ORDER BY es.ProviderID ASC, Status DESC
");

$providers = [];
$appendix = [];
$minTotalDate = null;
$isMobile = \in_array($platform, ['mobile-autologin', 'mobile-update'], true);

while (!$q->EOF) {
    $code = $q->Fields["Code"];
    $provider = [
        "Code" => $code,
        "ID" => $q->Fields["ProviderID"],
        "Name" => $q->Fields["DisplayName"],
        "MobileAutologin" => $q->Fields["MobileAutoLogin"],
        "Accounts" => $q->Fields["Accounts"],
        "TotalCount" => 0,
        "FailCount" => 0,
        "SuccessCount" => 0,
        "Errors" => [],
        "Forget" => $q->Fields["Forget"],
    ];

    while (!$q->EOF && $q->Fields["Code"] == $code) {
        $count = $q->Fields["Count"];

        switch ($q->Fields["Status"]) {
            case Extensionstat::STATUS_SUCCESS:
                $provider["SuccessCount"] += $count;

                break;

            case Extensionstat::STATUS_FAIL:
                $errorText = $q->Fields["ErrorText"];

                if (\array_key_exists($errorText, $provider["Errors"])) {
                    $provider["Errors"][$errorText]['Count'] += $count;
                } else {
                    $provider["Errors"][$errorText] = [
                        "Error" => $q->Fields["ErrorText"],
                        "ErrorCode" => $q->Fields["ErrorCode"],
                        "Count" => $count,
                    ];
                }
                $provider["FailCount"] += $count;

                break;

            case Extensionstat::STATUS_TOTAL:
                $provider["TotalCount"] += $count;

                break;
        }

        $q->Next();
    }

    if (!$isMobile) {
        $provider["TotalCount"] = $provider["SuccessCount"] + $provider["FailCount"];
    }

    if ($isMobile && ($provider["TotalCount"] == 0)) {
        $provider["Rate"] = 'N/A';
    } else {
        $provider["Rate"] = (int) ($provider["SuccessCount"] * 100 / $provider["TotalCount"]);
    }

    if ($provider["Forget"]) {
        $appendix[] = $provider;
    } else {
        $providers[] = $provider;
    }
}

uasort($providers, $sortAsc ? $sortings[$sorting] : invertSort($sortings[$sorting]));
uasort($appendix, $sortAsc ? $sortings[$sorting] : invertSort($sortings[$sorting]));
$providers = array_merge($providers, $appendix);

foreach ($providers as $provider) {
    if (!$provider["Forget"]) {
        $link = "<span class=\"providerStatus\"><a rel=\"Forget\" href=\"?forget={$provider["ID"]}&platform={$platform}{$period}&sorting={$origSorting}\">Forget</a></span>";
    } else {
        $link = "<span class=\"providerStatus\"><a rel=\"Remember\" href=\"?remember={$provider["ID"]}&platform={$platform}{$period}&sorting={$origSorting}\">Remember</a></span>";
    }

    $providerInfo = '';

    if ('mobile-autologin' === $platform) {
        $autologinStates = [
            MOBILE_AUTOLOGIN_DISABLED => 'Disabled',
            MOBILE_AUTOLOGIN_SERVER => 'Server',
            MOBILE_AUTOLOGIN_EXTENSION => 'Mobile extension',
            MOBILE_AUTOLOGIN_DESKTOP_EXTENSION => 'Desktop extension',
        ];

        if (isset($autologinStates[$provider['MobileAutologin']])) {
            $providerInfo = " ({$autologinStates[$provider['MobileAutologin']]})";
        }
    }
    ?>
	<tr>
		<td class="caption" rel="ProviderCode"><?php echo $provider["Code"] . $providerInfo; ?>&nbsp;<a target="_blank" href="edit.php?Schema=Provider&ID=<?php echo $provider['ID']; ?>">>></a></td>
        <td class="caption" rel="ProviderName"><?php echo $provider["Name"]; ?> <button title="Скопировать ссылку на статистику провайдера" style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick="copy(this)">&nbsp;❒ Copy</button></td>
		<td style="<?php echo (\is_int($provider["Rate"]) && ($provider["Rate"] < 50)) ? "background-color: red;" : ""; ?>" rel="Success"><?php echo \is_int($provider["Rate"]) ? (number_format($provider["Rate"], 2) . ' %') : $provider["Rate"]; ?>
		</td>
		<td style="background: #ffffb2" rel="TotalCount"><?php echo $provider["TotalCount"]; ?></td>
		<td style="background: #b6e2b7" rel="SuccessCount"><?php echo $provider["SuccessCount"]; ?></td>
		<td style="background: #ffb2b2" rel="ErrorCount"><?php echo $provider["FailCount"]; ?></td>
		<td class="caption" rel="ErrorCode">Code</td>
		<td class="caption" rel="ErrorMessage">Error Message</td>
		<td class="caption" rel="Popularity"><?php echo $provider["Accounts"]; ?></td>
		<td class="providerStatus caption">
			<nobr><a rel="ClearProvider" href="extensionStat.php?clear=<?php echo $provider["ID"]; ?>&platform=<?php echo $platform . $period; ?>&sorting=<?php echo $sorting; ?>">Clear provider</a> | <?php echo $link; ?></nobr>
		</td>
	</tr>
	<?php
        foreach ($provider["Errors"] as $error) {
            ?>
		<tr>
			<td colspan="5"></td>
			<td><?php echo $error["Count"]; ?> (<?php echo number_format($error["Count"] * 100 / $provider["FailCount"], 2); ?>%)</td>
			<td rel="<?php echo $error["ErrorCode"]; ?>"><?php echo $error["ErrorCode"]; ?></td>
            <td <?php echo empty(CleanXMLValue($error["Error"])) ? "style=\"background-color: red;\"" : ""; ?>><?php echo $error["Error"]; ?><?php echo isset($standardErrors[preg_replace('/\s+\[[^\]]+\]$/', '', $error["Error"])]) ? " <i class='icon-blue-info' rel=\"" . $standardErrors[preg_replace('/\s+\[[^\]]+\]$/', '', $error["Error"])] . "\"></i>" : ""; ?></td>
			<td class="providerStatus" rel="AccountIDs"><a href="/manager/extension/stat/details?providerId=<?php echo $provider['ID']; ?>&platform=<?php echo $platform; ?>&errorCode=<?php echo $error['ErrorCode']; ?>&msg=<?php echo rawurlencode($error['Error']); ?>">AccountIDs</a></td>
		</tr>
		<?php
        }
}
?>
</table>

<div class='descriptionProviderStatus'>
    <table>
        <tr>
            <th class='titleProvStat' style="background: #ffffb2;" align="left">Option</th>
            <th class='titleProvStat' style="background: #ffffb2;" align="left">Description</th>
        </tr>
        <tr>
            <td class='titleProvStat'><nobr>Clear provider</nobr></td>
            <td lab="ClearProvider">Сбросить статистику по провайдеру</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Forget</td>
            <td lab="Forget">"Забыть" провайдер (переместить его в конец списка)</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Remember</td>
            <td lab="Remember">Восстановить приоритет для этого провайдера в списке (отменить действие "Forget" для этого провайдера)</td>
        </tr>
        <tr>
            <td class='titleProvStat'><nobr>Remember All</nobr></td>
            <td lab="RememberAll">Восстановить приоритет всех провайдеров в списке (отменить действие "Forget" для всех провайдеров)</td>
        </tr>
    </table>
</div>

<div class='descriptionProviderStatus'>
    <table>
        <tr>
            <th class='titleProvStat' style="background: #ffffb2;" align="left">Сode</th>
            <th class='titleProvStat' style="background: #ffffb2;" align="left">Description</th>
        </tr>
        <tr>
            <td class='titleProvStat'>2</td>
            <td lab="2">Неверные кренделя</td>
        </tr>
        <tr>
            <td class='titleProvStat'>3</td>
            <td lab="3">Аккаунт был заблокирован провайдером из-за многочисленных неудачных попыток входа</td>
        </tr>
        <tr>
            <td class='titleProvStat'>4</td>
            <td lab="4">Провайдер вернул какую-либо ошибку, не относящуюся к неверным учетным данным, или требует некоторых действий от пользователя</td>
        </tr>
        <tr>
            <td class='titleProvStat'>6</td>
            <td lab="6">Попытка собрать данные с сайта провайдера была неудачной по неизвестной причине</td>
        </tr>
        <tr>
            <td class='titleProvStat'>11</td>
            <td lab="11">Что-то пошло не так при работе extension'a или аккаунт проверялся слишком долго</td>
        </tr>
    </table>
</div>

<div class='descriptionProviderStatus' style="display: none;">
    <table>
        <tr>
            <td class='titleProvStat'>Provider code</td>
            <td lab="ProviderCode">Код провайдера</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Provider name</td>
            <td lab="ProviderName">Название провайдера</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Sort by Success %</td>
            <td lab="SortBySuccess">Отсортировать список по проценту успеха</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Sort by Error count</td>
            <td lab="SortByErrorCount">Отсортировать список по количеству ошибок</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Sort by Popularity</td>
            <td lab="SortByPopularity">Отсортировать список по популярности (по количеству аккаунтов)</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Success %</td>
            <td lab="Success">Процент успеха</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Total count</td>
            <td lab="TotalCount">Общее количество автологинов / проверок</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Success count</td>
            <td lab="SuccessCount">Количетсво успешных автологинов / проверок</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Error count</td>
            <td lab="ErrorCount">Общее количество автологинов / проверок, закончившихся с ошибками</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Error code</td>
            <td lab="ErrorCode">Код ошибки, полученный при работе extension'a</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Error message</td>
            <td lab="ErrorMessage">Текст ошибки, полученный при работе extension'a</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Popularity</td>
            <td lab="Popularity">Общее количество аккаунтов у провайдера</td>
        </tr>

        <tr>
            <td class='titleProvStat'>Can't determine login state</td>
            <td lab="standardErrors1">Extension не смог определить залогинен ли пользователь, данная ситуация требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Login form not found</td>
            <td lab="standardErrors2">Extension не смог найти форму логина, данная ситуация требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Password form not found</td>
            <td lab="standardError3">Extension не смог найти форму пароля, данная ситуация требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Itinerary form not found</td>
            <td lab="standardErrors4">Extension не смог найти форму для ретрива резервации по Conf # (автологин в резервацию), данная ситуация требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Itinerary not found</td>
            <td lab="standardErrors5">Extension не смог найти резервацию пользователя (автологин в резервацию), данная ситуация, возможно, требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>Timed out</td>
            <td lab="standardErrors6">Что-то пошло не так при работе extension'a или аккаунт проверялся слишком долго, данная ситуация требует починки</td>
        </tr>
        <tr>
            <td class='titleProvStat'>We could not recognize captcha. Please try again later.</td>
            <td lab="standardErrors7">При автологине в форме авторизации юзеру была показана капча и мы не смогли ее распознать</td>
        </tr>
        <tr>
            <td class='titleProvStat'>AccountIDs</td>
            <td lab="AccountIDs">Посмотреть примеры аккаунтов с данной ошибкой</td>
        </tr>
    </table>
</div>

<script type="text/javascript">
    $("#mainTable").find('a[rel], td[rel]').hover(
        function(event){
            if (typeof($(this).attr('rel')) != 'undefined') {
                var label = $(this).attr('rel');
                var text = $('td[lab='+label+']').html();
                if($('#helper').length == 0){
                    $('body').append("<div id='helper'></div>");
                } else {
                    $('#helper').show();
                }
                var data = {
                    position:   'fixed',
                    left:       $('body').width()/2-250,
                    top:        100,
                    border:     '1px solid #ccc',
                    background: '#000',
                    color:      '#fff',
                    padding:    '10px',
                    width:      500
                };
                $('#helper').css(data).html(text);
            }
        },
        function(){
            $('#helper').hide();
        }
    );
    $("i.icon-blue-info").hover(
        function(event){
            if (typeof($(this).attr('rel')) != 'undefined') {
                var label = $(this).attr('rel');
                var text = $('td[lab='+label+']').html();
                if ($('#helper').length == 0) {
                    $('body').append("<div id='helper'></div>");
                } else {
                    $('#helper').show();
                }
                var data = {
                    position:   'fixed',
                    left:       $('body').width()/2-250,
                    top:        100,
                    border:     '1px solid #ccc',
                    background: '#000',
                    color:      '#fff',
                    padding:    '10px',
                    width:      500
                };
                $('#helper').css(data).html(text);
            }
        },
        function(){
            $('#helper').hide();
        }
    );
    function copy(e) {
        var url = new URL(window.location.href);
        var platform = url.searchParams.get("platform");
        var from = url.searchParams.get("from");
        var to = url.searchParams.get("to");
        var id = e.parentElement.previousElementSibling.children.item(0).getAttribute('href').match('ID=([0-9]+)')[1];
        var link = "https://awardwallet.com/manager/extensionStat.php?platform=" + platform + '&id=' + id;
        if (from && to)
            link += '&from=' + from + '&to=' + to;
        var input = document.createElement('input');
        document.body.appendChild(input);
        input.value = link;
        input.select();
        document.execCommand("copy");
    }
    function refreshPeriod(typeRefresh) {
        var url = new URL(window.location.href);
        var platform = url.searchParams.get("platform");
        var link = url.origin + "/manager/extensionStat.php?platform=" + platform;
        var id = url.searchParams.get("id");
        if (id!=null)
            link += '&id=' + id;
        var from = null;
        var to = null;
        switch (typeRefresh){
            case 0:
                from = document.querySelector('input[name="from"]').value;
                to = document.querySelector('input[name="to"]').value;
                break;
            case 1:
                from = new Date().toJSON().slice(0,10);
                to = from;
                break;
        }
        if (from && to)
            link += '&from=' + from + '&to=' + to;
        document.location.href = link;
    }
</script>
<?php
drawFooter();
