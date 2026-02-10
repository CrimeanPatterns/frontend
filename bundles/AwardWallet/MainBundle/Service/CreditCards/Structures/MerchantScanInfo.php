<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Structures;

class MerchantScanInfo
{
    public ?int $merchantId = null;
    public ?string $name = null;
    public ?string $displayName = null;
    public string $providers = "";
    public string $categories = "";
    public string $multipliers = "";
    public string $creditCards = "";
    public int $transactions = 0;
    public ?\SplDoublyLinkedList $names = null;
    public int $firstSeenDate;
    public int $lastSeenDate;

    public function __construct(?int $merchantId, ?string $name, ?string $displayName, int $date)
    {
        $this->merchantId = $merchantId;
        $this->name = $name;
        $this->displayName = $displayName;
        $this->firstSeenDate = $date;
        $this->lastSeenDate = $date;
    }
}
