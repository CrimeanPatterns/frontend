<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PatternMatcher
{
    /**
     * @var array
     *
     * Sample element:
     * [
     *      "MerchantID" => 1,
     *      "Name" => "IKEA",
     *      "DisplayName" => "Ikea",
     *      "Patterns" => [
     *           ["template" => "ikea", "isPreg" => false, "beginSymbol" => true],
     *           ["template" => "ikea2", "isPreg" => false, "beginSymbol" => true],
     *      ]
     *  ]
     */
    private array $merchants;
    private $cache = [];
    private ContextAwareLoggerWrapper $logger;
    private array $patternChunks;
    private array $prefixes = [];
    private array $substrings = [];
    private ProgressLogger $progressLogger;
    private int $count = 0;
    private int $patternCount;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->setMessagePrefix("patternMatcher: ");
        $this->progressLogger = new ProgressLogger($this->logger, 1000, 60);

        $patterns = [];

        $this->merchants = it(
            $connection->fetchAllAssociative(
                "select 
                MerchantID, Name, DisplayName, Patterns 
            from 
                Merchant 
            where 
                Patterns is not null 
            order by 
                DetectPriority
            ")
        )
        ->map(function (array $row) use (&$patterns) {
            it(explode("\n", $row["Patterns"]))
                ->map('trim')
                ->apply(function (string $item) use (&$patterns, $row) {
                    $isPreg = substr($item, 0, 1) === '#';
                    $beginSymbol = substr($item, 1, 1) === '^' ? true : false;

                    //                    if ($isPreg) {
                    //                        $search = str_replace(['#', '^'], ['', ''], $item);
                    //                        $isPreg = $search !== preg_quote($search);
                    //
                    //                        if (!$isPreg) {
                    //                            $item = $search;
                    //                        }
                    //                    }

                    if (!$isPreg) {
                        $item = '#' . $item . '#';
                        $isPreg = true;
                    }

                    if ($isPreg) {
                        try {
                            preg_match($item, 'test');
                        } catch (\ErrorException $exception) {
                            $this->logger->warning("invalid pattern $item on merchant {$row['MerchantID']}: {$exception->getMessage()}");

                            return;
                        }

                        $item = substr($item, 1);
                        $item = substr($item, 0, strrpos($item, '#'));

                        if (isset($patterns[$item])) {
                            $this->logger->warning("Double pattern {$item} for merchant {$row['MerchantID']}, also used on {$patterns[$item]}");
                        } else {
                            $patterns[$item] = $row['MerchantID'];
                        }
                    } elseif ($beginSymbol) {
                        if (isset($this->prefixes[$item])) {
                            $this->logger->warning("Double prefix {$item} for merchant {$row['MerchantID']}, also used on {$this->prefixes[$item]}");
                        } else {
                            $this->prefixes[$item] = $row['MerchantID'];
                        }
                    } else {
                        if (isset($this->substrings[$item])) {
                            $this->logger->warning("Double substring {$item} for merchant {$row['MerchantID']}, also used on {$this->substrings[$item]}");
                        } else {
                            $this->substrings[$item] = $row['MerchantID'];
                        }
                    }
                })
            ;

            $row['MerchantID'] = (int) $row['MerchantID'];

            return $row;
        })
        ->reindexByColumn('MerchantID')
        ->toArrayWithKeys();

        $this->patternCount = count($patterns);
        $this->patternChunks = it($patterns)
            ->mapIndexed(fn (int $merchantId, string $pattern) => "(*MARK:{$merchantId}){$pattern}")
            ->chunk(100)
            ->map(function (array $chunk) {
                return "#(?|" . implode("|", $chunk) . ")#";
            })
            ->toArray()
        ;
    }

    /**
     * @return - see item structure in merchants property
     */
    public function identify(string $normalizedName): ?array
    {
        $merchantId = $this->cache[$normalizedName] ?? null;

        if ($merchantId !== null) {
            return $this->merchants[$merchantId];
        }
        //
        //        if (($this->count % 1000) === 0) {
        //            $this->progressLogger->showProgress("cache size: " . count($this->cache), $this->count);
        //        }
        $this->count++;

        // pattern matching is faster
        //        $merchantId = $this->searchByPrefix($normalizedName);
        //
        //        if ($merchantId === null) {
        //            $merchantId = $this->searchBySubstring($normalizedName);
        //        }
        //
        //        if ($merchantId === null) {
        $merchantId = $this->searchByPatterns($normalizedName);
        //        }

        $this->cache[$normalizedName] = $merchantId;

        return $merchantId ? $this->merchants[$merchantId] : null;
    }

    public function logStats(): void
    {
        $this->logger->info("loaded " . count($this->merchants) . " merchants, with {$this->patternCount} patterns in " . count($this->patternChunks) . " chunks, " . count($this->prefixes) . " prefixes, " . count($this->substrings) . " substrings");
    }

    public function logPatterns(): void
    {
        it($this->merchants)
            ->flatMap(fn (array $merchant) => $merchant['Patterns'])
            ->filter(fn (array $pattern) => $pattern['isPreg'])
            ->apply(function (array $pattern) {
                $this->logger->info($pattern['template']);
            });
    }

    private function searchByPrefix(string $normalizedName): ?int
    {
        foreach ($this->prefixes as $prefix => $merchantId) {
            if (str_starts_with($normalizedName, $prefix)) {
                return $merchantId;
            }
        }

        return null;
    }

    private function searchBySubstring(string $normalizedName): ?int
    {
        foreach ($this->substrings as $substring => $merchantId) {
            if (strpos($normalizedName, $substring) !== false) {
                return $merchantId;
            }
        }

        return null;
    }

    private function searchByPatterns(string $normalizedName)
    {
        foreach ($this->patternChunks as $patternChunk) {
            if (preg_match($patternChunk, $normalizedName, $matches)) {
                return $matches['MARK'];
            }
        }

        return null;
    }
}
