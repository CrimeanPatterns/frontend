<?php

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;

$schema = "voteMailer";

require "start.php";

require_once "../kernel/TForm.php";

require_once "../schema/Provider.php";

global $Connection;

$typeMailer = ArrayVal($_GET, 'type', 'fixed');

if ($typeMailer == 'fixed') {
    $sTitle = "Mailer for broken program";
} elseif ($typeMailer == 'added') {
    $sTitle = "Mailer for add program";
} elseif ($typeMailer == 'clear') {
    $sTitle = "Clear counter broken program";
} else {
    $sTitle = "Vote mailer";
}

$arMessages = [
    'fixed' => '{userName}, our records indicate that you told us that {displayName} was not working. We looked into this issue and got it resolved. <br/>
Please try updating your balance on {displayName} via:
<p><a href="https://awardwallet.com/account/list.php">https://awardwallet.com/account/list.php</a></p>
If you still see a problem with this program please let us know via:
<p><a href="https://awardwallet.com/contact">https://awardwallet.com/contact</a></p>',
    'added' => '{userName}, our records indicate that you expressed interest in us adding support for {displayName} program. <br/>
We looked into it and got it implemented. Please try adding this program using this link:
<p><a href="https://awardwallet.com/account/edit.php?ProviderID={providerID}&ID=0&UserAgentID=0">https://awardwallet.com/account/edit.php?ProviderID={providerID}&ID=0&UserAgentID=0</a></p>
If you see a problem with this program please let us know via:
<p><a href="https://awardwallet.com/contact">https://awardwallet.com/contact</a></p>',
];

$isPost = isset($_POST['post']) ? true : false;

if (!$isPost) {
    drawHeader($sTitle);
    echo '<p><a href="voteMailer.php?type=fixed">Mailer for support program by type</a> | <a href="voteMailer.php?type=clear">Clear counter broken program</a></p>';
}

if (isset($_GET['Action']) && isset($_GET['ID'])) {
    $action = ArrayVal($_GET, 'Action');
    $providerID = intval(ArrayVal($_GET, 'ID', 0));

    if (isset($_GET['DontSendEmails']) && ($_GET['DontSendEmails'] == '1')) {
        $dontSendEmails = 1;
    } else {
        $dontSendEmails = 0;
    }

    if ($providerID == 0) {
        echo '<p>Provider ID not correct</p>';
    } else {
        // adding User IDs to mailer list
        if (!empty($_GET['addUserID'])) {
            $userIDs = $_GET['addUserID'];
        } elseif (!empty($_POST['UserIDs'])) {
            $userIDs = $_POST['UserIDs'];
        } else {
            $userIDs = null;
        }

        if (!empty($userIDs)) {
            preg_match_all('/([0-9]+)/', $userIDs, $additionalUserIDs, PREG_SET_ORDER);

            foreach ($additionalUserIDs as $userID) {
                $userID = intval($userID[0]);
                $q = new TQuery("select 1 from Usr where UserID = " . $userID);

                if (!$q->EOF) {
                    $Connection->Execute("INSERT INTO ProviderVote (ProviderID, UserID, VoteDate)
										VALUES ({$providerID}, {$userID}, now())
										ON DUPLICATE KEY UPDATE VoteDate = now()");
                }
            }
        }

        $notSet = (isset($_GET['state']) && 'notset' == $_GET['state']) || !empty($_GET['showEnabled']);

        if ($action == 'fixed') {
            $provider = $Connection->Execute('SELECT StatePrev FROM Provider WHERE ProviderID = ' . $providerID)->fetch();
            $providerState = $provider['StatePrev'];

            if (!$notSet) {
                if (empty($provider['StatePrev'])) {
                    exit('Something is wrong, StatePrev is EMPTY');
                }

                if (!$dontSendEmails) {
                    getSymfonyContainer()->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')->emailShouldSendUsers($providerID, $action);
                }
                $Connection->Execute("UPDATE Provider SET State = $providerState, StatePrev = NULL, Assignee=null WHERE ProviderID=" . $providerID);
                // sync Database
                startDatabaseUpdate($providerID, $providerState, $dontSendEmails);
            } elseif (!$dontSendEmails) {
                $result = getSymfonyContainer()
                    ->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')
                    ->sendVoted($providerID, $action);
                echo implode('<br>', $result);
            }
        } elseif ($action == 'broken') {
            $provider = $Connection->Execute('SELECT State, StatePrev FROM Provider WHERE ProviderID = ' . $providerID)->fetch();

            if (!empty($provider['StatePrev'])) {
                exit('Something is wrong, StatePrev in db is already defined');
            }

            getSymfonyContainer()->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')->emailShouldSendUsers($providerID, $action);

            $providerState = PROVIDER_FIXING;
            $Connection->Execute("UPDATE Provider SET State = $providerState, StatePrev = " . $provider['State'] . " WHERE ProviderID=" . $providerID);
            // sync Database
            startDatabaseUpdate($providerID, $providerState, 0);

            echo '<p>ProviderID ' . $providerID . ' moved to broken</p>';
        } elseif ($action == 'added') {
            if (!$notSet) {
                if (!$dontSendEmails) {
                    getSymfonyContainer()->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')->emailShouldSendUsers($providerID, $action);
                }
                $providerState = PROVIDER_ENABLED;
                $Connection->Execute("UPDATE Provider SET State = $providerState, CollectingRequests = 0, StatePrev = NULL, EnableDate = IF(EnableDate IS NULL, NOW(), EnableDate) WHERE ProviderID=" . $providerID);
                // sync Database
                startDatabaseUpdate($providerID, $providerState, $dontSendEmails);
            } elseif (!$dontSendEmails) {
                $result = getSymfonyContainer()
                    ->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')
                    ->sendVoted($providerID, 'added');
                echo implode('<br>', $result);
            }
        } elseif ('clear' == $action) {
            $providerID = intval(ArrayVal($_GET, 'ID', 0));

            if ($providerID) {
                $Connection->Execute('DELETE FROM ProviderVote WHERE ProviderID=' . $providerID);
                $prov = $Connection->Execute('SELECT p.DisplayName FROM Provider p WHERE p.ProviderID = ' . $providerID . ' LIMIT 1')->fetchColumn();
                echo '<p>Provider counter for “<a href="/manager/edit.php?ID=' . $providerID . '&Schema=Provider" target="_blank">' . $prov . '</a>” cleaned</p>';
            } else {
                DieTrace('Provider error (' . $provider . ')');
            }
        }
    }
} elseif (isset($_GET['type'])) {
    if ($typeMailer == 'clear') {
        $where = 'p.State IN (' . implode(',', [PROVIDER_ENABLED, PROVIDER_CHECKING_OFF, PROVIDER_CHECKING_WITH_MAILBOX, PROVIDER_CHECKING_EXTENSION_ONLY]) . ')';
        $form = new TForm([
            "Provider" => [
                "Caption" => "Program name",
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => ['0' => 'Select provider'] + SQLToArray("SELECT ProviderID, concat_ws(' - ', DisplayName, Cnt) as DisplayName2 from ( SELECT p.DisplayName, COUNT(*) as Cnt, p.ProviderID FROM Provider p, ProviderVote pv WHERE " . $where . " AND p.ProviderID=pv.ProviderID GROUP BY p.ProviderID, p.DisplayName ) a", "ProviderID", "DisplayName2"),
            ],
        ]);
        $form->SubmitButtonCaption = "Clear";

        if ($form->IsPost) {
            $provider = intval(ArrayVal($_POST, 'Provider', 0));

            if ($provider <= 0) {
                DieTrace('Provider error (' . $provider . ')');
            }

            $Connection->Execute('DELETE FROM ProviderVote WHERE ProviderID=' . $provider);
            $prov = $Connection->Execute('SELECT p.DisplayName FROM Provider p WHERE p.ProviderID = ' . $provider . ' LIMIT 1')->fetchColumn();
            echo '<p>Provider counter for “<a href="/manager/edit.php?ID=' . $provider . '&Schema=Provider" target="_blank">' . $prov . '</a>” cleaned</p>';
        }
    } else {
        $message = $arMessages[$typeMailer];

        if ($typeMailer == 'fixed') {
            $where = "State IN (" . PROVIDER_FIXING . ") ";
        } else {
            $where = "(State IN (" . PROVIDER_IN_DEVELOPMENT . ", " . PROVIDER_COLLECTING_ACCOUNTS . "," . PROVIDER_TEST . "," . PROVIDER_RETAIL . ") or p.CollectingRequests)";
        }

        if (!empty($_GET['showEnabled'])) {
            $where = 'State IN (' . implode(',', [PROVIDER_ENABLED, PROVIDER_CHECKING_OFF, PROVIDER_CHECKING_WITH_MAILBOX, PROVIDER_CHECKING_EXTENSION_ONLY]) . ')';
        }

        $form = new TForm([
            "Type" => [
                "Caption" => "Type",
                "Type" => "string",
                "Required" => true,
                "Value" => ArrayVal($_GET, 'type', 'fixed'),
                "Options" => [
                    'fixed' => 'Fixed program',
                    'added' => 'Added program',
                ],
            ],
            "Provider" => [
                "Caption" => "Program name",
                "Type" => "integer",
                "Required" => true,
                "Value" => intval(ArrayVal($_GET, 'ID', 0)),
                "Options" => ['0' => 'Select provider'] + SQLToArray("SELECT ProviderID, DisplayName FROM Provider p WHERE {$where} ORDER BY DisplayName", "ProviderID", "DisplayName"),
            ],
            "ShowEnabled" => [
                "Type" => "input",
                'InputType' => 'checkbox',
                "Caption" => "Show Enabled programs",
                "Note" => '',
                "Required" => false,
                "Size" => 120,
                "Value" => '',
                'InputAttributes' => ' ' . (!empty($_GET['showEnabled']) ? 'checked="checked"' : '') . ' ',
            ],
            "UserIDs" => [
                "Type" => "string",
                "Caption" => "User IDs",
                "Note" => "Here you may add User IDs that you would like to be in the Mailer list (e.g. 61266, 349414, 428036)",
                "Required" => false,
                "Size" => 120,
                "Value" => '',
            ],
            //			"Subject" => array(
            //				"Type" => "string",
            //				"Caption" => "Email subject",
            //				"Required" => true,
            //				"Size" => 120,
            //				"Value" => ($typeMailer == 'fixed' || $typeMailer == 'sendEmails') ? '{displayName} has been fixed.' : 'Support for {displayName} has been added.',
            //			),
            //			"Text" => array(
            //				"Type" => "string",
            //				"Caption" => "Email Text",
            //				"Required" => true,
            //				"Value" => $message,
            //				"InputType" => "textarea",
            //				"Cols" => 60,
            //				"Rows" => 15,
            //			),
        ]);

        $form->SubmitButtonCaption = "Send";

        if ($form->IsPost) {
            $providerID = intval(ArrayVal($_POST, 'Provider', 0));

            if ($providerID <= 0) {
                DieTrace('Provider error (' . $providerID . ')');
            }

            $result = getSymfonyContainer()
                ->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')
                ->sendVoted($providerID, ArrayVal($_POST, 'Type', 'fixed'));
            echo implode('<br>', $result);
        }
    }
    echo $form->HTML();
}

if (!$isPost) {
    echo "<script type='text/javascript'>
$(function() {
    // change form action
    window.onload = changeformAction();
    $('#fldProvider').on('change', function () {
        changeformAction();
    });
    function changeformAction () {
        var providerID = $('#fldProvider option:selected').attr('value');
        var form = $('form[name = editor_form]');
        form.attr('onsubmit', '');
        form.attr('action', '/manager/voteMailer.php?Action={$typeMailer}&ID=' + providerID + '&showEnabled=' + ($('#fldShowEnabled').is(':checked')? 1:0));
        
        $('#fldType,#fldShowEnabled').on('change', function(e) {
            var type = $('#fldType').val();
            location.href = '/manager/voteMailer.php?type=' + type + '&showEnabled=' + ($('#fldShowEnabled').is(':checked')? 1:0);
        });
    }
    // checking selected provider
	$('input[value = \"Send\"]').bind('click', function ( event ) {
	    var providerID = $('#fldProvider option:selected').attr('value');
        if (providerID <= 0) {
            alert('Please select a provider');
            $('#fldProvider').parent().append('<p id=\"errorMessage\" style=\"color:red; margin:0;padding:0;\">Please select a provider</p>');
            event.preventDefault();
        }
        else
            $('form#formTable').submit();
    });
});
</script>";
    drawFooter();
}

function startDatabaseUpdate($providerID, $providerState, $dontSendEmails)
{
    $postData = [];

    if (!$dontSendEmails && isset($_POST['Type']) && isset($_POST['Provider'])) {
        $postData = [
            'Type' => $_POST['Type'],
            'Provider' => $_POST['Provider'],
        ];
    }
    TProviderSchema::triggerDatabaseUpdate(['voteMailer' => $postData]);

    $task = new \AwardWallet\MainBundle\Loyalty\BackgroundCheck\AsyncTask($providerID);
    getSymfonyContainer()->get(\AwardWallet\MainBundle\Worker\AsyncProcess\Process::class)->execute($task);

    if (!empty($postData)
        && 'dev' === getSymfonyContainer()->getParameter('kernel.environment')) {
        // getSymfonyContainer()->get('AwardWallet\MainBundle\Command\ProviderTableSyncSuccessCommand')
        //    ->sendVoted($postData['Provider'], $postData['Type']);
    }
}
