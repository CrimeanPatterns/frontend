<?php

use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\LoyaltyLocation;

require 'start.php';
drawHeader('Account property');
?>

<?php
$accountId = (int) ArrayVal($_GET, 'accountId', 0);
$listProviderKind = '';
$sel = isset($_GET['providerStatusKind']) ? (int) $_GET['providerStatusKind'] : (isset($_GET['providerCalcProps']) ? (int) $_GET['providerCalcProps'] : 0);

foreach ($arProviderKind as $key => $val) {
    $listProviderKind .= '<option value="' . $key . '"' . ($sel == $key ? ' selected="selected"' : '') . '>' . $val . '</option>';
}
$days = '<select class="selectTxt" name="days"><option value="">Days</option>
<option value="1" selected>1</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="30">30</option>
<option value="60">60</option>
<option value="90">90</option>
</select>';

$db = getSymfonyContainer()->get('doctrine')->getConnection();
$providers = $db->executeQuery('
    SELECT
        p.ProviderID, p.Code, p.DisplayName, p.Kind
    FROM
        Provider p
    WHERE
            p.State >= ?
    ORDER BY
            p.Code ASC
',
    [PROVIDER_ENABLED]
)->fetchAll();
?>
<h1>Search propertys of account</h1>
<table class="formTable" id="formTable" cellspacing="0" cellpadding="5" border="0">
    <tbody>
    <tr class="text">
        <td class="caption nowrap row"><label for="accountID">Account ID</label></td>
        <td class="input row">
            <form id="formAccount" class="editor_form" method="get" name="editor_form"><input class="inputTxt"
                                                                                              name="accountId"
                                                                                              id="accountID"
                                                                                              value="<?php echo empty($accountId) ? '' : $accountId; ?>"
                                                                                              type="text" required>
            </form>
        </td>
        <td>
            <button type="submit" data-form="formAccount">Search</button>
        </td>
        <td>
            <form id="formKibana" method="get" target="kibana">
                <select id="kibanaShowProvider">
                    <option value="">Kibana: Last list - OR - only provider</option>
                    <?php
                    $html = '';

for ($i = -1, $iCount = count($providers); ++$i < $iCount;) {
    $html .= '<option value="' . $providers[$i]['Code'] . '">' . $providers[$i]['Code'] . ' - ' . $providers[$i]['DisplayName'] . '</option>';
}
echo $html;
?>
                </select>
                <button type="submit" title="Вывод списка по дате попадания, либо по конкретному провайдеру">Show</button>
                <button class="js-provider-count" type="button" title="Показать кол-во неудачных аккаунтов по выбранному провайдеру за последние 7 дней">count</button>
            </form>
        </td>
    </tr>
    <tr class="text">
        <td class="caption nowrap row"><label for="providerStatusKind">Provider fetched status</label></td>
        <td class="input row">
            <form id="formStatus" class="editor_form" method="get" name="editor_form"><select id="providerStatusKind"
                                                                                              name="providerStatusKind"
                                                                                              class="selectTxt"><?php echo $listProviderKind; ?></select><?php echo $days; ?>
            </form>
        </td>
        <td>
            <button type="submit" data-form="formStatus">Show</button>
        </td>
        <td></td>
    </tr>
    <tr class="text">
        <td class="caption nowrap row"><label for="providerCalcProps">Provider calc properties</label></td>
        <td class="input row">
            <form id="formCalcProps" class="editor_form" method="get" name="editor_form"><select id="providerCalcProps"
                                                                                                 name="providerCalcProps"
                                                                                                 class="selectTxt"><?php echo $listProviderKind; ?></select><?php echo $days; ?>
            </form>
        </td>
        <td>
            <button type="submit" data-form="formCalcProps">Show</button>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>
<form class="editor_form" method="get" name="editor_form">
    <table class="formTable" cellspacing="0" cellpadding="5" border="0">
        <tbody>
        <tr class="text">
            <td class="input row">
                <select name="providerId">
                    <option value=""></option>
                    <?php
$providerId = (int) ArrayVal($_GET, 'providerId', 0);
$kind = array_fill_keys(array_keys($arProviderKind), '');

for ($i = -1, $iCount = count($providers); ++$i < $iCount;) {
    if (!array_key_exists($providers[$i]['Kind'], $kind)) {
        continue;
    }
    $kind[$providers[$i]['Kind']] .=
        '<option value="' . $providers[$i]['ProviderID'] . '"'
        . ($providerId == $providers[$i]['ProviderID'] ? ' selected="selected"' : '') . '>'
        . $providers[$i]['Code'] . ' - '
        . $providers[$i]['DisplayName'] . '</option>';
}
$html = '';

foreach ($kind as $k => $options) {
    $html .= '<optgroup label="' . $arProviderKind[$k] . '">' . $options . '</optgroup>';
}
echo $html;
?>
                </select>
                <input type="hidden" name="limit" value="15">
            </td>
            <td>
                <button type="submit">Search accounts</button>
            </td>
            <td><input type="text" name="login2" value="" placeholder="login2" style="border-width: 0;margin-left: 25px"></td>
        </tr>
        </tbody>
    </table>
</form><br>
<style type="text/css">
    #formTable td:last-child {
        padding-left: 4vw;
    }

    #contentBody {
        padding-bottom: 25px;
    }

    .selectTxt + .selectTxt {
        margin-left: 10px !important;
    }

    h2 a {
        display: inline-block;
        text-decoration: none !important;
        border-bottom: dashed #999 1px;
    }

    h2 a:hover {
        color: darkblue !important;
        border-bottom-style: solid;
    }

    .detailsTable {
        float: none;
        clear: both;
    }
</style>
<script type="text/javascript">
    $('button[data-form]', '#formTable').click(function() {
        $(this).closest('tr').find('#' + $(this).data('form')).submit();
    });
    var $formKibana = $('#formKibana');
    $('#kibanaShowProvider')
        .change(function() {
            var providerCode = $(this).val();
            if ('' == providerCode) {
                $formKibana.attr('action', 'https://kibana.awardwallet.com/app/kibana#/discover?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-7d,mode:quick,to:now))&_a=(columns:!(context.provider,context.accountId),filters:!(),index:%27logstash-*%27,interval:auto,query:(query_string:(analyze_wildcard:!t,query:%27%22Account%20EliteLevels%20error%22%27)),sort:!(%27@timestamp%27,desc),vis:(aggs:!((params:(field:context.accountId,orderBy:%272%27,size:20),schema:segment,type:terms),(id:%272%27,schema:metric,type:count)),type:histogram))&indexPattern=logstash-*&type=histogram');
            } else {
                $formKibana.attr('action', 'https://kibana.awardwallet.com/app/kibana#/discover?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-7d,mode:quick,to:now))&_a=(columns:!(context.provider,context.accountId),filters:!(),index:%27logstash-*%27,interval:auto,query:(query_string:(analyze_wildcard:!t,query:%27%22Account%20EliteLevels%20error%22%20AND%20context.provider:%22' + providerCode + '%22%27)),sort:!(%27@timestamp%27,desc),vis:(aggs:!((params:(field:context.provider,orderBy:%272%27,size:20),schema:segment,type:terms),(id:%272%27,schema:metric,type:count)),type:histogram))&indexPattern=logstash-*&type=histogram')
            }
        })
        .change()
	.next().click(function(){
            $(this).prev().change();
        });
    $('.js-provider-count', $formKibana).click(function() {
        var providerCode = $('#kibanaShowProvider').val();
        if ('' !== providerCode) {
            $formKibana.attr('action', 'https://kibana.awardwallet.com/app/kibana#/visualize/create?type=table&indexPattern=logstash-*&_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-7d,mode:quick,to:now))&_a=(filters:!(),linked:!f,query:(query_string:(analyze_wildcard:!t,query:%27%22Account%20EliteLevels%20error%22%20AND%20context.provider:%22' + providerCode + '%22%27)),uiState:(),vis:(aggs:!((id:%271%27,params:(),schema:metric,type:count)),listeners:(),params:(perPage:10,showMeticsAtAllLevels:!f,showPartialRows:!f),title:%27New%20Visualization%27,type:table))');
        } else {
            $formKibana.attr('action', 'https://kibana.awardwallet.com/app/kibana#/visualize/create?type=table&indexPattern=logstash-*&_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-7d,mode:quick,to:now))&_a=(filters:!(),linked:!f,query:(query_string:(analyze_wildcard:!t,query:%27%22Account%20EliteLevels%20error%22%27)),uiState:(),vis:(aggs:!((id:%271%27,params:(),schema:metric,type:count)),listeners:(),params:(perPage:10,showMeticsAtAllLevels:!f,showPartialRows:!f),title:%27New%20Visualization%27,type:table))');
        }
        $formKibana.submit();
    });
</script>

<?php
if (isset($_GET['providerStatusKind'])) {
    echo '<br>', statusProperty__v2();
} elseif (isset($_GET['providerCalcProps'])) {
    echo '<br>', statusProperty__v3();
} elseif (!empty($providerId)) {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 1;
    $limit > 100 || 0 > $limit ? $limit = 1 : null;

    $provider = $db->executeQuery('
        SELECT
            p.Name, p.Code, p.Kind, p.State
        FROM
            Provider p
        WHERE
            ProviderID = ?
        LIMIT 1
    ',
        [$providerId]
    )->fetch(\PDO::FETCH_OBJ);

    $accounts = $db->executeQuery('
        SELECT
            a.AccountID, a.UserID, a.Login, a.Login2, a.Login3, a.Region, a.SubAccounts, a.ErrorMessage, a.Balance, a.UpdateDate, a.TotalBalance, a.CheckedBy, a.ChangeCount, a.Itineraries, a.Disabled
        FROM
            Account a
        WHERE
                a.ProviderID  = ?
            ' . (empty($_GET['login2']) ? '' : 'AND Login2 = ?') . '
        ORDER BY a.UpdateDate DESC
        LIMIT ?
    ',
        \array_merge(
            [$providerId],
            empty($_GET['login2']) ? [] : [$_GET['login2']],
            [$limit]
        )
    )->fetchAll();

    $html = '';
    $html .= '<h2>Provider: <a href="/manager/edit.php?Schema=ProviderID=' . $providerId . '" target="provider">' . $provider->Code . ' <span>(' . $provider->Name . ') ↗</span> <sub style="font-size: 11px;display: inline-block">[' . $arProviderState[$provider->State] . ']</sub></a></h2>';
    $html .= '<table class="detailsTable">';
    $html .= '<thead><tr>';
    $html .= '<th>AccountID</th>';
    $html .= '<th>UserID</th>';
    $html .= '<th>Login</th>';
    $html .= '<th title="Login2">L2</th>';
    $html .= '<th title="Login3">L3</th>';
    $html .= '<th>Balance</th>';
    $html .= '<th></th>';
    $html .= '<th>Date update</th>';
    $html .= '<th title="Itineraries">It.</th>';
    $html .= '<th title="SubAccounts">Subs</th>';
    $html .= '<th>Checked</th>';
    $html .= '<th>Logs</th>';
    $html .= '<th>Error</th>';
    $html .= '</tr></thead><tbody>';

    for ($i = -1, $iCount = count($accounts); ++$i < $iCount;) {
        $checkedBy = \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_NAMES[$accounts[$i]['CheckedBy']] ?? $accounts[$i]['CheckedBy'] . ' ❌';

        $html .= '<tr' . (1 == $accounts[$i]['Disabled'] ? ' fetcherr="true" title="Account DISABLED"' : '') . '>';
        $html .= '<td><a href="/manager/accountProperty.php?accountId=' . $accounts[$i]['AccountID'] . '">' . $accounts[$i]['AccountID'] . '</a></td>';
        $html .= '<td>' . $accounts[$i]['UserID'] . '</td>';
        $html .= '<td>' . $accounts[$i]['Login'] . '</td>';
        $html .= '<td>' . $accounts[$i]['Login2'] . '</td>';
        $html .= '<td>' . $accounts[$i]['Login3'] . '</td>';
        $html .= '<td class="text-right" title="Total balance: ' . $accounts[$i]['TotalBalance'] . '&#013;ChangeCount: ' . $accounts[$i]['ChangeCount'] . '">' . $accounts[$i]['Balance'] . '</td>';
        $html .= '<td><a href="/manager/passwordVault/index.php?AccountID=' . $accounts[$i]['AccountID'] . '" target="password">🔒</a></td>';
        $html .= '<td>' . $accounts[$i]['UpdateDate'] . '</td>';
        $html .= '<td class="text-right">' . $accounts[$i]['Itineraries'] . '</td>';
        $html .= '<td class="text-right">' . $accounts[$i]['SubAccounts'] . '</td>';
        $html .= '<td>' . $checkedBy . '</td>';
        $html .= '<td><a href="/manager/loyalty/logs?AccountID=' . $accounts[$i]['AccountID'] . '&Partner=awardwallet" target="accountLog">show logs ↗</a></td>';
        $html .= '<td>' . $accounts[$i]['ErrorMessage'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    echo $html;
} elseif (!empty($accountId)) {
    $html = '';

    $account = $db->executeQuery('
        SELECT
            a.ProviderID, a.UserID, a.ErrorMessage, a.Balance, a.UpdateDate, a.TotalBalance, a.CheckedBy, a.ChangeCount, a.Itineraries, a.Disabled,
            p.Name, p.Code, p.Kind, p.State
        FROM
            Account a,
            Provider p,
            ProviderProperty pp
        WHERE
                a.AccountID  = ?
            AND a.ProviderID = p.ProviderID
        LIMIT 1
    ',
        [$accountId]
    )->fetch(\PDO::FETCH_OBJ);

    if (empty($account)) {
        exit('<h1>Not Found</h1>');
    }

    $html .= '<h2>Provider: <a href="/manager/edit.php?Schema=ProviderID=' . $account->ProviderID . '" target="provider">' . $account->Code . ' <span>(' . $account->Name . ') ↗</span></a></h2>';
    $html .= '<table class="detailsTable">';
    $html .= '<thead><tr>';
    $html .= '<th>AccountID</th>';
    $html .= '<th>UserID</th>';
    $html .= '<th>Balance</th>';
    $html .= '<th>Date update</th>';
    $html .= '<th>Itineraries</th>';
    $html .= '<th>Error</th>';
    $html .= '<th>Checked</th>';
    $html .= '<th>Logs</th>';
    $html .= '<th>State</th>';
    $html .= '</tr></thead><tbody><tr' . (1 == $account->Disabled ? ' fetcherr="true" title="Account DISABLED"' : '') . '>';
    $html .= '<td>' . $accountId . '</td>';
    $html .= '<td>' . $account->UserID . '</td>';
    $html .= '<td title="Total balance: ' . $account->TotalBalance . '&#013;ChangeCount: ' . $account->ChangeCount . '">' . $account->Balance . '</td>';
    $html .= '<td>' . $account->UpdateDate . '</td>';
    $html .= '<td class="text-right">' . $account->Itineraries . '</td>';
    $html .= '<td>' . $account->ErrorMessage . '</td>';
    $html .= '<td>' . \AwardWallet\MainBundle\Entity\Account::CHECKED_BY_NAMES[$account->CheckedBy] . '</td>';
    $html .= '<td><a href="/manager/loyalty/logs?AccountID=' . $accountId . '&Partner=awardwallet" target="accountLog">show logs ↗</a></td>';
    $html .= '<td>' . $arProviderState[$account->State] . '</td>';
    $html .= '</tr></tbody></table>';

    $accProps = $db->executeQuery('
        SELECT
            ap.ProviderPropertyID, ap.Val,
            pp.Name, pp.Code, pp.Required, pp.Visible, pp.Kind
        FROM
            AccountProperty ap,
            ProviderProperty pp
        WHERE
                ap.AccountID          = ?
            AND ap.ProviderPropertyID = pp.ProviderPropertyID
        ORDER BY
                pp.SortIndex ASC
    ',
        [$accountId]
    )->fetchAll();

    $normalizePropsId = array_column($accProps, 'ProviderPropertyID');

    if (!empty($normalizePropsId)) {
        $otherProps = $db->executeQuery('
            SELECT
                pp.ProviderPropertyID, pp.Name, pp.Code, pp.Required, pp.Visible, pp.Kind
            FROM
                ProviderProperty pp
            WHERE
                    pp.ProviderID          = ?
                AND pp.ProviderPropertyID NOT IN (?)
            ORDER BY
                    pp.SortIndex ASC
        ',
            [$account->ProviderID, $normalizePropsId],
            [\Doctrine\DBAL\ParameterType::INTEGER, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        )->fetchAll();

        if (!empty($otherProps)) {
            $accProps = array_merge($accProps, $otherProps);
        }
    }

    $ppIds = array_column($accProps, 'ProviderPropertyID');

    if (!empty($ppIds)) {
        $eliteLvls = $db->executeQuery('
            SELECT
                EliteLevelProgressID, ProviderPropertyID
            FROM
               EliteLevelProgress
            WHERE
                ProviderPropertyID IN (?)
        ',
            [$ppIds],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        )->fetchAll();
        empty($eliteLvls) ?: $eliteLvlPropId = array_column($eliteLvls, 'ProviderPropertyID');
    }
    !empty($eliteLvlPropId) ?: $eliteLvlPropId = [];

    $html .= '<h2>Account Property + <a href="/manager/list.php?Schema=ProviderProperty&ProviderID=' . $account->ProviderID . '" target="providerProperty">Provider Property ↗</a></h2>';
    $html .= '<table class="detailsTable"><thead><tr>';
    $html .= '<th title="ProviderPropertyID">ppID</th>';
    $html .= '<th>Code</th>';
    $html .= '<th>Name</th>';
    $html .= '<th>Val</th>';
    $html .= '<th title="Required">Req</th>';
    $html .= '<th title="Visible">Vis</th>';
    $html .= '<th>Kind</th>';
    $html .= '<th></th>';
    $html .= '</tr></thead><tbody>';

    for ($i = -1, $iCount = count($accProps); ++$i < $iCount;) {
        $inProcessLvl = array_search($accProps[$i]['ProviderPropertyID'], $eliteLvlPropId);
        $kind = array_key_exists($accProps[$i]['Kind'], $arPropertiesKinds) ? $arPropertiesKinds[$accProps[$i]['Kind']] : $arPropertiesKinds[0];
        $isStatusKind = PROPERTY_KIND_STATUS == $accProps[$i]['Kind'];
        !$isStatusKind ?: $propStatus = $accProps[$i];

        $val = $accProps[$i]['Val'] ?? 'ERROR: not collected';
        $rowAttr = [];

        if (false !== $inProcessLvl) {
            $rowAttr[] = 'class="eliteLevel-process" title="Field to calculate elite level //PROGRESS"';
        } elseif ($isStatusKind) {
            $rowAttr[] = 'title="Status Kind"';
        }

        if (!isset($accProps[$i]['Val'])) {
            $rowAttr[] = 'fetcherr="true" title="This property has not been collected for the account"';
        }

        $html .= '<tr ' . implode(' ', $rowAttr) . '>';
        $html .= '<td>' . $accProps[$i]['ProviderPropertyID'] . '</td>';
        $html .= '<td>' . $accProps[$i]['Code'] . '</td>';
        $html .= '<td>' . $accProps[$i]['Name'] . '</td>';
        $html .= '<td>' . $val . '</td>';
        $html .= '<td>' . (1 == $accProps[$i]['Required'] ? '✓' : '&mdash;') . '</td>';
        $html .= '<td>' . (1 == $accProps[$i]['Visible'] ? '✓' : '&mdash;') . '</td>';
        $html .= '<td>' . $kind . '</td>';
        $html .= '<td>' . (false !== $inProcessLvl ? '⬅' : ($isStatusKind ? '☆' : '')) . '</td>';
        $html .= '</tr>';

        if (false !== $inProcessLvl) {
            $process = $db->executeQuery('
                SELECT
                    elv.EliteLevelID, elv.Value,
                    el.Name
                FROM
                    EliteLevelValue elv,
                    EliteLevel el
                WHERE
                        elv.EliteLevelProgressID = ?
                    AND el.EliteLevelID          = elv.EliteLevelID
                ORDER BY el.Rank ASC, elv.Value ASC
            ',
                [$eliteLvls[$inProcessLvl]['EliteLevelProgressID']]
            )->fetchAll();

            if (!empty($process)) {
                $html .= '<tr>';
                $html .= '<td colspan="4"></td>';
                $html .= '<td colspan="4">';

                for ($j = -1, $jCount = count($process); ++$j < $jCount;) {
                    $html .= '<b>' . $process[$j]['Value'] . ': </b><span>' . $process[$j]['Name'] . '</span><br>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
    }
    $html .= '</tbody></table>';

    $eliteLevel = $db->executeQuery('
            SELECT
                el.EliteLevelID, el.Rank, el.ByDefault, el.Name,
                GROUP_CONCAT(txtel.ValueText SEPARATOR \', \') as ValuesText
            FROM
               EliteLevel el,
               TextEliteLevel txtel
            WHERE
                    el.ProviderID   = ?
                AND el.EliteLevelID = txtel.EliteLevelID
            GROUP BY el.EliteLevelID
            ORDER BY el.Rank ASC
        ',
        [$account->ProviderID],
    )->fetchAll();

    $html .= '<div style="display: inline-block"><h2 style="margin-bottom: 0">Provider <a href="/manager/list.php?Schema=EliteLevel&ProviderID=' . $account->ProviderID . '" target="eliteLevel">Elite Level ↗</a> <a href="/manager/list.php?Schema=EliteLevelProgress&Provider=' . $account->ProviderID . '" target="eliteLevel" style="float: right">Elite Level Progress ↗</a></h2>';
    $html .= '<table class="detailsTable"><thead><tr>';
    $html .= '<th title="EliteLevelID">elID</th>';
    $html .= '<th>Name</th>';
    $html .= '<th>Rank</th>';
    $html .= '<th title="by Default">def</th>';
    $html .= '<th>Keywords</th>';
    $html .= '</tr></thead><tbody>';

    for ($i = -1, $iCount = count($eliteLevel); ++$i < $iCount;) {
        $html .= '<tr>';
        $html .= '<td>' . $eliteLevel[$i]['EliteLevelID'] . '</td>';
        $html .= '<td>' . $eliteLevel[$i]['Name'] . '</td>';
        $html .= '<td>' . $eliteLevel[$i]['Rank'] . '</td>';
        $html .= '<td>' . (1 == $eliteLevel[$i]['ByDefault'] ? '✓' : '&mdash;') . '</td>';
        $html .= '<td>' . $eliteLevel[$i]['ValuesText'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';

    if (!empty($propStatus)) {
        $list = new \AwardWallet\MainBundle\Globals\AccountList\AccountList(
            getSymfonyContainer()->get('doctrine.orm.entity_manager'),
            getSymfonyContainer()->get(LoyaltyLocation::class),
            getSymfonyContainer()->get('AwardWallet\MainBundle\Service\ProviderPhoneResolver'),
            getSymfonyContainer()->get(GeoLocation::class)
        );

        $info = getSymfonyContainer()->get(\AwardWallet\MainBundle\Manager\AccountListManager::class)
            ->getAccount(
                getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\AccountList\OptionsFactory::class)
                    ->createDefaultOptions()
                    ->set(
                        \AwardWallet\MainBundle\Globals\AccountList\Options::OPTION_USER,
                        getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($account->UserID)
                    ),
                $accountId
            );
        $account = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $drawer = getSymfonyContainer()->get('aw.elitelevel.drawer');

        $info['EliteLevelTab'] = $drawer->draw($accountId, $info);
    }

    if (isset($info['EliteLevelTab'])) {
        $html .= '
    <div id="accountDetails" class="ui-dialog account-popup " style="width: 800px;height: 400px;padding-top: 25px;">
        <div class="item"><div class="tabs-content" style="min-height: 295px;">' . $info['EliteLevelTab'] . '</div></div>
    </div>';
    }
    echo $html;
}
?>

<style type="text/css">
    h2 span {
        font-size: 1.5rem;
        color: #333;
    }

    .detailsTable tbody tr:hover {
        background: #ccc;
    }

    td[rowspan] {
        vertical-align: middle;
    }

    .eliteLevel-process {
        background: #eee;
    }

    tr[fetcherr] {
        background: darkred !important;
    }

    tr[fetcherr] td,
    tr[fetcherr] td a,
    tr[fetcherr] td a:visited {
        color: #fff;
    }

    tr[fetcherr] td:nth-child(4) {
        color: #fff;
        font-weight: bold;
        text-align: center;
    }

    a[href*="/passwordVault/"] {
        font-size: .8rem;
        text-decoration: none;
    }

    .text-right {
        text-align: right !important;
    }

    .detailsTable {
        background: #fff;
    }

    .detailsTable th {
        padding: 10px 15px;
    }

    .detailsTable td {
        padding: 5px 10px;
        border: solid 1px #aaa;
    }

    .reached {
        min-width: 50px;
        background: url(/images/levels/reached1.png) 0% 0% / 100% 100% no-repeat;
    }

    .account-popup table.elite_stats {
        border-spacing: 0;
        width: 100%;
    }

    .account-popup table.elite_chart {
        margin-top: 10px;
        width: 100% !important;
    }

    .account-popup table.elite_stats td.elite_stats {
        color: #535457;
        font-size: 13px;
        padding: 8px 0 8px 10px;
    }

    .account-popup div.elite_level_name {
        text-align: right;
        vertical-align: bottom;
        font-size: 8pt;
        padding-right: 2px;
        padding-bottom: 2px;
        white-space: nowrap;
    }

    .account-popup .eliteLevelInfo {
        float: right;
        position: relative;
        left: 3px;
    }

    .account-popup .eliteLevelInfo:hover {
        -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
        filter: alpha(opacity=50);
        opacity: 0.5;
    }

    .account-popup .eliteCommentText {
        word-wrap: break-word;
        font-size: 8pt;
    }

    .account-popup table.elite_chart,
    .account-popup table.elite_chart table {
        padding: 0;
        border-spacing: 1px 0;
    }

    .account-popup td.elite_progress_info {
        padding-bottom: 10px;
    }

    .account-popup span.elite_p_value {
        color: #b2d673;
    }

    .account-popup table.elite_chart td {
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
    }

    .account-popup table.elite_chart td.progress,
    .account-popup table.elite_chart td.reached,
    .account-popup table.elite_chart td.delimiter {
        border: #536379 solid 1px;
        padding: 0;
        font-size: 8pt;
    }

    .account-popup table.elite_chart td.skipped {
        padding: 1px 1px 0 0;
        font-size: 8pt;
    }

    .account-popup table.elite_chart td.reached {
        position: relative;
        background-color: #dee6f1;
        height: 30px;
    }

    .account-popup table.elite_chart .progress {
        background: url('../../assets/awardwalletnewdesign/img/levels/progress.png') no-repeat;
        background-size: 100% 100%;
    }

    .account-popup table.elite_chart .delimiter {
        background: url('../../assets/awardwalletnewdesign/img/levels/separator.png') no-repeat;
        background-size: 100% 100%;
    }

    .account-popup table.elite_chart .group {
        background: url('../../assets/awardwalletnewdesign/img/levels/triangle.png') no-repeat;
        background-size: 100% 100%;
    }

    .account-popup table.elite_chart td.delimiter {
        background-color: #708096;
        padding: 0;
        width: 9px;
    }

    .account-popup table.elite_chart td.group {
        padding: 0;
        width: 10px;
    }

    .account-popup table.elite_chart td.group img {
        margin-left: -1px;
    }

    .account-popup table.elite_chart td.progress {
        background-color: #d3dbe6;
    }

    .account-popup table.elite_chart td.empty {
        padding: 0;
        border: #C4C4C4 solid 1px;
        background-color: #E9EAEA;
        font-size: 8pt;
    }

    .account-popup td.space {
        padding: 0;
        width: 1px;
    }

    .account-popup table.elite_chart td.elite_comment {
        color: #bdbdbd;
        font-size: 8pt;
        padding: 0 4px 0 4px;
        white-space: nowrap;
    }

    .account-popup .barContainter,
    .account-popup .eliteComment {
        width: 100%;
    }

    .account-popup table.elite_chart .hasGroup {
        border-color: #C4C4C4;
        border-style: solid;
        border-width: 0 1px;
        padding-right: 2px;
        padding-left: 2px;
    }

    .account-popup table.elite_chart td.hasGroup.firstGrouped {
        padding-top: 2px;
        border-top: 1px solid #C4C4C4;
    }

    .account-popup table.elite_chart td.hasGroup.lastGrouped {
        border-bottom: 1px solid #C4C4C4;
    }

    .account-popup .barContainter div,
    .account-popup .eliteComment div {
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        font-size: 8pt;
    }

    .account-popup .internal {
        padding: 0;
        float: left;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .account-popup .internal.progress {
        border: #536379 solid 1px;
        background-color: #d3dbe6;
        color: white;
        position: relative;
        text-align: right;
    }

    .account-popup .internal.progress .progressValue {
        padding-left: 1px;
        display: inline-block;
        top: 2px;
    }

    .account-popup .internal.empty {
        border: #C4C4C4 solid 1px;
        background-color: #E9EAEA;
        color: #536379;
    }

    .account-popup .internal.empty .needed {
        padding-left: 1px;
        display: inline-block;
        height: 100%;
    }

    .account-popup .internal.empty,
    .account-popup .internal.progress,
    .account-popup .reached {
        min-height: 16px;
    }

    .account-popup .eliteComment {
        color: #bdbdbd;
        display: table;
    }

    .account-popup .eliteComment div {
        display: table-cell;
        width: auto;
        padding-top: 2px;
        padding-bottom: 2px;
    }

    .account-popup .eliteComment .propertyName {
        text-align: left;
        padding-left: 4px;
    }

    .account-popup .eliteComment .levelGoal {
        padding-right: 4px;
        text-align: right;
    }

    .account-popup table.elite_chart .andorContainer {
        text-align: center;
    }

    .account-popup table.elite_chart .andorContainer .andor {
        display: inline-block;
        border: 1px solid #C4C4C4;
        background-color: #ECEDED;
        margin: 3px 0;
        padding: 1px 4px;
        font-size: 8pt;
        color: #89896E;
    }

    .account-popup [data-pane-id] > div {
        padding: 20px;
    }

    .account-popup [data-pane-id="details"] > div {
        padding: 10px 0 0 0;
    }

    .account-popup [data-pane-id="creditcard"] > div {
        padding-bottom: 5px !important;
    }

    .account-popup [data-pane-id=comments] div {
        color: #535457;
        font-size: 13px;
        line-height: 1.2;
    }

    .account-popup [data-pane-id=history] table.main-table {
        margin-top: 0;
        table-layout: auto;
    }

    .account-popup [data-pane-id=history] table.main-table td,
    .account-popup [data-pane-id=history] table.main-table th {
        text-align: left;
    }

    .account-popup [data-pane-id=history] table.main-table td:first-child,
    .account-popup [data-pane-id=history] table.main-table th:first-child {
        padding-left: 15px;
    }

    .account-popup [data-pane-id=history] table.main-table td:last-child,
    .account-popup [data-pane-id=history] table.main-table th:last-child {
        padding-right: 15px;
    }

    .account-popup [data-pane-id=history] table.main-table tr.green td {
        color: #00a67c;
    }

    .account-popup [data-pane-id=history] table.main-table tr.blue td {
        color: #4684c4;
    }

    .account-popup [data-pane-id="chart"] a {
        color: #4684c4;
        font-size: 13px;
        text-decoration: none;
    }

    .account-popup [data-pane-id="chart"] a:hover {
        text-decoration: underline;
    }

    .account-popup [data-pane-id="totals"] .main-table {
        border-top: none;
        margin-top: 0;
    }

    .account-popup [data-pane-id="totals"] .main-table th,
    .account-popup [data-pane-id="totals"] .main-table td {
        text-align: left;
    }

    .account-popup [data-pane-id="totals"] .main-table tr:last-child td {
        border-bottom: 1px dotted #8e9199;
    }

    .account-popup [data-pane-id="totals"] .main-table p.big {
        font-size: 15px;
    }

    .account-popup [data-pane-id="totals"] .main-table .silver {
        font-size: 13px;
        color: #8e9199;
    }

    .update-account .progress-bar {
        margin: 10px auto;
    }

    .update-account .content-blk {
        padding: 20px;
    }

    .update-account .title {
        text-align: center;
    }

    .update-account .title p {
        display: inline-block;
        font-size: 13px;
        color: #5c6373;
        vertical-align: middle;
        margin-left: 10px;
        white-space: nowrap;
    }

    .update-account .title p span {
        font-family: 'open_sansbold';
    }

    .update-account table.main-table {
        border-top: none;
        margin: 20px auto;
        width: 450px;
    }

    .update-account table.main-table td {
        font-size: 13px;
        color: #828999;
    }

</style>
<?php
drawFooter();

function statusProperty__v2()
{
    $connection = getSymfonyContainer()->get('doctrine')->getConnection();
    $kind = isset($_GET['providerStatusKind']) ? (int) $_GET['providerStatusKind'] : 1;
    $day = isset($_GET['days']) ? (int) $_GET['days'] : 1;
    $day > 100 || 0 > $day ? $day = 1 : null;

    $providers = $connection->executeQuery('
        SELECT
            p.ProviderID, p.Code, p.DisplayName,
            pp.ProviderPropertyID
        FROM
            Provider p,
            ProviderProperty pp
        WHERE
                p.ProviderID = pp.ProviderID
            AND p.State     >= ?
		    AND p.Kind       = ?
		    AND pp.Kind      = 3
	    ORDER BY
		        p.Accounts DESC
    ',
        [PROVIDER_ENABLED, $kind],
    )->fetchAll();

    $html = '<table id="provEStatus" class="detailsTable">';
    $html .= '<thead><tr>';
    $html .= '<th>Provider</th>';
    $html .= '<th>Fetched status</th>';
    $html .= '<th>Account counts</th>';
    $html .= '<th>Rank</th>';
    $html .= '</tr></thead><tbody>';
    $output = [];

    for ($i = -1, $iCount = count($providers); ++$i < $iCount;) {
        $eliteStatus = $connection->executeQuery(
            'SELECT EliteLevelID, Name, `Rank` FROM EliteLevel WHERE ProviderID = ? ORDER BY `Rank` ASC',
            [$providers[$i]['ProviderID']]
        )
        ->fetchAll();
        $accountStatus = $connection->executeQuery('
            SELECT
                ap.Val, COUNT(*) as _countAccounts 
            FROM
                AccountProperty ap,
                Account a
            WHERE
                    ap.ProviderPropertyID = ?
                AND a.AccountID           = ap.AccountID
                ' . (0 === $day ? '' : 'AND a.UpdateDate          > ADDDATE(NOW(), INTERVAL ? DAY)') . '
            GROUP BY ap.Val 
            ORDER BY _countAccounts DESC
        ',
            \array_merge(
                [$providers[$i]['ProviderPropertyID']],
                0 === $day ? [] : [-$day]
            )
        )->fetchAll();

        $output[$providers[$i]['ProviderID']] = [];

        $html .= '<tr class="provider prov-' . $providers[$i]['Code'] . '">';
        $html .= '<td class="provider-name"><a href="/manager/edit.php?ID=' . $providers[$i]['ProviderID'] . '&Schema=Provider">' . $providers[$i]['DisplayName'] . '</a></td>';
        $html .= '<td><a class="js-prov-status" href="#" data-provider="' . $providers[$i]['Code'] . '" title="Show/hide all fetched status">✍ ' . count($accountStatus) . '</a></td>';
        $html .= '<td colspan="2" class="elite-status-count"><i>db <a href="/manager/list.php?ProviderID=' . $providers[$i]['ProviderID'] . '&Schema=EliteLevel">elite status count: <b>' . count($eliteStatus) . '</b></a></i></td>';
        $html .= '</tr>';

        for ($j = -1, $jCount = count($accountStatus); ++$j < $jCount;) {
            $statHtml = '';
            $statusInPos = $statusFound = false;

            for ($m = -1, $mCount = count($eliteStatus); ++$m < $mCount;) {
                $eliteStatusText = $connection->executeQuery(
                    'SELECT ValueText FROM TextEliteLevel WHERE EliteLevelID = ?',
                    [$eliteStatus[$m]['EliteLevelID']]
                )->fetchAll();

                $accountStatus[$j]['Val'] = trim($accountStatus[$j]['Val']);
                $eliteStatus[$m]['Name'] = trim($eliteStatus[$m]['Name']);

                for ($n = -1, $nCount = count($eliteStatusText); ++$n < $nCount;) {
                    if (false !== mb_stripos($accountStatus[$j]['Val'], $eliteStatusText[$n]['ValueText'])) {
                        $accountStatus[$j]['Val'] = str_ireplace($eliteStatusText[$n]['ValueText'], '<u class="mark">' . $eliteStatusText[$n]['ValueText'] . '</u>', $accountStatus[$j]['Val']);
                        $statHtml = '<td>' . $eliteStatus[$m]['Rank'] . '</td>';
                        $eliteStatus[$m]['_checked'] = $statusFound = true;

                        false === array_search($eliteStatus[$m]['Name'], $output[$providers[$i]['ProviderID']])
                            ? $output[$providers[$i]['ProviderID']][] = $eliteStatus[$m]['Name']
                            : $statusInPos = true;

                        break;
                    }
                }

                if (!$statusFound) {
                    if ($accountStatus[$j]['Val'] == $eliteStatus[$m]['Name'] || mb_strtolower($accountStatus[$j]['Val']) == mb_strtolower($eliteStatus[$m]['Name'])) {
                        $statHtml = '<td>' . $eliteStatus[$m]['Rank'] . '</td>';
                        $eliteStatus[$m]['_checked'] = $statusFound = true;

                        false === array_search($eliteStatus[$m]['Name'], $output[$providers[$i]['ProviderID']])
                            ? $output[$providers[$i]['ProviderID']][] = $eliteStatus[$m]['Name']
                            : $statusInPos = true;

                        break;
                    } elseif (false !== mb_stripos($accountStatus[$j]['Val'], $eliteStatus[$m]['Name'])) {
                        $statHtml = '<td>#' . $eliteStatus[$m]['Rank'] . '</td>';
                        $eliteStatus[$m]['_checked'] = $statusFound = $statusInPos = true;

                        false === array_search($eliteStatus[$m]['Name'], $output[$providers[$i]['ProviderID']])
                            ? $output[$providers[$i]['ProviderID']][] = $eliteStatus[$m]['Name']
                            : $statusInPos = true;

                        break;
                    }
                }
            }

            if (!$statusFound) {
                $statusInPos = false;
                $statHtml = '<td class="status-notfound">' . $accountStatus[$j]['Val'] . '</td>';
            }

            $html .= '<tr' . ($statusInPos ? ' class="acc-es acc-es-' . $providers[$i]['Code'] . '"' : '') . '>';
            $html .= '<td></td>';
            $html .= '<td>' . $accountStatus[$j]['Val'] . '</td>';
            $html .= '<td>' . $accountStatus[$j]['_countAccounts'] . '</td>';
            $html .= $statHtml;
            $html .= '</tr>';
        }

        for ($m = -1, $mCount = count($eliteStatus); ++$m < $mCount;) {
            if (empty($eliteStatus[$m]['_checked'])) {
                $html .= '<tr class="status-none">';
                $html .= '<td>not founded in account status</td>';
                $html .= '<td colspan="2">' . $eliteStatus[$m]['Name'] . '</td>';
                $html .= '<td>' . $eliteStatus[$m]['Rank'] . '</td>';
                $html .= '</tr>';
            }
        }
    }
    $html .= '</tbody></table><br>';

    $html .= 'В колонке Fetched status рядом с иконкой <b style="font-size: 18px">✍</b> отображено количество вариантов "статуса" полученных для данного провайдера, значений может быть больше ожидаемого, т.к. часть из них обрабатывается по вхожденям на основе ключевых слов. При нажатии будет раскрыт полный список всех статусов которые парсер собрал с сайта провайдера.<br>';
    $html .= '<b>db elite status count: #</b> - здесь отображено кол-во статусов заполненных в базе, которые отображаются непосредственно на сайте awardwallet.com<br>';
    $html .= '<b>not founded in account status</b> - данная строка говорит о том, что в полученных статусах с сайта провайдера мы не смогли сопоставить данный статус с базой awardwallet.com, это говорит о том, что либо появился новый статус, либо необходимо добавить ключевое слово к нему и связать с уже имеющимся, либо свойство собралось с ошибкой<br>Важный момент, что данный статус возможно привелегированный и за последнее время ни для какого из аккаунтов такой получить просто не удалось.';

    $html .= <<<CSS
<style type="text/css">
.detailsTable {
    background: #fff;
}
.detailsTable th {
    padding: 10px 15px;
}
.detailsTable td {
    padding: 5px 10px;
    border: solid 1px #aaa;
}
tr.provider {
    background: #eee;
}
.provider-name {
    font-size: 1.2rem;
}
.elite-status-count {
    text-align: right !important;
}
.status-notfound {
    background: red;
    color: #fff;
}
tr.status-none td {
    padding: 2px 10px;
    
}
tr.status-none td:nth-child(1) {
    text-align: right !important;
}
tr.status-none td:nth-child(2),
tr.status-none td:nth-child(3) {
    background: #bd9f9f;
    color: #fff;
}
.acc-es {
    display: none;
}
.js-prov-status {
    float: right;
}
u.mark {
    display: inline-block;
    text-decoration: none;
    border-bottom: dotted 1px #aaa;
}
</style>
CSS;

    $html .= <<<JS
<script type="text/javascript">
$('a.js-prov-status', '#provEStatus').click(function(){
    var tr = $('tr.acc-es-' + $(this).data('provider'));
    tr.is(':visible') ? tr.hide() : tr.show(); 
    return false;
});
</script>
JS;

    return $html;
}

function statusProperty__v3()
{
    $connection = getSymfonyContainer()->get('doctrine')->getConnection();
    $kind = isset($_GET['providerCalcProps']) ? (int) $_GET['providerCalcProps'] : 1;
    $day = isset($_GET['days']) ? (int) $_GET['days'] : 1;
    $day > 100 || 0 > $day ? $day = 1 : null;

    $providers = $connection->executeQuery('
        SELECT
            p.ProviderID, p.Code, p.DisplayName
        FROM
            Provider p
        WHERE
                p.State     >= ?
		    AND p.Kind       = ?
	    ORDER BY
		        p.Accounts DESC
    ',
        [PROVIDER_ENABLED, $kind]
    )->fetchAll();

    $html = '<table id="provEStatus" class="detailsTable">';
    $html .= '<thead><tr>';
    $html .= '<th>Provider</th>';
    $html .= '<th>Field</th>';
    $html .= '<th title="Count accounts with property">Vals count</th>';
    $html .= '<th title="Count accounts with number in property">Vals num count</th>';
    $html .= '<th title="difference between the number of properties">diff %</th>';
    $html .= '</tr></thead><tbody>';

    for ($i = -1, $iCount = count($providers); ++$i < $iCount;) {
        $processFields = $connection->executeQuery('
            SELECT
                    pp.ProviderPropertyID, pp.Name, pp.Code,
                    elp.EliteLevelProgressID
            FROM
                    ProviderProperty pp,
                    EliteLevelProgress elp
            WHERE
                    pp.ProviderID        = ?
                AND pp.ProviderPropertyID = elp.ProviderPropertyID
        ',
            [$providers[$i]['ProviderID']]
        )->fetchAll();

        $html .= '<tr>';
        $html .= '<td colspan="2"><a href="/manager/edit.php?Schema=Provider&ID=' . $providers[$i]['ProviderID'] . '">' . $providers[$i]['DisplayName'] . '</a></td>';
        $html .= '<td class="elite-fields" colspan="3"><a href="/manager/list.php?Schema=EliteLevelProgress&Provider=' . $providers[$i]['ProviderID'] . '">Elite level progress</a></td>';
        $html .= '</tr>';

        for ($j = -1, $jCount = count($processFields); ++$j < $jCount;) {
            $fieldState = $connection->executeQuery('
                SELECT
                       COUNT(*) as _countAll,
                       SUM(CASE WHEN ap.Val REGEXP \'[0-9]+$\' THEN 1 ELSE 0 END) _countNum
                       -- SUM(CASE WHEN ap.Val REGEXP \'^[0-9]+$\' THEN 1 ELSE 0 END) _countNum
                FROM
                        Account a,
                        AccountProperty ap
                WHERE
                        a.AccountID           = ap.AccountID
                    AND ap.ProviderPropertyID = ?
                    ' . (0 === $day ? '' : 'AND a.UpdateDate          > ADDDATE(NOW(), INTERVAL ? DAY)') . '
            ',
                \array_merge(
                    [$processFields[$j]['ProviderPropertyID']],
                    0 === $day ? [] : [-$day]
                ),
            )->fetch(\PDO::FETCH_OBJ);

            $fieldState->_countAll = (int) $fieldState->_countAll;
            $fieldState->_countNum = (int) $fieldState->_countNum;
            $diff = '';

            if ($fieldState->_countAll > 0) {
                $diff = round(100 - (100 * $fieldState->_countNum / $fieldState->_countAll), 2);
            }
            $bad = empty($diff) ? false : $diff > 15;

            $html .= '<tr' . ($bad ? ' class="bad-vals"' : '') . '>';
            $html .= '<td></td>';
            $html .= '<td>' . $processFields[$j]['Name'] . ' - ' . $processFields[$j]['Code'] . '</td>';
            $html .= '<td>' . $fieldState->_countAll . '</td>';
            $html .= '<td>' . $fieldState->_countNum . '</td>';
            $html .= '<td>' . $diff . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table><br>';

    $html .= '<b>Vals count </b> - в этой колонке отображено общее количество аккаунтов имеющие собранное значение из колонки <u>Field</u><br>';
    $html .= '<b>Vals num count </b> - в данной колонке выводится количество аккаунтов значения которых мы в состоянии посчитать, т.е. имеют в себе числовое значение<br>';
    $html .= '<b>diff % </b> - разница, между общим количеством свойств аккаунтов и общим количеством аккаунтов собранные свойства которых мы можем использовать в расчётах, 
 если разница межу ними составляет более 15% - будет подсвечено красным, даже если свойство будет в красной зоне, это не показатель что у многих не будет выведен элитный статус, т.к. у некоторых провайдеров расчёт идет по нескольким полям, 
 и если не будет значения в одном, скорее всего будет высчитано значение по другому.<br>';

    $html .= <<<CSS
<style type="text/css">
.detailsTable {
    background: #fff;
}
.detailsTable th {
    padding: 10px 15px;
}
.detailsTable td {
    padding: 5px 10px;
    border: solid 1px #aaa;
}
tr.bad-vals td {
    background: red;
    color: #fff;
}
.elite-fields {
    text-align: right !important;
    font-style: italic;
}
</style>
CSS;

    return $html;
}

?>
