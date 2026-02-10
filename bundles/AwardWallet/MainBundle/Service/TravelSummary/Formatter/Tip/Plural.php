<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Plural extends Translation
{
    /**
     * @param callable|float|int $count
     */
    public function __construct(string $key, $count, array $params = [])
    {
        parent::__construct($key, array_merge($params, ['%count%' => $count]));
    }

    /**
     * @return float|int|null
     */
    public function getCount()
    {
        return $this->getParam('%count%');
    }
}
