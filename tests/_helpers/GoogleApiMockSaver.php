<?php

namespace Codeception\Extension;

use AwardWallet\Common\Tests\HttpCache;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Module\GoogleApiMock;

class GoogleApiMockSaver extends Extension
{
    public static $events = [
        Events::RESULT_PRINT_AFTER => 'printResults',
    ];

    public function printResults()
    {
        if (HttpCache::save(GoogleApiMock::MOCK_FILE)) {
            $this->output->writeln("<bg=yellow;fg=black>http mocks file changed, see diff of " . GoogleApiMock::MOCK_FILE . "</>");
        }
    }
}
