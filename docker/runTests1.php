#!/usr/bin/php
<?php

declare(ticks=1);

include_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/testsCommon.php';

use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Component\Process\Process;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

chdir('/www/awardwallet');

$isDistribute = ($argv[1] ?? null) === 'distribute';
$processesCount = $argv[2] ?? 3;

if ($isDistribute) {
    verbosePassthru('docker/codeceptSplitTests.php ' . $processesCount);
}

$codeceptOptions = '';

foreach ([
    'verbose' => '-vvv',
    'skipSlow' => '--skip-group slow',
    'skipUnstable' => '--skip-group unstable',
    'stopOnFailure' => '-f',
] as $env => $option) {
    if (getJenkinsFlag($env)) {
        $codeceptOptions .= ' ' . $option;
    }
}

function makeCodeceptCommand(string $target): string
{
    global $codeceptOptions;

    $result = "php -d opcache.enable_cli=On vendor/bin/codecept run {$target} --no-colors --no-interaction {$codeceptOptions}";
    echo "+ $result\n";

    return $result;
};

class GroupRun
{
    /**
     * @var array
     */
    private $includeGroups;
    /**
     * @var array
     */
    private $excludeGroups;
    /**
     * @var string
     */
    private $logLinePrefix;
    private bool $rerunFailed;

    public function __construct(array $includeGroups, array $excludeGroups, string $logLinePrefix, bool $rerunFailed = false)
    {
        $this->includeGroups = $includeGroups;
        $this->excludeGroups = $excludeGroups;
        $this->logLinePrefix = $logLinePrefix;
        $this->rerunFailed = $rerunFailed;
    }

    public function getIncludeGroups(): array
    {
        return $this->includeGroups;
    }

    public function getExcludeGroups(): array
    {
        return $this->excludeGroups;
    }

    public function getLogLinePrefix(): string
    {
        return $this->logLinePrefix;
    }

    public function isRerunFailed(): bool
    {
        return $this->rerunFailed;
    }
}

class ProcessHandler
{
    /**
     * @var string
     */
    public $tail;
    /**
     * @var Symfony\Component\Process\Process;
     */
    public $process;
    /**
     * @var string
     */
    protected $err = '';
    /**
     * @var string
     */
    protected $std = '';
    /**
     * @var string
     */
    protected $groupName;

    public function __construct(Symfony\Component\Process\Process $process, string $linePrefix)
    {
        $this->process = $process;
        $this->groupName = $linePrefix;
    }

    public function handleStd($newData): Iterator
    {
        return $this->handleBuffer($this->std, $newData);
    }

    public function handleErr($newData): Iterator
    {
        return $this->handleBuffer($this->err, $newData);
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    protected function handleBuffer(string &$buffer, $newData): Iterator
    {
        $buffer .= $newData;

        while (false !== ($newLinePos = strpos($buffer, "\n"))) {
            yield substr($buffer, 0, $newLinePos + 1);
            $buffer = substr($buffer, $newLinePos + 1);
        }
    }
}

/** @var ProcessHandler[] $processes */
$processes = [];

function makeCodeceptProcessByGroupRun(GroupRun $groupRun): ProcessHandler
{
    $included =
        it($groupRun->getIncludeGroups())
        ->map(function (string $group) { return " -g {$group}"; })
        ->joinToString(' ');

    $excluded =
        it($groupRun->getExcludeGroups())
        ->map(function (string $group) { return " -x {$group}"; })
        ->joinToString(' ');

    $failGroupName = 'fail-rerun-' .
        it($groupRun->getIncludeGroups())
        ->joinToString('_');

    $failedGroupOpt = "--override \"extensions: config: Codeception\\Extension\\RunFailed: fail-group: {$failGroupName}\"";

    if (getJenkinsFlag('rerunFailedTests')) {
        $included = '-g ' . $failGroupName;
        $excluded = '';
    }

    return doMakeCodeceptProcess(makeCodeceptCommand("{$failedGroupOpt} {$included} {$excluded}"), $groupRun->getLogLinePrefix());
}

function makeCodeceptProcess(string $group, bool $runOne = false): ProcessHandler
{
    return doMakeCodeceptProcess(makeCodeceptCommand(" " . ($runOne ? '' : '-g') . " {$group}"), $group);
}

function doMakeCodeceptProcess(string $command, string $logLinePrefix): ProcessHandler
{
    $processes[] = $handler = new ProcessHandler(
        $process = new Process($command),
        $logLinePrefix
    );
    $process->setIdleTimeout(60 * 5);
    $process->setTimeout(null);
    $process->start(function ($type, $newDataBuffer) use ($handler) {
        $lines = (Process::ERR === $type) ?
            $handler->handleErr($newDataBuffer) :
            $handler->handleStd($newDataBuffer);

        foreach ($lines as $line) {
            if (
                isset($handler->tail)
                || preg_match('/^((\[[a-zA-Z0-9- :]+\]\s+)?PHP Fatal Error: |Fatal error:|Time:[^,]+,\s*Memory:|No tests executed!|In [^\n]+ line \d+:|The following test\(s\) has unverified mock\(s\):)/ims', $line)
            ) {
                $handler->tail .= $handler->getGroupName() . ': ' . $line;
            }

            echo $handler->getGroupName() . ': ' . $line;
        }
    });

    return $handler;
}

function makeProcess(string $command, string $logLinePrefix, int $idleTimeout = 60 * 5, ?string $workingDir = null): ProcessHandler
{
    $processes[] = $handler = new ProcessHandler(
        $process = new Process($command),
        $logLinePrefix
    );
    $handler->tail = '';
    $process->setIdleTimeout($idleTimeout);
    $process->setTimeout(null);

    if ($workingDir) {
        $process->setWorkingDirectory($workingDir);
    }
    $process->start(function ($type, $newDataBuffer) use ($handler) {
        $lines = (Process::ERR === $type) ?
            $handler->handleErr($newDataBuffer) :
            $handler->handleStd($newDataBuffer);

        foreach ($lines as $line) {
            $handler->tail .= $handler->getGroupName() . ': ' . $line;
            echo $handler->getGroupName() . ': ' . $line;
        }
    });

    return $handler;
}

if ($isDistribute) {
    $i = 0;

    foreach (range(1, $processesCount < 1 ? 1 : $processesCount) as $groupN) {
        if ($i++ !== 0) {
            sleep(5);
        }

        $group = "paracept_{$groupN}";
        $processes[] = makeCodeceptProcess($group);
    }
} else {
    if ('' !== ($testName = (string) getenv('testName'))) {
        $processes[] = makeCodeceptProcess(escapeshellarg($testName), true);
    } else {
        $i = 0;

        foreach ([
            'frontendUnit' => [new GroupRun(['frontend-unit'], [], 'frontend-unit')],
            'frontendFunctional' => [
                new GroupRun(['frontend-functional'], ['mobile'], 'frontend-functional (no mobile)'),
                new GroupRun(['mobile'], ['frontend-unit', 'frontend-acceptance'], 'frontend-functional (mobile)'),
            ],
            'frontendAcceptance' => [new GroupRun(['frontend-acceptance'], [], 'frontend-acceptance')],
        ] as $option => $groupRuns) {
            foreach ($groupRuns as $groupRun) {
                if (getJenkinsFlag($option)) {
                    if ($i++ !== 0) {
                        sleep(5);
                    }

                    $processes[] = makeCodeceptProcessByGroupRun($groupRun);
                }
            }
        }
        $group = getenv('group');

        if (!empty($group) && preg_match('#^[\w\-_]+$#ims', $group)) {
            $processes[] = makeCodeceptProcess($group);
        }
    }
}

const PHP_LINT_COMMAND = <<<'EOT'
(
    find . -type f -name '*.php' \
        -not -path "./vendor/symfony/*" \
        -not -path "./vendor/doctrine/*" \
        -not -path "./vendor/fgrosse/phpasn1/lib/ASN1/Universal/Null.php" \
        -not -path "./vendor/jms/security-extra-bundle/Security/Util/String.php" \
        -not -path "./vendor/codeception/codeception/tests/data/Invalid.php" \
        -not -path "./vendor/zendframework/zend-mail/src/Transport/Null.php" \
        -not -path "./app/cache/*" \
        -not -path "./web/engine/*" \
        -not -path "./vendor/friendsofphp/php-cs-fixer/*" \
        -print0 \
    | xargs -I '{}' -0 -n1 -P0 sh -c "php -l -n '{}' || exit 0" \
    | (! grep -v "No syntax errors detected")
) && echo "OK"
EOT;

if (getJenkinsFlag('phpLint')) {
    $processes[] = makeProcess(PHP_LINT_COMMAND, 'phpLinter', 15 * 60);
}

const ES_LINT_COMMAND = <<<'EOT'
../../node_modules/.bin/eslint --no-color -f compact . && echo "OK"
EOT;

if (getJenkinsFlag('esLint')) {
    $processes[] = makeProcess(ES_LINT_COMMAND, 'esLinter', 15 * 60, __DIR__ . '/../web/assets');
}

$endHandler = function () use ($processes) {
    $exitCode = 0;

    foreach ($processes as $processHandler) {
        echo "**********************************************\n" .
            "* " . $processHandler->process->getCommandLine() . "\n" .
            "**********************************************\n\n";

        if (isset($processHandler->tail)) {
            echo (StringUtils::isNotEmpty($processHandler->tail) ? $processHandler->tail : 'NO OUTPUT FROM PROCESS') . "\n";
            $processHandler->tail = null;
        }

        if ($processHandler->process->isRunning()) {
            echo "Still running, killing process\n";
            $processHandler->process->stop(1, SIGKILL);
        }

        if (0 !== $processHandler->process->getExitCode()) {
            echo "Exited with code {$processHandler->process->getExitCode()}\n";
            $exitCode = 1;
        }
    }

    return $exitCode;
};

pcntl_signal(SIGTERM, $endHandler);
pcntl_signal(SIGUSR1, $endHandler);
pcntl_signal(SIGHUP, $endHandler);
pcntl_signal(SIGINT, $endHandler);

sleep(1);

while (true) {
    $liveCount = 0;

    foreach ($processes as $processHandler) {
        $timedOut = false;

        try {
            $processHandler->process->checkTimeout();
        } catch (\RuntimeException $exception) {
            $timedOut = true;
            $processHandler->process->stop(1, SIGKILL);
        }

        if (!$timedOut && $processHandler->process->isRunning()) {
            $liveCount++;
        }
    }

    if ($liveCount === 0) {
        $exitCode = 1;

        try {
            $exitCode = $endHandler();

            if (0 === $exitCode) {
                $branch = \trim(\shell_exec('git rev-parse --abbrev-ref HEAD'));
                $commit = \trim(\shell_exec('git rev-parse HEAD'));
                echo "**********************************************\n" .
                    "BRANCH: {$branch}\n" .
                    "DEPLOY-FRONTEND: https://jenkins.awardwallet.com/job/Frontend/job/deploy-frontend/parambuild/?branch={$commit}\n" .
                    "**********************************************\n\n";
            }
        } finally {
            exit($exitCode);
        }
    } else {
        sleep(1);
    }
}
