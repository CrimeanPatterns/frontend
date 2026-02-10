<?

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;

$schema = "historySchedule";
require( "../start.php" );

$asyncProcess = getSymfonyContainer()->get(Process::class);
$getSerialized  = \serialize($_GET);
$postSerialized = \serialize($_POST);
$serverSerialized = \serialize($_SERVER);
$sPathCopy = $sPath;

while (\ob_get_level() > 0) {
    \ob_end_flush();
}

$asyncProcess->execute($task = new CallbackTask(function () use ($getSerialized, $postSerialized, $serverSerialized, $sPathCopy) {
    global $_GET, $_POST, $_SERVER, $sPath;

    $sPath = $sPathCopy;
    $_GET  = \unserialize($getSerialized);
    $_POST = \unserialize($postSerialized);
    $_SERVER = \unserialize($serverSerialized);
    \ob_start();

    echo "<div>Start processing long-running computations...</div><br/>";
    \sleep(180 + 30);
    echo "<div>Done!</div>";

    return \ob_get_clean();
}));

class ScheduleException extends \RuntimeException {}

header('X-Accel-Buffering: no');
$result =
    it(\iter\range(0, INF))
    ->timeout(10 * 60 * 1000, new ScheduleException('Timeout (10 minutes) exceeded!'))
    ->onNth(30, function ($n) {
        \header('X-Aw-Heartbeat-30sec: ' . $n);
    })
    ->flatMap(function () use ($asyncProcess, $task) {
        $response = $asyncProcess->execute($task);

        switch ($response->status) {
            case Response::STATUS_READY: {
                yield $response->data;

                break;
            }

            case Response::STATUS_NONE:
            case Response::STATUS_ERROR: {
                throw new ScheduleException('An error occurred while executing task.');
            }

            default: {
                \sleep(1);

                break;
            }
        }
    })
    ->recover(function (ScheduleException $e) {
        yield $e;
    })
    ->first();
echo "</div>";

if(isset($_GET['export'])){
    $list = new TAdminHistoryPropertiesScheduleList;
    $list->ExportCSVHeader = true;
    $list->ExportExcelHeaders();

    if ($result instanceof ScheduleException) {
        throw $result;
    } else {
        echo $result;
    }
} else{
    drawHeader("History Properties Schedule (Sync)");

    if ($result instanceof ScheduleException) {
        echo $result->getMessage() . "\n";
    } else {
        echo $result;
    }

    drawFooter();
}
