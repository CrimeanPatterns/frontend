<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Sortable
{
    private FieldInterface $field;

    private bool $isAsc;

    public function __construct(FieldInterface $field, bool $isAsc)
    {
        $this->field = $field;
        $this->isAsc = $isAsc;
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function isAsc(): bool
    {
        return $this->isAsc;
    }
}
