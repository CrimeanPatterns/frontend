<?php

namespace AwardWallet\Engine\testprovider;

use AwardWallet\Engine\testprovider\Extension\DomCacheExtension;
use AwardWallet\ExtensionWorker\ParserSelectorInterface;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use Psr\Log\LoggerInterface;

class TestproviderExtensionParserSelector implements ParserSelectorInterface
{

    public function selectParser(SelectParserRequest $request, LoggerInterface $logger): string
    {
        return substr(self::class, 0, strrpos(self::class, '\\') + 1) . str_replace('.', '\\', $request->getLogin());
    }
}