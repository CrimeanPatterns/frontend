<?php

namespace AwardWallet\Engine\golair;

use AwardWallet\ExtensionWorker\ParserSelectorInterface;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use Psr\Log\LoggerInterface;

class GolairExtensionParserSelector implements ParserSelectorInterface
{
    public function selectParser(SelectParserRequest $request, LoggerInterface $logger): string
    {
        if (in_array($request->getLogin2(), ['Argentina'])) {
            return GolairExtensionArgentina::class;
        }

        if (in_array($request->getLogin2(), ['Brasil'])) {
            return GolairExtension::class;
        }
        
        return GolairExtension::class;
    }
}
