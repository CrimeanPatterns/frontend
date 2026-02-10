<?php

namespace AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher;

use Symfony\Component\Form\FormBuilderInterface;

class ArrayKeyMatcher implements MapMatcherInterface
{
    private ?string $name = null;
    private ?string $regex = null;

    /**
     * @param FormBuilderInterface $value
     */
    public function match($value): bool
    {
        if (null !== $this->name) {
            return $this->name === $value->getName();
        }

        return false;
    }

    public static function create(string $name): self
    {
        $matcher = new self();
        $matcher->name = $name;

        return $matcher;
    }

    public static function createByRegex(string $regex): self
    {
        $matcher = new self();
        $matcher->regex = $regex;

        return $matcher;
    }

    public function matchMap(array $map): array
    {
        if (
            (null !== $this->name)
            && (null !== ($idx = $map[$this->name] ?? null))
        ) {
            return [$idx];
        }

        if (null !== $this->regex) {
            $idxs = [];

            foreach ($map as $key => $idx) {
                if (\preg_match($this->regex, $key)) {
                    $idxs[] = $idx;
                }
            }

            return $idxs;
        }

        return [];
    }
}
