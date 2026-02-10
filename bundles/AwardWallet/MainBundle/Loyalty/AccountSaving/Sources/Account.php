<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class Account extends AbstractSource
{
    /**
     * @var int
     * @Serializer\Type("int")
     */
    private $accountId;

    public function __construct(int $accountId)
    {
        parent::__construct();
        $this->accountId = $accountId;
    }

    public function getId(): string
    {
        return "a." . $this->accountId;
    }

    public function getOldId(): ?string
    {
        return null;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }
}
