<?php

namespace AwardWallet\MainBundle\Parameter;

class UnremovableUsersParameter implements ParameterInterface
{
    /**
     * @var DefaultBookerParameter
     */
    private $defaultBooker;

    public function __construct(DefaultBookerParameter $defaultBooker)
    {
        $this->defaultBooker = $defaultBooker;
    }

    /**
     * @return int[]
     */
    public function get(): array
    {
        return [
            116000,
            $this->defaultBooker->get(),
        ];
    }
}
