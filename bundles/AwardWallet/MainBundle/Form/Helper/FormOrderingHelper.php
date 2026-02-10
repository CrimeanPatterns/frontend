<?php

namespace AwardWallet\MainBundle\Form\Helper;

use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Sorter;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FormOrderingHelper
{
    public const SIDE_AFTER = 1;
    public const SIDE_BEFORE = 2;

    /**
     * @var FormBuilderInterface
     */
    private $parent;

    public function __construct(FormBuilderInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param string $childWhere form name to move by around
     * @param string|FormBuilderInterface $childWhat form name to be moved
     * @return self
     */
    public function moveBefore($childWhere, $childWhat)
    {
        return $this->move(self::SIDE_BEFORE, $childWhere, $childWhat);
    }

    /**
     * @param string $childWhere form name to move by around
     * @param string|FormBuilderInterface $childWhat form name to be moved
     * @return self
     */
    public function moveAfter($childWhere, $childWhat)
    {
        return $this->move(self::SIDE_AFTER, $childWhere, $childWhat);
    }

    /**
     * @param array<string, FormView> $children
     * @return array<string, FormView>
     */
    public static function useFormViewSorter(array $children, Sorter $sorter): array
    {
        /** @var \SplObjectStorage<FormView, string> $map */
        $map = new \SplObjectStorage();

        foreach ($children as $name => $child) {
            $map[$child] = $name;
        }

        $childrenSortedList = $sorter->sort(
            \array_values($children),
            it($children)
            ->keys()
            ->flip()
            ->toArrayWithKeys()
        );

        $result = [];

        foreach ($childrenSortedList as $child) {
            $result[$map[$child]] = $child;
        }

        return $result;
    }

    /**
     * @param int $side
     * @param string $childWhere form name to move by around
     * @param string|FormBuilderInterface $childWhat
     * @return self
     */
    protected function move($side, $childWhere, $childWhat)
    {
        if (
            (
                is_string($childWhat)
                && !$this->parent->has($childWhat)
            )
            || !$this->parent->has($childWhere)
        ) {
            throw new \InvalidArgumentException("Forms '{$childWhat}', '{$childWhere}' should exist in parent form!");
        }

        $children = $this->parent->all();

        foreach ($children as $childName => $childForm) {
            $this->parent->remove($childName);
        }

        if (is_string($childWhat)) {
            $whatForm = $children[$childWhat];
            unset($children[$childWhat]);
        } else {
            $whatForm = $childWhat;
        }

        foreach ($children as $childName => $childForm) {
            if (self::SIDE_AFTER === $side) {
                $this->parent->add($childForm);
            }

            if ($childName === $childWhere) {
                $this->parent->add($whatForm);
            }

            if (self::SIDE_BEFORE === $side) {
                $this->parent->add($childForm);
            }
        }

        return $this;
    }
}
