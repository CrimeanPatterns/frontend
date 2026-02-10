<?php

namespace AwardWallet\Engine\groupon;

use AwardWallet\ExtensionWorker\ParserSelectorInterface;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use Psr\Log\LoggerInterface;

class GrouponExtensionParserSelector implements ParserSelectorInterface
{
    public function selectParser(SelectParserRequest $request, LoggerInterface $logger): string
    {
        switch ($request->getLogin2()) {
            case 'USA':
                return GrouponExtensionUsa::class;
            default:
                return GrouponExtensionGeneral::class;
        }
    }
}
