<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

/**
 * Interface ConverterInterface.
 *
 * @property int|string $id
 * @property $entity
 */
interface ConverterInterface
{
    public function getEntity();
}
