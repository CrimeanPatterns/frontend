<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;

class TravelerObject
{
    /**
     * @var string
     * @Type("string")
     */
    private $first_name;
    /**
     * @var string
     * @Type("string")
     */
    private $middle_name;
    /**
     * @var string
     * @Type("string")
     */
    private $last_name;
    /**
     * @var int
     * @Type("integer")
     */
    private $ticket_num;

    public function getFirstName(): string
    {
        return ucfirst(strtolower($this->first_name));
    }

    public function getMiddleName(): string
    {
        return ucfirst(strtolower($this->middle_name));
    }

    public function getLastName(): string
    {
        return ucfirst(strtolower($this->last_name));
    }

    public function getFullName(): string
    {
        return implode(' ', array_filter([
            $this->getFirstName(),
            $this->getMiddleName(),
            $this->getLastName(),
        ]));
    }

    public function getTicketNum()
    {
        return $this->ticket_num;
    }
}
