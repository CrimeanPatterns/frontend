<?php

function getJenkinsFlag(string $optionName): ?bool
{
    switch (getenv($optionName)) {
        case false: return null;

        case 'true': return true;

        case 'false': return false;

        default: throw new \RuntimeException("Unknown {$optionName} value");
    }
}

function verbosePassthru(string $command)
{
    echo "+ command: {$command}\n";
    passthru($command, $ret);

    if (0 !== $ret) {
        throw new \RuntimeException('Command exited with non-zero status');
    }
}
