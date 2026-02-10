<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\Parser\ParserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ParseCommand extends Command
{
    private const CACHE_KEY = 'lounges-parsing_%s';

    public static $defaultName = 'aw:parsing-lounges';

    private Scheduler $scheduler;

    private Logger $logger;

    private EntityManagerInterface $em;

    private Clusterizer $clusterizer;

    private Mailer $mailer;

    private \Memcached $memcached;

    /**
     * @var ParserInterface[]
     */
    private array $parsers;

    private PriorityManager $priorityManager;

    private array $loungeSourceUpdateMap;

    private array $loungeSourceNotTrackableMap;

    private array $loungeUpdateMap;

    private EntityRepository $loungeRep;

    private EntityRepository $loungeSourceRep;

    private PropertyAccessor $propAccessor;

    private Connection $connection;

    private Statement $changeLoungeSourceQuery;

    private \DateTime $startDateTime;

    private ?InputInterface $input;

    private array $changedProps = [];

    private array $freezedChangedProps = [];

    /**
     * @param iterable<ParserInterface> $loungeParsers
     */
    public function __construct(
        Scheduler $scheduler,
        Logger $logger,
        EntityManagerInterface $em,
        Clusterizer $clusterizer,
        Mailer $mailer,
        \Memcached $memcached,
        iterable $loungeParsers,
        PriorityManager $priorityManager
    ) {
        parent::__construct();

        $this->scheduler = $scheduler;
        $this->logger = $logger;
        $this->em = $em;
        $this->clusterizer = $clusterizer;
        $this->mailer = $mailer;
        $this->memcached = $memcached;
        $this->parsers = it($loungeParsers)
            ->reindex(fn (ParserInterface $parser) => $parser->getCode())
            ->toArrayWithKeys();
        $this->priorityManager = $priorityManager;
        $this->loungeSourceUpdateMap = array_values(
            array_filter(
                array_merge(
                    $this->em->getClassMetadata(LoungeSource::class)->getColumnNames(),
                    ['Airlines', 'Alliances']
                ),
                function ($column) {
                    return !in_array($column, [
                        'LoungeSourceID',
                        'SourceCode',
                        'CreateDate',
                        'UpdateDate',
                        'DeleteDate',
                        'ParseDate',
                        'LoungeID',
                    ]);
                }
            )
        );
        $this->loungeSourceNotTrackableMap = [
            'Assets',
            'PageBody',
        ];
        $this->loungeUpdateMap = array_values(
            array_filter(
                array_merge(
                    $this->em->getClassMetadata(Lounge::class)->getColumnNames(),
                    ['Airlines', 'Alliances']
                ),
                function ($column) {
                    return !in_array($column, [
                        'LoungeID',
                        'AirportCode',
                        'CreateDate',
                        'UpdateDate',
                        'CheckedBy',
                        'CheckedDate',
                        'Visible',
                        'AttentionRequired',
                        'State',
                        'LocationParaphrased',
                        'OpeningHoursAi',
                    ]);
                }
            )
        );

        $this->loungeRep = $em->getRepository(Lounge::class);
        $this->loungeSourceRep = $em->getRepository(LoungeSource::class);
        $this->propAccessor = PropertyAccess::createPropertyAccessor();
        $this->connection = $em->getConnection();
        $this->changeLoungeSourceQuery = $this->connection->prepare('
            INSERT INTO LoungeSourceChange(LoungeSourceID, Property, OldVal, NewVal, ChangeDate)
            VALUES(?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE OldVal = ?, NewVal = ?
        ');
        $this->startDateTime = new \DateTime();
    }

    /**
     * @return iterable<string, LoungeSource[]>
     */
    final public function findAirportLoungesFromDb(ParserInterface $parser, array $includeAirportCodes, array $excludeAirportCodes): iterable
    {
        if (!$parser->isEnabled()) {
            yield from array_fill_keys($includeAirportCodes, []);
        }

        $filter = !empty($includeAirportCodes) ? ' AND AirportCode IN (:includeAirCodes)' : '';

        if (!empty($excludeAirportCodes)) {
            $filter .= ' AND AirportCode NOT IN (:excludeAirCodes)';
        }

        $airportsData = stmtAssoc($this->connection->executeQuery("
            SELECT
                AirportCode,
                PageBody
            FROM LoungeSource
            WHERE
                SourceCode = :sourceCode
                $filter
            ORDER BY AirportCode
        ", [
            'sourceCode' => $parser->getCode(),
            'includeAirCodes' => $includeAirportCodes,
            'excludeAirCodes' => $excludeAirportCodes,
        ], [
            'sourceCode' => \PDO::PARAM_STR,
            'includeAirCodes' => Connection::PARAM_STR_ARRAY,
            'excludeAirCodes' => Connection::PARAM_STR_ARRAY,
        ]))->groupAdjacentBy(fn (array $row1, array $row2) => $row1['AirportCode'] <=> $row2['AirportCode'])
            ->reindexByPropertyPath('[0][AirportCode]')
            ->map(function (array $group) {
                return it($group)
                    ->map(fn (array $row) => json_decode($row['PageBody'], true))
                    ->filterNotNull()
                    ->toArray();
            });

        foreach ($airportsData as $code => $airportData) {
            $lounges = [];

            if (is_array($airportData) && !empty($airportData)) {
                foreach ($airportData as $loungeData) {
                    $lounge = $this->doGetLounge($parser, $code, $loungeData);

                    if ($lounge) {
                        $lounges[] = $lounge;
                    }
                }
            }

            yield $code => $lounges;
        }
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('airport', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'parse lounges only for this airport')
            ->addOption('popularity-top', null, InputOption::VALUE_REQUIRED, 'parse lounges only for N popularity airports, as scheduled')
            ->addOption('db', null, InputOption::VALUE_NONE, 'parse lounges only from db')
            ->addOption('parser', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'parse lounges only for this parsers, without a merging stage')
            ->addOption('enable-recent-parsing', 'r', InputOption::VALUE_NONE, 'enabling re-parsing for parsers that have already successfully gathered information within the last 24 hours.')
            ->addOption('test', null, InputOption::VALUE_NONE, 'test mode, iata airport codes with prefix "$" like "$AB", "$CD", "$IO"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('lounges parsing starts');
        $this->input = $input;

        try {
            $this->searchLounges();
        } finally {
            $this->sendEmailNotifications();
            $this->sendSlackNotifications();
        }
        $this->logger->info('lounges parsing ends');

        return 0;
    }

    private function searchLounges(): void
    {
        $testMode = $this->input->getOption('test');

        if ($testMode) {
            $this->logger->info('test mode');
        }

        $parsers = $this->parsers;
        $skipMerge = false;
        $hasParserFilter = false;

        if (!empty($filtered = $this->input->getOption('parser'))) {
            $parsers = it($parsers)
                ->filter(fn (ParserInterface $parser) => in_array($parser->getCode(), $filtered))
                ->toArrayWithKeys();
            $skipMerge = true;
            $hasParserFilter = true;
        }

        $popularityTop = $this->input->getOption('popularity-top');
        $optimalMode = !empty($popularityTop);

        if ($optimalMode) {
            if ($this->scheduler->isItsTimeForScan()) {
                $this->logger->info(sprintf('optimal mode, search lounges for %s top airports', $popularityTop));
            } else {
                $this->logger->info('optimal mode, but not time for scan');

                return;
            }
        }

        $this->logger->info(sprintf(
            'search lounges from: %s',
            \count($parsers) > 0 ? implode(', ', $parsers) : 'no parsers'
        ));
        $fromDb = $this->input->getOption('db');

        if ($fromDb) {
            $this->logger->info('search lounges from db');
        }

        $enableRecentParsing = $this->input->getOption('enable-recent-parsing') || $hasParserFilter;

        if ($enableRecentParsing) {
            $this->logger->info('enable recent parsing');
        }

        // airport filter
        $inFilter = [];
        $notInFilter = [];
        $allAircodes = false;
        $newAircodes = false;

        if ($optimalMode) {
            $newAircodes = true;
            $aircodes = $this->connection->fetchFirstColumn('
                SELECT DISTINCT AirportCode 
                FROM LoungeSource 
                ' . ($testMode ? "WHERE AirportCode LIKE '\$__'" : '') . '
                ORDER BY AirportCode
            ');
            $totalCount = \count($aircodes);
            $number = $this->scheduler->getCurrentUpdateIteration() % $this->scheduler->getNumberOfIterations();
            $totalPart = max(floor($totalCount / $this->scheduler->getNumberOfIterations()), 1);
            $inFilter = array_unique(array_merge(
                $this->connection->fetchFirstColumn("
                        SELECT AirCode 
                        FROM AirCode 
                        WHERE AirCode IN (?) 
                        ORDER BY Popularity DESC LIMIT $popularityTop
                    ", [$aircodes], [Connection::PARAM_STR_ARRAY]
                ),
                array_slice(
                    $aircodes,
                    $start = max(floor(($totalPart * $number) - ($totalPart * 0.1)), 0),
                    $length = min(ceil($totalPart + ($totalPart * 0.2)), $totalCount)
                )
            ));
            $notInFilter = array_diff($aircodes, $inFilter);
            $this->logger->info(sprintf(
                'total aircodes: %d, %d out of %d, start: %d, length: %d',
                $totalCount,
                $number + 1,
                $this->scheduler->getNumberOfIterations(),
                $start,
                $length
            ));
        } elseif (!empty($this->input->getOption('airport'))) {
            $inFilter = array_map(function (string $airport) {
                return strtoupper(trim($airport));
            }, $this->input->getOption('airport'));
        } else {
            $allAircodes = true;
        }

        $this->logger->info(
            sprintf(
                'search for these airports: %s%s%s',
                $allAircodes ? 'all' : implode(', ', $inFilter),
                $notInFilter ? ', exclude: ' . implode(', ', $notInFilter) : '',
                $newAircodes ? ', + new airports' : ''
            )
        );

        $listAircodes = [];

        /** @var ParserInterface $parser */
        foreach ($parsers as $parser) {
            $parserCode = (string) $parser;
            $this->logger->getLogProcessor()->setBaseContext([
                'parser' => $parserCode,
            ]);

            if ($parser->isParsingFrozen()) {
                $this->logger->info('skip frozen parser');

                continue;
            }

            $this->logger->info('retrieving data');

            if ($fromDb) {
                $airports = $this->findAirportLoungesFromDb($parser, $inFilter, $notInFilter);
            } else {
                if ($optimalMode && !is_null($lastTs = $this->isParserAlreadySuccess($parser)) && !$enableRecentParsing) {
                    $this->logger->info(sprintf('skip parsing, already parsed at %s', date('Y-m-d H:i:s', $lastTs)));

                    continue;
                }

                $airports = $this->findAirportLounges($parser, function (string $airCode) use ($inFilter, $notInFilter, $allAircodes, $newAircodes) {
                    if ($allAircodes) {
                        return true;
                    }

                    return (in_array($airCode, $inFilter) || $newAircodes) && !in_array($airCode, $notInFilter);
                });
            }

            $this->logger->info('saving sources');
            $affectedAirCodes = [];

            try {
                foreach ($airports as $airCode => $lounges) {
                    $this->logger->getLogProcessor()->replacePrevContext(['aircode' => $airCode, 'lounge' => null]);
                    $affectedAirCodes[] = $airCode;
                    $loungeSourceAffectedIds = [];
                    $loungeSourceProviderIds = array_map(fn (LoungeSource $source) => $source->getSourceId(), $lounges);
                    $newLoungesCount = 0;

                    foreach ($lounges as $lounge) {
                        $this->logger->getLogProcessor()->replacePrevContext(['aircode' => $airCode, 'lounge' => $lounge]);
                        $existingLoungeSource = $this->findExistingLoungeSource($lounge, $loungeSourceProviderIds);

                        if (!empty($existingLoungeSource)) {
                            $loungeSourceProviderIds[] = $existingLoungeSource->getSourceId();
                            $loungeSourceProviderIds = array_unique($loungeSourceProviderIds);
                            $this->copyPropertiesToLoungeSource($parser, $lounge, $existingLoungeSource);
                            $loungeSourceAffectedIds[] = $existingLoungeSource->getId();

                            if (!$fromDb) {
                                $existingLoungeSource->setParseDate(new \DateTime());
                                $existingLoungeSource->setDeleteDate(null);
                            }
                        } elseif ($fromDb) {
                            $this->logger->error('there can be no new lounges in the DB mode');
                        } else {
                            $this->logger->info('create new lounge');
                            $newLoungesCount++;
                            $lounge->setParseDate(new \DateTime());
                            $lounge->setDeleteDate(null);
                            $this->em->persist($lounge);
                            $this->em->flush();
                            $loungeSourceAffectedIds[] = $lounge->getId();
                        }

                        $this->em->flush();
                    }

                    $this->logger->getLogProcessor()->replacePrevContext(['aircode' => $airCode, 'lounge' => null]);
                    $this->logger->info(sprintf('found %d lounges, new: %d', \count($lounges), $newLoungesCount));

                    if (!$fromDb) {
                        $deleted = $this->markAsDeleteLoungesInAirport($parserCode, $airCode, $loungeSourceAffectedIds);
                        $this->logger->info(sprintf('mark as deleted lounges: %d', $deleted));
                    }
                }

                if (!$fromDb && $optimalMode) {
                    $this->onParserSuccess($parser);
                }
            } catch (\Exception $e) {
                if (!$fromDb && $optimalMode) {
                    $this->onParserError($parser);
                    $context = $e instanceof HttpException ? $e->getContext() : [];
                    $this->logger->critical(sprintf('Parser error: %s', $e->getMessage()), array_merge($context, [
                        'trace' => $e->getTraceAsString(),
                    ]));
                } else {
                    $this->logger->error(sprintf('Parser error: %s', $e->getMessage()), array_merge($context ?? [], [
                        'trace' => $e->getTraceAsString(),
                    ]));
                }

                continue;
            } finally {
                if (\count($affectedAirCodes) > 0) {
                    $this->logger->getLogProcessor()->popContext();
                }
            }

            $listAircodes = array_unique(array_merge($listAircodes, $affectedAirCodes));

            // delete
            if (!$fromDb) {
                $skipDelete = false;
                $params = [$parserCode];
                $types = [\PDO::PARAM_STR];
                $filter = '';

                if (($allAircodes || $optimalMode) && \count($affectedAirCodes) === 0 && $parser->isEnabled()) {
                    if ($optimalMode) {
                        $this->logger->critical('no affected airports found, the parser is broken');
                    } else {
                        $this->logger->warning('no affected airports found, the parser is broken');
                    }
                }

                if ($allAircodes) {
                    if (count($affectedAirCodes) > 0) {
                        $filter = ' AND AirportCode NOT IN (?)';

                        if ($testMode) {
                            $filter .= " AND AirportCode LIKE '\$__'";
                        }

                        $params[] = $affectedAirCodes;
                        $types[] = Connection::PARAM_STR_ARRAY;
                    }
                } else {
                    $diff = array_diff($inFilter, $affectedAirCodes);

                    if (count($diff) > 0) {
                        $filter = ' AND AirportCode IN (?)';
                        $params[] = $diff;
                        $types[] = Connection::PARAM_STR_ARRAY;
                    } else {
                        $skipDelete = true;
                    }
                }

                if (!$skipDelete) {
                    $deleted = $this->connection->executeStatement("
                        UPDATE LoungeSource 
                        SET DeleteDate = NOW() + INTERVAL 3 MONTH
                        WHERE SourceCode = ? AND DeleteDate IS NULL $filter
                    ", $params, $types);
                    $this->logger->info(sprintf('mark as deleted lounges: %d', $deleted));
                }
            }
        }

        $this->logger->getLogProcessor()->setBaseContext([]);

        if ($skipMerge) {
            $this->logger->info('skip merging');

            return;
        }

        $this->logger->info('start merging');

        if ($allAircodes) {
            $filter = '';

            if ($testMode) {
                $filter = "WHERE AirportCode LIKE '\$__'";
            }

            $listAircodes = $this->connection->fetchFirstColumn("
                SELECT DISTINCT AirportCode FROM LoungeSource $filter
                UNION DISTINCT
                SELECT DISTINCT AirportCode FROM Lounge $filter
            ");
        } else {
            $listAircodes = array_unique(array_merge($listAircodes, $inFilter));
        }

        foreach ($listAircodes as $airCode) {
            $this->logger->getLogProcessor()->setBaseContext([
                'aircode' => $airCode,
            ]);
            $processedLounges = array_merge(
                $this->loungeSourceRep->findBy(['airportCode' => $airCode]),
                $this->loungeRep->findBy(['airportCode' => $airCode])
            );
            // refresh all lounges
            array_walk($processedLounges, fn (LoungeInterface $lounge) => $this->em->refresh($lounge));
            $clusters = $this->clusterizer->clusterize($processedLounges, LegacyMatcher::NAME);

            $this->logger->info(sprintf('merge airport, found %d clusters', \count($clusters)));

            foreach ($clusters as $cluster) {
                $this->mergeLounges($airCode, $cluster);
            }
        }

        $this->logger->getLogProcessor()->setBaseContext([]);
        $this->logger->info(sprintf('end merging, processed %d airports', \count($listAircodes)));
    }

    /**
     * @param LoungeInterface[] $cluster
     */
    private function mergeLounges(string $airCode, array $cluster): void
    {
        $this->logger->getLogProcessor()->replacePrevContext([
            'lounge' => null,
        ]);

        /** @var LoungeSource[] $sources */
        $sources = [];
        /** @var Lounge $lounge */
        $lounge = null;
        // true if lounge was created
        $newLounge = false;
        // default lounge, for default values
        $defaultLoungeSource = new LoungeSource();
        // state for managers
        $state = [];
        $addState = function (string $message) use (&$state) {
            $state[] = [
                'message' => $message,
                'date' => time(),
            ];
        };

        // separate sources and lounge
        foreach ($cluster as $item) {
            if ($item instanceof LoungeSource) {
                $sources[] = $item;
            } elseif ($item instanceof Lounge) {
                if (!is_null($lounge)) {
                    throw new \RuntimeException('More than one lounge in cluster');
                }

                $lounge = $item;
            } else {
                throw new \RuntimeException('Unknown item type');
            }
        }

        $newLounge = is_null($lounge);
        // prev sources for calculate changes
        $prevSources = [];

        if (!$newLounge) {
            $prevSources = array_map(function (LoungeSource $source) {
                return sprintf('%s-%s', $source->getSourceCode(), $source->getId());
            }, $lounge->getSources()->toArray());
        }

        // remove lounge from sources
        foreach ($sources as $source) {
            $source->setLounge(null);
        }

        // create lounge if not exists
        if ($newLounge) {
            $this->logger->info('create lounge');
            $lounge = new Lounge();
            $lounge->setAirportCode($airCode);
            $lounge->setCreateDate(new \DateTime());
            $lounge->setUpdateDate(new \DateTime());
            $lounge->setAvailable(false);
            $lounge->setVisible(false);
            $this->em->persist($lounge);
            $addState('create lounge');
        }

        $this->logger->getLogProcessor()->replacePrevContext([
            'lounge' => $lounge,
        ]);

        if (\count($sources) === 0) {
            $this->logger->info('no sources, delete');
            $this->em->remove($lounge);
        } else {
            $newSources = [];

            // set lounge to sources
            foreach ($sources as $source) {
                $source->setLounge($lounge);
                $newSources[] = sprintf('%s-%s', $source->getSourceCode(), $source->getId());
            }

            if (!$newLounge) {
                sort($prevSources);
                sort($newSources);

                if ($prevSources !== $newSources) {
                    $addState(sprintf(
                        'sources changed, prev: %s, new: %s',
                        \count($prevSources) > 0 ? implode(', ', $prevSources) : 'none',
                        \count($newSources) > 0 ? implode(', ', $newSources) : 'none'
                    ));
                }
            }

            $updatedProps = [];
            $updated = false;
            $parsersByPriorityMap = $this->priorityManager->getParsersByPriority();
            $patches = $this->priorityManager->getPatches();
            /** @var LoungeSource $candidateForUpdate */
            $candidateForUpdate = null;

            foreach ($parsersByPriorityMap as $parser) {
                foreach ($sources as $source) {
                    if ($source->getSourceCode() === $parser) {
                        $candidateForUpdate = $source;

                        break 2;
                    }
                }
            }

            if (is_null($candidateForUpdate)) {
                throw new \RuntimeException('Candidate for update not found');
            }

            $changedProperties = [];

            foreach ($this->loungeUpdateMap as $propName) {
                $newValues = [];
                $isAvailableProp = $propName === 'IsAvailable';
                $freezeAction = null;

                foreach ($lounge->getActions() as $action) {
                    $action = $action->getAction();

                    if (
                        $action instanceof FreezeAction
                        && in_array($propName, $action->getProps())
                        && (
                            count($action->getEmails()) > 0 || $action->isSendToSlack()
                        )
                    ) {
                        $freezeAction = $action;

                        break;
                    }
                }

                foreach ($sources as $source) {
                    $parser = $source->getSourceCode();
                    $isCandidateForUpdate = $source === $candidateForUpdate;
                    $patchIndex = array_search($parser, $patches[$propName] ?? [], true);

                    if ($patchIndex === false && !$isCandidateForUpdate) {
                        continue;
                    }

                    $isNotAvailablePropViaDeleteDate = false;
                    $defaultPropValueString = $this->stringify($this->propAccessor->getValue($defaultLoungeSource, $propName));
                    $oldPropValueString = $this->stringify($this->propAccessor->getValue($lounge, $propName));
                    $newPropValue = $this->propAccessor->getValue($source, $propName);

                    if ($isAvailableProp && !empty($source->getDeleteDate())) {
                        $newPropValue = false;
                        $isNotAvailablePropViaDeleteDate = true;
                    }

                    $newPropValueString = $this->stringify($newPropValue);

                    if (!isset($changedProperties[$source->getId()])) {
                        $changedProperties[$source->getId()] = $this->getChangedProperties($source);
                    }

                    $changed =
                        $newPropValueString !== $oldPropValueString
                        && !$newLounge
                        && (
                            in_array($propName, $changedProperties[$source->getId()])
                            || $isNotAvailablePropViaDeleteDate
                        );

                    $priorities = [];

                    if ($isCandidateForUpdate) {
                        $priorities[] = PHP_INT_MAX;
                    }

                    if ($patchIndex !== false) {
                        $priorities[] = $patchIndex;
                    }

                    $priority = \min($priorities);

                    if ($changed && !$isNotAvailablePropViaDeleteDate && !is_null($freezeAction)) {
                        $this->logger->info(sprintf('freeze action, prop %s', $propName));
                        $this->onFreezedPropChanged($source, $propName, $freezeAction);
                    }

                    if (
                        is_null($freezeAction)
                        && ($isCandidateForUpdate || $newPropValueString !== $defaultPropValueString)
                    ) {
                        $newValues[$priority] = [
                            'source' => $source,
                            'changed' => $newPropValueString !== $oldPropValueString && !$newLounge,
                            'value' => $newPropValue,
                        ];
                    }
                }

                if (!is_null($freezeAction)) {
                    continue;
                }

                if (\count($newValues) === 0) {
                    throw new \RuntimeException(sprintf('No values for prop %s', $propName));
                }

                ksort($newValues, SORT_NUMERIC);
                $newValues = array_values($newValues);
                $currentValue = $newValues[0];
                $this->propAccessor->setValue($lounge, $propName, $currentValue['value']);

                if (
                    $isAvailableProp
                    && !$currentValue['value']
                    && !empty($currentValue['source']->getDeleteDate())
                    && it($sources)->any(function (LoungeSource $source) {
                        return empty($source->getDeleteDate()) && $source->isAvailable();
                    })
                ) {
                    $this->logger->warning(sprintf('probably lounge is available, but most priority parser "%s" is broken', $currentValue['source']->getSourceCode()));
                }

                if ($currentValue['changed']) {
                    $updatedProps[] = $propName;
                    $updated = true;
                }
            }

            if (\count($updatedProps) > 0) {
                $addState(sprintf('updated props: %s', implode(', ', $updatedProps)));
            }

            if ($updated) {
                $lounge->setUpdateDate(new \DateTime());
            }

            $checkedInvisibleLounge = !is_null($lounge->getCheckedBy()) && !$lounge->isVisible();

            if (!$checkedInvisibleLounge) {
                $lounge->setVisible(true);
            } else {
                $addState('lounge is invisible and checked');
            }

            $lounge->addStateMessages($state);

            if ($newLounge) {
                $this->logger->info('merged lounge');
            } else {
                $this->logger->info(sprintf(
                    'merged lounge, updated props: %s',
                    \count($updatedProps) > 0 ? sprintf('[%s]', implode(', ', $updatedProps)) : 'none'
                ));
            }
        }

        $this->em->flush();
    }

    private function markAsDeleteLoungesInAirport(string $parser, string $airCode, array $excludeIds): int
    {
        $params = [$parser, $airCode];
        $types = [\PDO::PARAM_STR, \PDO::PARAM_STR];
        $filter = '';

        if (\count($excludeIds) > 0) {
            $filter = ' AND LoungeSourceID NOT IN (?)';
            $params[] = $excludeIds;
            $types[] = Connection::PARAM_INT_ARRAY;
        }

        $affected = $this->connection->executeStatement("
            UPDATE LoungeSource 
            SET DeleteDate = NOW() + INTERVAL 3 MONTH
            WHERE SourceCode = ? AND AirportCode = ? AND DeleteDate IS NULL $filter
        ", $params, $types);

        if (\count($excludeIds) === 0 && $affected >= 3) {
            $this->logger->critical('Looks like the parser is broken');
        }

        return $affected;
    }

    private function copyPropertiesToLoungeSource(ParserInterface $parser, LoungeSource $from, LoungeSource $to): void
    {
        if ($from->getSourceCode() !== $to->getSourceCode()) {
            throw new \InvalidArgumentException('Source codes are not equal');
        }

        if ($parser->getCode() !== $from->getSourceCode()) {
            throw new \InvalidArgumentException('Parser source code is not equal to lounge source code');
        }

        $changed = false;

        foreach ($this->loungeSourceUpdateMap as $prop) {
            if (
                $this->propAccessor->isReadable($to, $prop)
                && $this->propAccessor->isReadable($from, $prop)
                && $this->propAccessor->isWritable($to, $prop)
            ) {
                $fromValue = $this->stringify($this->propAccessor->getValue($from, $prop));
                $toValue = $this->stringify($this->propAccessor->getValue($to, $prop));
                $newValue = $this->propAccessor->getValue($from, $prop);

                if ($fromValue !== $toValue) {
                    if (!in_array($prop, $this->loungeSourceNotTrackableMap)) {
                        $changed = true;

                        $this->changeLoungeSourceQuery->executeQuery([
                            $to->getId(),
                            $prop,
                            $toValue,
                            $fromValue,
                            (new \DateTime())->format('Y-m-d H:i:s'),
                            $toValue,
                            $fromValue,
                        ]);

                        $sourceId = sprintf('%s-%s', $to->getSourceCode(), $to->getSourceId());

                        if (!isset($this->changedProps[$sourceId])) {
                            $this->changedProps[$sourceId] = [$prop];
                        } else {
                            $this->changedProps[$sourceId] = array_unique(array_merge($this->changedProps[$sourceId], [$prop]));
                        }
                    }

                    $this->propAccessor->setValue($to, $prop, $newValue);
                }
            } elseif (!$this->propAccessor->isWritable($to, $prop)) {
                $this->logger->error(sprintf('Can\'t change property "%s"', $prop));
            }
        }

        if ($changed) {
            $to->setUpdateDate(new \DateTime());
        }
    }

    /**
     * find existing lounge source by source code and source id.
     */
    private function findExistingLoungeSource(LoungeSource $lounge, array $excluding): ?LoungeSource
    {
        /** @var LoungeSource $existingLounge */
        $existingLounge = $this->loungeSourceRep->findOneBy([
            'sourceCode' => $lounge->getSourceCode(),
            'sourceId' => $lounge->getSourceId(),
        ]);

        if (empty($existingLounge)) {
            $this->logger->info('lounge was not found, trying to find similar');
            $clusters = $this->clusterizer->clusterize(
                array_merge(
                    array_filter(
                        $this->loungeSourceRep->findBy([
                            'airportCode' => $lounge->getAirportCode(),
                            'sourceCode' => $lounge->getSourceCode(),
                        ]),
                        fn (LoungeSource $source) => !in_array($source->getSourceId(), $excluding)
                    ),
                    [$lounge]
                ),
                LegacyMatcher::NAME,
                true
            );

            foreach ($clusters as $cluster) {
                if (in_array($lounge, $cluster) && count($cluster) > 1) {
                    $existingLounges = array_filter($cluster, function (LoungeSource $loungeSource) use ($lounge) {
                        return $loungeSource !== $lounge;
                    });

                    if (count($existingLounges) > 1) {
                        $this->logger->warning(sprintf('found more than one similar lounge: <%s>', implode('>, <', $existingLounges)));
                    }

                    $existingLounge = array_shift($existingLounges);

                    if (!empty($existingLounge)) {
                        $this->logger->info(sprintf('similar lounge <%s> was found', $existingLounge));

                        break;
                    } else {
                        $this->logger->error(sprintf('similar lounge was not found, cluster: <%s>', implode('>, <', $cluster)));
                    }

                    break;
                }
            }
        } else {
            $this->logger->info('lounge was found');

            if ($existingLounge->getAirportCode() !== $lounge->getAirportCode()) {
                $this->logger->warning(
                    sprintf(
                        'lounge was moved to another airport. Old: %s, new: %s',
                        $existingLounge->getAirportCode(),
                        $lounge->getAirportCode()
                    )
                );
            }
        }

        return $existingLounge;
    }

    private function stringify($value): ?string
    {
        if (is_scalar($value)) {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return (string) $value;
        }

        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (is_object($value)) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('c');
            }

            if ($value instanceof RawOpeningHours) {
                return $value->getRaw();
            }

            if ($value instanceof StructuredOpeningHours) {
                return \json_encode([
                    'tz' => $value->getTz(),
                    'data' => $this->recursiveSort($value->getData()),
                ]);
            }

            if ($value instanceof \JsonSerializable) {
                return \json_encode($value);
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
        }

        if (is_array($value)) {
            $value = array_map(fn ($v) => $this->stringify($v), $value);
            sort($value);

            return \json_encode($value);
        }

        return null;
    }

    private function recursiveSort(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSort($value);
            }
        }

        return $data;
    }

    private function onFreezedPropChanged(LoungeSource $loungeSource, string $propName, FreezeAction $action)
    {
        $key = $loungeSource->getId();

        if (!isset($this->freezedChangedProps[$key])) {
            $this->freezedChangedProps[$key] = [
                'name' => (string) $loungeSource,
                'changed' => [],
                'actions' => [],
            ];
        }

        $this->freezedChangedProps[$key]['changed'] = array_unique(array_merge($this->freezedChangedProps[$key]['changed'], [$propName]));
        $this->freezedChangedProps[$key]['actions'][] = $action;
    }

    private function sendEmailNotifications(): void
    {
        $emails = it($this->freezedChangedProps)
            ->flatMap(function (array $data) {
                yield from it($data['actions'])
                    ->flatMap(fn (FreezeAction $action) => $action->getEmails());
            })
            ->sort()
            ->unique()
            ->toArray();

        foreach ($emails as $emailAddress) {
            $this->logger->info(sprintf('send email to %s', $emailAddress));
            $listChanged = it($this->freezedChangedProps)
                ->mapIndexed(function (array $data, string $id) use ($emailAddress) {
                    $actions = it($data['actions'])
                        ->filter(fn (FreezeAction $action) => in_array($emailAddress, $action->getEmails()))
                        ->toArray();

                    if (empty($actions)) {
                        return null;
                    }

                    return sprintf('<li><a target="_blank" href="/manager/list.php?Schema=LoungeSource&LoungeSourceID=%d">%s</a>, changed props: %s</li>',
                        $id,
                        $data['name'],
                        implode(', ', it($actions)
                            ->flatMap(fn (FreezeAction $action) => array_intersect($data['changed'], $action->getProps()))
                            ->sort()
                            ->unique()
                            ->toArray()
                        )
                    );
                })
                ->filterNotNull()
                ->joinToString();

            $message = $this->mailer->getMessage(null, $emailAddress, 'Airport Lounges Changes');
            $message->setBody(sprintf('<ol>%s</ol>', $listChanged));
            $this->mailer->send($message, [
                Mailer::OPTION_SKIP_DONOTSEND => true,
                Mailer::OPTION_SKIP_STAT => true,
            ]);
        }
    }

    private function sendSlackNotifications(): void
    {
        $listChanged = it($this->freezedChangedProps)
            ->mapIndexed(function (array $data, string $key) {
                $actions = it($data['actions'])
                    ->filter(fn (FreezeAction $action) => $action->isSendToSlack())
                    ->flatten()
                    ->toArray();

                if (empty($actions)) {
                    return null;
                }

                return sprintf('<li><a target="_blank" href="/manager/list.php?Schema=LoungeSource&LoungeSourceID=%d">%s</a>, changed props: %s</li>',
                    $key,
                    $data['name'],
                    implode(', ', it($actions)
                        ->flatMap(fn (FreezeAction $action) => array_intersect($data['changed'], $action->getProps()))
                        ->sort()
                        ->unique()
                        ->toArray()
                    )
                );
            })
            ->filterNotNull()
            ->joinToString();

        if (!empty($listChanged)) {
            $this->logger->info('send slack notification');
        }
        // TODO: send to slack
    }

    /**
     * @return iterable<string, LoungeSource[]>
     */
    private function findAirportLounges(ParserInterface $parser, callable $airportFilter): iterable
    {
        if (!$parser->isEnabled()) {
            return [];
        }

        foreach ($parser->requestAirports($airportFilter) as $code => $airportData) {
            if (empty($code)) {
                throw new \RuntimeException('Airport code is empty');
            }

            $lounges = [];

            if (is_array($airportData) && !empty($airportData)) {
                foreach ($airportData as $loungeData) {
                    $lounge = $this->doGetLounge($parser, $code, $loungeData);

                    if ($lounge) {
                        $lounges[] = $lounge;
                    }
                }
            }

            yield $code => $lounges;
        }
    }

    private function doGetLounge(ParserInterface $parser, string $airportCode, $loungeData): ?LoungeSource
    {
        $airportCode = mb_strtoupper($airportCode);
        $parserCode = $parser->getCode();
        $lounge = $parser->getLounge($airportCode, $loungeData);

        if (is_null($lounge)) {
            return null;
        }

        if (StringHandler::isEmpty($lounge->getName())) {
            throw new \RuntimeException(sprintf('Lounge name is empty for airport "%s", terminal: "%s", parser "%s", url: "%s"', $airportCode, $lounge->getTerminal() ?? 'unknown', $parserCode, $lounge->getUrl() ?? 'unknown'));
        }

        if (StringHandler::isEmpty($lounge->getSourceId())) {
            throw new \RuntimeException(sprintf('Lounge source ID is empty for airport "%s", terminal: "%s", parser "%s", url: "%s"', $airportCode, $lounge->getTerminal() ?? 'unknown', $parserCode, $lounge->getUrl() ?? 'unknown'));
        }

        $lounge
            ->setAirportCode($airportCode)
            ->setSourceCode($parserCode)
            ->setPageBody(json_encode($loungeData));

        return $lounge;
    }

    private function onParserSuccess(ParserInterface $parser): void
    {
        $this->memcached->set(sprintf(self::CACHE_KEY, (string) $parser), time(), 3600 * 24);
    }

    private function onParserError(ParserInterface $parser): void
    {
        $this->memcached->delete(sprintf(self::CACHE_KEY, (string) $parser));
    }

    private function isParserAlreadySuccess(ParserInterface $parser): ?int
    {
        $result = $this->memcached->get(sprintf(self::CACHE_KEY, (string) $parser));

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getChangedProperties(LoungeSource $source): array
    {
        return $this->connection->executeQuery('
            SELECT Property 
            FROM LoungeSourceChange 
            WHERE LoungeSourceID = ? AND ChangeDate >= ?
        ', [$source->getId(), $this->startDateTime->format('Y-m-d H:i:s')])->fetchFirstColumn();
    }
}
