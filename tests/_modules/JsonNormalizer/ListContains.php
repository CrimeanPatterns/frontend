<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use AwardWallet\Tests\Modules\AwAssert;
use PHPUnit\Framework\ExpectationFailedException;

class ListContains implements PlaceholderProcessorInterface
{
    use PlaceholderProcessorHelperTrait;

    private array $sublist;
    private ?int $position;

    public function __construct(array $sublist, ?int $position = null)
    {
        $this->sublist = $sublist;
        $this->position = $position;
    }

    public function process($value, string $propertyPath, Context $context)
    {
        if (
            !\is_array($value)
            || (\array_keys($value) !== \range(0, \count($value) - 1))
        ) {
            return $this->makeError(
                'Is not a list',
                $propertyPath,
                [
                    'value' => $value,
                ]
            );
        }

        if (isset($this->position)) {
            $searchDomain = \array_slice($value, 0, \count($this->sublist));
        } else {
            $searchDomain = $value;
        }

        try {
            AwAssert::assertArrayContainsArray($searchDomain, $this->sublist);

            return $this->makeStub();
        } catch (ExpectationFailedException $e) {
            return $this->makeError(
                $e->getMessage(),
                $propertyPath,
                [
                    'value' => $value,
                ]
            );
        }
    }

    public static function getCode(): string
    {
        return 'list-contains';
    }

    private static function searchSublistInList(array $list, array $sublist, ?int $startOffset = null): ?int
    {
        $sublistLength = \count($sublist);
        $listLength = \count($list);

        if ($sublistLength > $listLength) {
            return null;
        }

        $startOffset = $startOffset ?? 0;

        for ($i = $startOffset; $i < $listLength; ++$i) {
            if ($list[$i] === $sublist[0]) {
                $j = 1;

                while ($j < $sublistLength && $list[$i + $j] === $sublist[$j]) {
                    ++$j;
                }

                if ($j === $sublistLength) {
                    return $i;
                }
            }
        }

        return null;
    }
}
