<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Type;

/**
 * @Serializer\Discriminator(field = "type",
 * map = {
 * 		"account": "AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Account",
 * 		"email": "AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email",
 * 		"confirmationNumber": "AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\ConfirmationNumber",
 * 		"tripit": "AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Tripit"
 * })
 */
abstract class AbstractSource implements SourceInterface
{
    /**
     * @Type("array<string>")
     * @var string[]
     */
    private $dates = [];

    public function __construct()
    {
        $this->dates[] = date('Y-m-d H:i:s');
    }

    public function stillExists(): void
    {
        $this->dates[] = date('Y-m-d H:i:s');
        $this->dates = array_slice($this->dates, -3);
    }

    public function getDate(): ?\DateTimeInterface
    {
        return null;
    }
}
