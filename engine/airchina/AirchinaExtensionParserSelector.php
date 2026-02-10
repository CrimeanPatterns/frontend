<?php

namespace AwardWallet\Engine\airchina;

use AwardWallet\ExtensionWorker\ParserSelectorInterface;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use Psr\Log\LoggerInterface;

class AirchinaExtensionParserSelector implements ParserSelectorInterface
{
    public function selectParser(SelectParserRequest $request, LoggerInterface $logger): string
    {
        if (in_array($request->getLogin2(), ['China'])) {
            return AirchinaExtensionChina::class;
        }

        return AirchinaExtension::class;
    }
}
