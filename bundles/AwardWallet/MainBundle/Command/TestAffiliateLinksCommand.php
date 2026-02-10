<?php

namespace AwardWallet\MainBundle\Command
{
    use AwardWallet\Common\Monolog\Processor\TraceProcessor;
    use AwardWallet\MainBundle\Command\TestAffiliateLinksCommand\ProviderException;
    use AwardWallet\MainBundle\Entity\Provider;
    use AwardWallet\MainBundle\FrameworkExtension\Command;
    use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
    use AwardWallet\MainBundle\Globals\StackTraceUtils;
    use AwardWallet\MainBundle\Globals\StringUtils;
    use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
    use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\EntityManagerInterface;
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;

    use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
    use function AwardWallet\MainBundle\Globals\Utils\iter\filterNotByKeyInMap;
    use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
    use function AwardWallet\MainBundle\Globals\Utils\lazy;

    class TestAffiliateLinksCommand extends Command
    {
        public const PERIOD_HOURS = 16;
        public const MAX_FAILURES_PER_PERIOD = 4;

        public const FLEX_OFFERS_PAGE_SIZE = 500;
        public const FLEX_OFFERS_MIN_LINKS_COUNT = 10000;

        public const COMMISSION_JUNCTION_MIN_LINKS_COUNT = 4000;
        private const MAX_CJ_FAULTY_PAGES_COUNT = 3;
        private const CJ_SHRINK_FACTOR = 10;
        private const CJ_MIN_PAGE_SIZE = 10;
        private const CJ_PAGE_SIZE = 100;
        private const FO_MAX_REMOVE_THRESHOLD = 3;
        private const CJ_MAX_REMOVE_THRESHOLD = 3;
        protected static $defaultName = 'aw:affiliate-links:test';

        /**
         * @var LoggerInterface
         */
        private $logger;
        /**
         * @var Mailer
         */
        private $mailer;
        /**
         * @var EntityManager
         */
        private $em;
        /**
         * @var \Throttler
         */
        private $throttlerLinkRemover;
        /**
         * @var \Throttler
         */
        private $apiThrottler;
        private bool $dryRun;
        private string $flexOffersApiKey;
        private string $cjApiKey;
        private ?string $dumpDir = null;
        private \Memcached $memcached;

        public function __construct(
            LoggerInterface $logger,
            Mailer $mailer,
            EntityManagerInterface $entityManager,
            \Memcached $memcached,
            $flexOffersApiKeyParameter,
            $cjApiKeyParameter
        ) {
            parent::__construct();

            $this->logger = $logger;
            $this->mailer = $mailer;
            $this->em = $entityManager;
            $this->memcached = $memcached;

            $this->flexOffersApiKey = $flexOffersApiKeyParameter;
            $this->cjApiKey = $cjApiKeyParameter;
        }

        protected function configure()
        {
            $this
                ->setDescription('test affiliate links for errors')
                ->addOption('providers', null, InputOption::VALUE_REQUIRED, 'providers list: 12, skywards', '')
                ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'do not modify providers')
                ->addOption('dump-dir', null, InputOption::VALUE_REQUIRED);
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $this->throttlerLinkRemover = new \Throttler($this->memcached, 3600, self::PERIOD_HOURS, self::MAX_FAILURES_PER_PERIOD);
            $this->apiThrottler = new \Throttler($this->memcached, 3, 1, 1);

            $this->dumpDir = $input->getOption('dump-dir');
            $this->logger->debug('something');
            $providers = it(explodeLazy(',', $input->getOption('providers')))
                ->mapByTrim()
                ->filterNotEmpty()
                ->toArray();

            $query = $this->em->createQuery("
                select p 
                from AwardWallet\MainBundle\Entity\Provider p 
                where 
                    " . ($providers ? "(p.providerid in (:providers) or p.code in (:providers))" : '1=1') . " and
                    (p.clickurl <> '' or p.clickurl is not null)
                order by p.accounts desc
            ");

            if ($providers) {
                $query->setParameter('providers', $providers);
            }

            $this->dryRun = $input->getOption('dry-run');

            /** @var Provider[] $providers */
            $providers = $query->getResult();
            $invalidLinkCheckers = [
                $this->validateProviders(
                    $providers,
                    'flexoffers',
                    '/.*flex.*\?foid=([\d\.-]+)&.*trid=1038452\./',
                    $this->flexOffersLinksProvider(),
                    'The link was not found in FlexOffers /links API response.',
                    self::FO_MAX_REMOVE_THRESHOLD
                ),
                $this->validateProviders(
                    $providers,
                    'commissionjunktion',
                    '/click-8125108-([0-9]+)/',
                    $this->commissionJunctionLinksProvider(),
                    'The link was not found in CJ /v2/link-search API response.',
                    self::CJ_MAX_REMOVE_THRESHOLD
                ),
            ];

            $exitCode = 0;

            foreach ($invalidLinkCheckers as $invalidLinkChecker) {
                foreach (
                    it($invalidLinkChecker)
                    ->catch(function (ProviderException $e) use (&$exitCode) {
                        $exitCode = 1;
                        $this->logger->info(
                            \sprintf('%s: %s (uncaught exception) at %s line %s', get_class($e), TraceProcessor::filterMessage($e), $e->getFile(), $e->getLine()),
                            ['traces' => StackTraceUtils::flattenExceptionTraces($e)]
                        );
                    }) as [$invalidLinkProvider, $linkProviderCode, $reason]
                ) {
                    /** @var Provider $invalidLinkProvider */
                    $this->logger->info(
                        \sprintf(
                            "%s: %s(%d) %s: invalid link, %s",
                            $linkProviderCode,
                            $invalidLinkProvider->getCode(),
                            $invalidLinkProvider->getProviderid(),
                            $invalidLinkProvider->getShortName(),
                            $invalidLinkProvider->getClickUrl()
                        ),
                        ['_aw_server_module' => 'affiliate_links_test']
                    );

                    $this->notify($invalidLinkProvider, $linkProviderCode, $reason);
                }
            }

            return $exitCode;
        }

        protected function notify(Provider $provider, string $linkProviderCode, $reason): void
        {
            $providerId = $provider->getProviderid();
            $clickUrl = $provider->getClickurl();
            $displayName = $provider->getDisplayname();
            $code = $provider->getCode();

            $emails = $this->dryRun ? ['test@awardwallet.com'] : ['erik@awardwallet.com', 'affiliate@awardwallet.com', 'test@awardwallet.com'];
            $title = "{$displayName} invalid affiliate link detected" . ($this->dryRun ? ' (dry-run mode)' : '');
            $action = $this->dryRun ? "was not removed (dry run mode)" : "was removed";
            $body =
                "The following invalid affiliate link ({$linkProviderCode}) was detected on {$displayName}:<br/><br/>

                    <a href='{$clickUrl}'>{$clickUrl}</a><br/><br/>

                    The link {$action}, please update {$displayName} with a new link in the ClickURL field:<br/><br/>
                                        
                    <a href='https://awardwallet.com/manager/edit.php?ID={$providerId}&Schema=Provider'>https://awardwallet.com/manager/edit.php?ID={$providerId}&Schema=Provider</a><br/><br/>

                    {$reason}<br/>";

            if (!$this->dryRun) {
                $throttlerKey = 'affiliate_links_throttler_v4_' . $code . '_' . \hash('sha256', $clickUrl);

                if ($this->throttlerLinkRemover->getDelay($throttlerKey) > 0) {
                    $provider->setClickurl(null);
                    $this->em->flush();
                    $this->throttlerLinkRemover->clear($throttlerKey);
                } else {
                    $this->logger->info(
                        'link removal was throttled',
                        [
                            '_aw_server_module' => 'affiliate_links_test',
                            'provider' => $code,
                        ]
                    );

                    return;
                }
            }

            $message = $this->mailer->getMessage('affiliate_link_test', $emails, $title);
            $message->setBody($body);
            $message->setContentType('text/html');

            $this->mailer->send($message);
        }

        private function validateProviders(array $providers, string $linkProviderCode, string $linkPattern, iterable $linkIdProvider, string $reason, int $maxRemovedLinks): iterable
        {
            $logger = function ($message) use ($linkProviderCode) {
                $this->logger->info("{$linkProviderCode}: " . $message, ['_aw_server_module' => 'affiliate_links_test']);
            };

            $savedProductsMap =
                it($providers)
                ->propertyPath('clickurl')
                ->flatMapToMatch($linkPattern)
                ->flip()
                ->toArrayWithKeys();

            $logger("links saved: " . \count($savedProductsMap));

            if (!\count($savedProductsMap)) {
                return;
            }

            $realProductsMap = it($linkIdProvider)
                ->onNthAndLast(2000, function ($n, $_, $__, $isTotal) use ($logger) {
                    $logger(($isTotal ? 'Total ' : '') . "{$n} links retrieved");
                })
                ->flip()
                ->toArrayWithKeys();

            $providersToRemove =
                it(filterNotByKeyInMap($savedProductsMap, $realProductsMap))
                ->toArray();

            if (\count($providersToRemove) > $maxRemovedLinks) {
                $logger(\sprintf('Remove-threshold (%d) was exceeded by %d', $maxRemovedLinks, \count($providersToRemove)));

                return;
            }

            foreach ($providersToRemove as $providerNumber) {
                yield [$providers[$providerNumber], $linkProviderCode, $reason];
            }
        }

        private function flexOffersLinksProvider(): iterable
        {
            $logger = function ($message) {
                $this->logger->info("flexoffers: " . $message, ['_aw_server_module' => 'affiliate_links_test']);
            };
            $getPageEmitter = function () use ($logger): IteratorFluent {
                return
                    it(\iter\range(1, \INF))
                    ->throttle('flex_offers_link_search_api_v1', $this->apiThrottler, function ($delay) use ($logger) {
                        $logger("sleeping {$delay} seconds");
                    });
            };

            $client = new Client();
            $totalLinksCounter = 0;
            $pageSize = self::FLEX_OFFERS_PAGE_SIZE;

            yield from $getPageEmitter()
                ->map(function (int $page) use ($logger, $client, $pageSize) {
                    try {
                        $logger("performing Links API request, page: {$page}");

                        return $client->get("https://api.flexoffers.com/promotions?page={$page}&pageSize={$pageSize}&sortColumn=startdate&sortOrder=asc", [
                            'headers' => [
                                'ApiKey' => $this->flexOffersApiKey,
                                'accept' => 'application/json',
                            ],
                        ]);
                    } catch (RequestException $exception) {
                        $this->raiseFlexoffersError('links request time error', null, $exception);
                    }
                })
                ->takeWhile(fn (ResponseInterface $response) => $response->getStatusCode() !== 204)
                ->flatMapIndexed(function (ResponseInterface $response, int $idx) use ($pageSize) {
                    $body = (string) $response->getBody();

                    if (null !== $this->dumpDir) {
                        \file_put_contents("{$this->dumpDir}/fo_{$idx}_{$pageSize}.json", $body);
                    }

                    yield from it(\json_decode($body, true)['results'])
                        ->map(function ($product) {
                            if (!isset($product['linkId']) || !\is_scalar($product['linkId'])) {
                                throw new ProviderException('invalid product format, missing "linkId" column');
                            }

                            if (!\preg_match('/^[\d\.-]+$/', $product['linkId'])) {
                                throw new ProviderException('invalid product format, linkId is not dot-numeric: ' . $product['linkId']);
                            }

                            return $product['linkId'];
                        })
                        ->catch(function (\Throwable $e) {
                            $this->raiseFlexoffersError('invalid response: ' . $e->getMessage(), null);
                        });
                })
                ->increment($totalLinksCounter);

            if ($totalLinksCounter < self::FLEX_OFFERS_MIN_LINKS_COUNT) {
                $this->raiseFlexoffersError('too few results', null);
            }
        }

        /**
         * @return never
         */
        private function raiseFlexoffersError(string $message, ?ResponseInterface $response, ?\Throwable $previous = null)
        {
            $this->raiseError('FlexOffers', $message, $response, $previous);
        }

        private function commissionJunctionLinksProvider(): iterable
        {
            $logger = function ($message) {
                $this->logger->info("commissionjunktion: " . $message, ['_aw_server_module' => 'affiliate_links_test']);
            };
            $logger("key length: " . \strlen($this->cjApiKey));

            $client = new Client();
            $linkCounter = 0;

            yield from it(\iter\range(1, \INF))
                ->throttle('comission_junction_link_search_api_v1', $this->apiThrottler, function ($delay) use ($logger) {
                    $logger("sleeping {$delay} seconds");
                })
                ->map(fn (int $page) => $this->downloadCjPage($page, $logger, $client))
                ->takeWhile(fn (?iterable $linkIt) => $linkIt !== null)
                ->flatten(1)
                ->increment($linkCounter);

            if ($linkCounter < self::COMMISSION_JUNCTION_MIN_LINKS_COUNT) {
                $this->raiseCommissionJunctionError('too few results: ' . $linkCounter, null);
            }
        }

        private function extractCjLinks(array $tuple): iterable
        {
            [$response, [$xml]] = $tuple;

            if (
                !isset($xml->links)
                || !($attributes = $xml->links->attributes())
                || !isset($attributes->{'records-returned'})
            ) {
                $this->raiseCommissionJunctionError('no records-returned field', $response);
            }

            foreach ($xml->links->link as $link) {
                if (
                    isset($link->clickUrl)
                    && ($link->clickUrl instanceof \SimpleXMLElement)
                    && ($link->clickUrl->count() === 1)
                    && StringUtils::isNotEmpty($clickUrl = (string) $link->clickUrl)
                    && \preg_match('/click-8125108-([0-9]+)/', $clickUrl, $matches)
                ) {
                    yield $matches[1];
                } elseif (
                    isset($link->{'link-id'})
                    && ($link->{'link-id'} instanceof \SimpleXMLElement)
                    && ($link->{'link-id'}->count() === 1)
                    && StringUtils::isNotEmpty($linkId = (string) $link->{'link-id'})
                    && \preg_match('/^\d+$/', $linkId)
                ) {
                    yield $linkId;
                } else {
                    $this->raiseCommissionJunctionError('invalid link element format: ' . ((string) $link), $response);
                }
            }
        }

        /**
         * @return never
         */
        private function raiseError(string $providerName, string $message, ?ResponseInterface $response, ?\Throwable $previous = null): void
        {
            if ($response) {
                $error = \sprintf($providerName . ' API error: ' . $message . ', code: %s, body(first 512 bytes): %s', $response->getStatusCode(), substr($this->filterProviderKeysFromResponse((string) $response->getBody()), 0, 512));
                $code = $response->getStatusCode();
            } else {
                $error = $providerName . " API error: " . $message;
                $code = 0;
            }

            throw new ProviderException($error, $code, $previous);
        }

        private function filterProviderKeysFromResponse(string $response): string
        {
            foreach ([$this->cjApiKey, $this->flexOffersApiKey] as $key) {
                $response = \str_replace($key, '[filtered_provider_key]', $response);
            }

            return $response;
        }

        /**
         * @return never
         */
        private function raiseCommissionJunctionError(string $message, ?ResponseInterface $response, ?\Throwable $previous = null)
        {
            $this->raiseError('CommissionJunction', $message, $response, $previous);
        }

        private static function isCjInternalServerError(ResponseInterface $response): bool
        {
            return
                (500 === $response->getStatusCode())
                && ($xml = @\simplexml_load_string((string) $response->getBody()))
                && isset($xml->{'error-message'})
                && (((string) $xml->{'error-message'}) === 'Internal Server Error');
        }

        private function downloadCjPage(int $pageNumber, callable $logger, Client $httpClient, int $pageSize = self::CJ_PAGE_SIZE, int &$skippedPages = 0): ?iterable
        {
            try {
                $rangeTo = $pageNumber * $pageSize;
                $rangeFrom = $rangeTo - $pageSize;
                $logger("performing API request, page: {$pageNumber}, size: {$pageSize}, range: [{$rangeFrom}, {$rangeTo}}]");
                $response = $httpClient->get("https://link-search.api.cj.com/v2/link-search?advertiser-ids=joined&website-id=8125108&records-per-page={$pageSize}&page-number={$pageNumber}", ['headers' => ['authorization' => "Bearer {$this->cjApiKey}"]]);
                $xml = lazy(function () use ($pageNumber, $response, $pageSize) {
                    $body = (string) $response->getBody();

                    if (null !== $this->dumpDir) {
                        \file_put_contents("{$this->dumpDir}/cj_{$pageNumber}_{$pageSize}.xml", $body);
                    }

                    return @\simplexml_load_string($body);
                });

                $tuple = [$response, $xml];

                if (!self::isValidCjPage($tuple)) {
                    return null;
                }

                return $this->extractCjLinks($tuple);
            } catch (RequestException $exception) {
                ++$skippedPages;
                $response = $exception->getResponse();

                if (self::isCjInternalServerError($response)) {
                    $newPageSize = $pageSize / self::CJ_SHRINK_FACTOR;

                    if ($newPageSize >= self::CJ_MIN_PAGE_SIZE) {
                        $logger('internal server error occured, shrinking page...');

                        return it(\range(1, self::CJ_PAGE_SIZE / self::CJ_SHRINK_FACTOR))
                            ->throttle('comission_junction_link_search_api_v1', $this->apiThrottler, function ($delay) use ($logger) {
                                $logger("sleeping {$delay} seconds");
                            })
                            ->map(function (int $subPageNumber) use ($pageNumber, $logger, $httpClient, $newPageSize, &$skippedPages) {
                                return $this->downloadCjPage(
                                    ($pageNumber - 1) * self::CJ_SHRINK_FACTOR + $subPageNumber,
                                    $logger,
                                    $httpClient,
                                    $newPageSize,
                                    $skippedPages
                                );
                            })
                            ->filterNotNull()
                            ->flatten(1);
                    } elseif ($skippedPages <= self::MAX_CJ_FAULTY_PAGES_COUNT) {
                        $logger('skipping faulty shrinked page...');

                        return null;
                    } else {
                        $this->raiseCJLinkSearchError($response, $exception);
                    }
                } else {
                    $this->raiseCJLinkSearchError($response, $exception);
                }
            }
        }

        /**
         * @return never
         */
        private function raiseCJLinkSearchError(ResponseInterface $response, RequestException $exception)
        {
            $this->raiseCommissionJunctionError('CJ link search request time error', $response, $exception);
        }

        private static function isValidCjPage(array $tuple): bool
        {
            [$response, $lazyXml] = $tuple;

            return
                ($response->getStatusCode() !== 204)
                && ($xml = $lazyXml())
                // no links found
                && isset($xml->links->link)
                && ($xml->links->link instanceof \SimpleXMLElement)
                && $xml->links->link->count();
        }
    }
}

namespace AwardWallet\MainBundle\Command\TestAffiliateLinksCommand
{
    class ProviderException extends \RuntimeException
    {
    }
}
