<?php

use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;

$schema = "historySchedule";
require("../start.php");

$asyncProcess = getSymfonyContainer()->get(Process::class);
$getSerialized  = \serialize($_GET);
$postSerialized = \serialize($_POST);
$serverSerialized = \serialize($_SERVER);
$sPathCopy = $sPath;
$serializedInterface = \serialize($Interface);

$task = new CallbackTask(function () use ($getSerialized, $postSerialized, $serverSerialized, $sPathCopy, $serializedInterface) {
    global $_GET, $_POST, $_SERVER, $sPath, $Interface;

    $sPath = $sPathCopy;
    $_GET  = \unserialize($getSerialized);
    $_POST = \unserialize($postSerialized);
    $_SERVER = \unserialize($serverSerialized);
    $Interface = \unserialize($serializedInterface);
    \ob_start();

    $list = new TAdminHistoryPropertiesScheduleList;

    if (isset($_GET['export'])) {
        $list->ExportCSVHeader = true;
        $list->DoExportExcel();
    } else {
        $list->Draw();
    }

    return [
        'render' => \ob_get_clean(),
        'interface' => \serialize($Interface),
    ];
});
$task->requestId = $task->requestId . '_' . ($_GET['export'] ?? false ? 'excel' : 'html');

const QUERY_STRING_REQUEST_ID_PARAM = 'requestId';

function buildRedirectUrl(array $additionalParams = []) : string
{
    $params = \http_build_query(\array_merge($_GET, $additionalParams));

    return $_SERVER['SCRIPT_NAME'] . "?{$params}";
}

if (isset($_GET[QUERY_STRING_REQUEST_ID_PARAM])) {
    $requestId = \preg_replace('/[^a-zA-Z0-9_]/', '', $_GET[QUERY_STRING_REQUEST_ID_PARAM]);
    $requestIdParts = \explode('_', $requestId);

    if (
        ('html' === $requestIdParts[\count($requestIdParts) - 1]) &&
        (($_GET['export'] ?? false))
    ) {
        Redirect(buildRedirectUrl([QUERY_STRING_REQUEST_ID_PARAM => \implode('_', \array_slice($requestIdParts, 0, \count($requestIdParts) - 1)) . '_' . 'excel']));
    }

    $task->requestId = $requestId;
    $response = $asyncProcess->execute($task);

    switch ($response->status) {
        case Response::STATUS_ERROR:
        case Response::STATUS_NONE: {
            $sTitle = "[Error] History Properties Schedule";
            drawHeader("History Properties Schedule");
            echo "<div>An error occurred while executing query</div>";
            DrawFooter();

            break;
        }

        case Response::STATUS_PROCESSING:
        case Response::STATUS_QUEUED: {
            $sTitle = "[In Progress] History Properties Schedule";
            drawHeader("History Properties Schedule");
            echo "
                <div>Processing query...</div>
                <script>
                    setTimeout(() => location.reload(), 30 * 1000);
                </script>
            ";
            drawFooter();

            break;
        }

        case Response::STATUS_READY: {
            if (isset($_GET['export'])) {
                $list = new TAdminHistoryPropertiesScheduleList;
                $list->ExportCSVHeader = true;
                $list->ExportExcelHeaders();
                echo $response->data['render'];
            } else {
                $sTitle = "[Done] History Properties Schedule";
                $Interface = \unserialize($response->data['interface']);
                drawHeader("History Properties Schedule");
                echo $response->data['render'];
                drawFooter();
            }

            break;
        }
    }
} else {
    $asyncProcess->execute($task);
    Redirect(buildRedirectUrl([QUERY_STRING_REQUEST_ID_PARAM => $task->requestId]));
}
