<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource;

class Clusterizer
{
    /**
     * @var MatcherInterface[]
     */
    private array $matchers;

    private array $debugSimilarity = [];

    private array $debugMessages = [];

    public function __construct(iterable $loungeMatchers)
    {
        $this->matchers = [];

        foreach ($loungeMatchers as $matcher) {
            $this->matchers[$matcher::getName()] = $matcher;
        }
    }

    /**
     * @param LoungeInterface[]|Lounge[]|LoungeSource[] $lounges
     * @return array groups
     */
    public function clusterize(array $lounges, string $matcher, bool $allowSameParserMatching = false): array
    {
        if (!isset($this->matchers[$matcher])) {
            throw new \InvalidArgumentException(sprintf('Matcher "%s" not found', $matcher));
        }

        $matcher = $this->matchers[$matcher];
        $threshold = $matcher::getThreshold();

        // order lounges
        $lounges = $this->orderLounges($lounges);

        // calculate similarity matrix
        $similarity = [];
        $loungeMap = [];

        foreach ($lounges as $lounge1) {
            $lounge1Id = $this->getLoungeId($lounge1);
            $loungeMap[$lounge1Id] = $lounge1;

            foreach ($lounges as $lounge2) {
                $lounge2Id = $this->getLoungeId($lounge2);
                $loungeMap[$lounge2Id] = $lounge2;

                if (isset($similarity[$lounge1Id][$lounge2Id], $similarity[$lounge2Id][$lounge1Id])) {
                    continue;
                }

                if ($lounge1Id === $lounge2Id) {
                    $similarity[$lounge1Id][$lounge2Id] = 1;
                    $similarity[$lounge2Id][$lounge1Id] = 1;
                } else {
                    $sameParser =
                        $lounge1 instanceof LoungeSource
                        && $lounge2 instanceof LoungeSource
                        && $lounge1->getSourceCode() === $lounge2->getSourceCode();

                    // only lounges with same airport code can be compared
                    if (mb_strtolower($lounge1->getAirportCode()) !== mb_strtolower($lounge2->getAirportCode())) {
                        $currentSimilarity = 0;
                    } elseif (
                        $lounge1 instanceof Lounge
                        && $lounge2 instanceof Lounge
                        && !is_null($lounge1->getId())
                        && !is_null($lounge2->getId())
                    ) {
                        $currentSimilarity = $lounge1->getId() === $lounge2->getId() ? 1 : 0;
                    } elseif ($sameParser && !$allowSameParserMatching) {
                        $currentSimilarity = 0;
                    } else {
                        $currentSimilarity = $matcher->getSimilarity($lounge1, $lounge2);
                    }

                    $similarity[$lounge1Id][$lounge2Id] = $currentSimilarity;
                    $similarity[$lounge2Id][$lounge1Id] = $similarity[$lounge1Id][$lounge2Id];
                }
            }
        }

        // convert similarity matrix
        $this->debugSimilarity = [];

        foreach ($similarity as $lounge1Id => $loungeSimilarity) {
            $lounge1DebugKey = $this->getLoungeDebugKey($loungeMap[$lounge1Id]);

            foreach ($loungeSimilarity as $lounge2Id => $similarityValue) {
                if ($lounge1Id === $lounge2Id) {
                    continue;
                }

                $lounge2DebugKey = $this->getLoungeDebugKey($loungeMap[$lounge2Id]);
                $percent = round($similarityValue * 100, 2);
                $this->debugSimilarity[$lounge1DebugKey][$lounge2DebugKey] = $percent;
            }

            if (isset($this->debugSimilarity[$lounge1DebugKey])) {
                arsort($this->debugSimilarity[$lounge1DebugKey], SORT_NUMERIC);
            }
        }

        /** @var LoungeInterface[][] $clusters */
        $clusters = [];
        $this->debugMessages = [];

        foreach ($lounges as $lounge) {
            $loungeId = $this->getLoungeId($lounge);
            $loungeDebugKey = $this->getLoungeDebugKey($lounge);
            $clustersCandidates = [];

            foreach ($clusters as $clusterId => $cluster) {
                $clusterSimilarity = array_map(function (LoungeInterface $otherLounge) use ($similarity, $loungeId) {
                    return $similarity[$loungeId][$this->getLoungeId($otherLounge)];
                }, $cluster);
                $minSimilarity = min($clusterSimilarity);
                $match = $minSimilarity >= $threshold;

                if ($match) {
                    $clustersCandidates[$clusterId] = $clusterSimilarity;
                }

                $this->debugMessages[$loungeDebugKey][] = [
                    'type' => 'match',
                    'message' => $match ? 'match' : 'not match',
                    'similarity' => $minSimilarity,
                    'clusterId' => $clusterId,
                    'clusterSimilarity' => $clusterSimilarity,
                ];
            }

            if (count($clustersCandidates) > 0) {
                // find best cluster
                $bestClusterId = null;
                $bestClusterSimilarity = 0;

                foreach ($clustersCandidates as $clusterId => $clusterSimilarity) {
                    // calculate average similarity
                    $clusterSimilarity = array_sum($clusterSimilarity) / count($clusterSimilarity);

                    if ($clusterSimilarity > $bestClusterSimilarity) {
                        $bestClusterId = $clusterId;
                        $bestClusterSimilarity = $clusterSimilarity;
                    }
                }

                $this->debugMessages[$loungeDebugKey][] = [
                    'type' => 'added',
                    'message' => 'add to cluster',
                    'candidates' => $clustersCandidates,
                    'bestClusterId' => $bestClusterId,
                    'bestClusterSimilarity' => $bestClusterSimilarity,
                ];

                $clusters[$bestClusterId][] = $lounge;
            } else {
                $this->debugMessages[$loungeDebugKey][] = [
                    'type' => 'new',
                    'message' => 'new cluster',
                ];

                $clusters[] = [$lounge];
            }
        }

        return $clusters;
    }

    private function getLoungeId(LoungeInterface $lounge): string
    {
        return spl_object_hash($lounge);
    }

    private function getLoungeDebugKey(LoungeInterface $lounge): string
    {
        $prefix = $lounge instanceof LoungeSource ? 'S' : 'L';
        $id = $lounge instanceof LoungeSource ? $lounge->getId() : $lounge->getId();
        $idWithPrefix = $id ? sprintf('%s%d', $prefix, $id) : $prefix;
        $terminal = $lounge->getTerminal();
        $gate1 = $lounge->getGate();
        $gate2 = $lounge->getGate2();
        $range = array_unique(array_filter([$gate1, $gate2]));

        return sprintf(
            '[%s] %s, "%s", %s, %s',
            $lounge->getAirportCode(),
            $idWithPrefix,
            $lounge->getName(),
            $terminal ? sprintf('"%s"', $terminal) : '<NULL>',
            count($range) > 0 ? sprintf('"%s"', implode(', ', $range)) : '<NULL>'
        );
    }

    /**
     * @param LoungeInterface[]|Lounge[]|LoungeSource[] $lounges
     * @return LoungeInterface[]|Lounge[]|LoungeSource[]
     */
    private function orderLounges(array $lounges): array
    {
        usort($lounges, function (LoungeInterface $lounge1, LoungeInterface $lounge2) {
            $emptyTerminal1 = empty($lounge1->getTerminal());
            $emptyTerminal2 = empty($lounge2->getTerminal());
            $gatesCount1 = \count(array_filter([$lounge1->getGate(), $lounge1->getGate2()]));
            $gatesCount2 = \count(array_filter([$lounge2->getGate(), $lounge2->getGate2()]));

            return [
                $lounge2 instanceof LoungeSource,
                $lounge1 instanceof LoungeSource ? $lounge1->getSourceCode() : 0,
                $lounge1->getAirportCode(),
                !$emptyTerminal2 && $gatesCount2 > 0,
                !$emptyTerminal2 && $gatesCount2 === 0,
                $emptyTerminal2 && $gatesCount2 > 0,
                $emptyTerminal2 && $gatesCount2 === 0,
            ] <=> [
                $lounge1 instanceof LoungeSource,
                $lounge2 instanceof LoungeSource ? $lounge2->getSourceCode() : 0,
                $lounge2->getAirportCode(),
                !$emptyTerminal1 && $gatesCount1 > 0,
                !$emptyTerminal1 && $gatesCount1 === 0,
                $emptyTerminal1 && $gatesCount1 > 0,
                $emptyTerminal1 && $gatesCount1 === 0,
            ];
        });

        return $lounges;
    }
}
