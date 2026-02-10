<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation\Type;

/**
 * @NoDI()
 */
class ConfirmationNumber extends AbstractSource
{
    /**
     * @Type("array")
     * @var array
     */
    private $confirmationFields;
    /**
     * @var string
     * @Type("string")
     */
    private $providerCode;

    public function __construct(string $providerCode, array $confirmationFields)
    {
        parent::__construct();
        $this->confirmationFields = $confirmationFields;
        $this->providerCode = $providerCode;
    }

    public function getId(): string
    {
        return "c." . json_encode($this->confirmationFields);
    }

    /**
     * used in setSoourceId, deprecated
     * should be deleted after migrations and release.
     *
     * @TODO: delete after release
     */
    public function getOldId(): ?string
    {
        return null;
    }

    /**
     * used by serializer.
     */
    public function getConfirmationFields(): array
    {
        return $this->confirmationFields;
    }

    /**
     * used by serializer.
     */
    public function getProviderCode(): string
    {
        return $this->providerCode;
    }
}
