<?php

namespace AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder;

use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\MapMatcherInterface;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\UnmatchedAnchor;

class Sorter
{
    /**
     * @var list<MapMatcherInterface>
     */
    private array $matchers;

    /**
     * @param list<MapMatcherInterface> $matchers
     */
    public function __construct(array $matchers = [])
    {
        $this->matchers = $matchers;
    }

    public function addMapMatcher(MapMatcherInterface $matcher): self
    {
        $this->matchers[] = $matcher;

        return $this;
    }

    /**
     * @template T
     * @param list<T> $itemsList ex.: [['id' => 'foo', ...], ['id' => 'bar', ...], ['id' => 'bazz', ...]]
     * @param array<array-key, int> $itemsIdxMap ex.: ['foo' => 0, 'bar' => 1, 'bazz' => 2]
     * @return list<T> ex.: [['id' => 'bar'], ['id' => 'bazz'], ['id' => 'foo']]
     */
    public function sort(array $itemsList, array $itemsIdxMap): array
    {
        $globalIdxMap = [];
        $unsortedAnchorIdx = null;

        foreach ($this->matchers as $matcherIdx => $matcher) {
            if ($matcher instanceof UnmatchedAnchor) {
                if (null !== $unsortedAnchorIdx) {
                    throw new \LogicException('Duplicate unmatched anchor matcher');
                }

                $unsortedAnchorIdx = $matcherIdx;

                continue;
            }

            foreach ($matcher->matchMap($itemsIdxMap) as $matchedItemIdx) {
                if (\array_key_exists($matchedItemIdx, $globalIdxMap)) {
                    throw new \LogicException('Duplicate item in sort map for matcher: ' . $matcherIdx);
                }

                $globalIdxMap[$matchedItemIdx] = $matcherIdx;
            }
        }

        \uksort($itemsList, fn ($idxA, $idxB) =>
            (($globalIdxMap[$idxA] ?? $unsortedAnchorIdx) <=> ($globalIdxMap[$idxB] ?? $unsortedAnchorIdx)) ?:
                ($idxA <=> $idxB)
        );

        return \array_values($itemsList);
    }
}
