<?php

namespace AwardWallet\Tests\Modules\Utils\Prophecy\Argument\Token;

use AwardWallet\MainBundle\Globals\Utils\ArrayContainsComparator;
use Prophecy\Argument\Token\TokenInterface;

class ContainsArrayToken implements TokenInterface
{
    /**
     * @var array
     */
    protected $needle;

    public function __construct(array $needle)
    {
        $this->needle = $needle;
    }

    public function __toString()
    {
        return var_export($this->needle, true);
    }

    public function scoreArgument($argument)
    {
        return ArrayContainsComparator::containsArray($this->needle, $argument) ? 10 : false;
    }

    public function isLast()
    {
        return false;
    }
}
