<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;

class ProfileEmailAddressObject
{
    /**
     * @var string
     * @Type("string")
     */
    private $uuid;
    /**
     * @var string
     * @Type("string")
     */
    private $uuid_ref;
    /**
     * @var string
     * @Type("string")
     */
    private $email_ref;
    /**
     * @var string
     * @Type("string")
     */
    private $address;
    /**
     * @var bool
     * @Type("bool")
     */
    private $is_auto_import;
    /**
     * @var bool
     * @Type("bool")
     */
    private $is_confirmed;
    /**
     * @var bool
     * @Type("bool")
     */
    private $is_primary;

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getUuidRef()
    {
        return $this->uuid_ref;
    }

    public function getEmailRef()
    {
        return $this->email_ref;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getIsAutoImport(): bool
    {
        return $this->is_auto_import;
    }

    public function getIsConfirmed(): bool
    {
        return $this->is_confirmed;
    }

    public function getIsPrimary(): bool
    {
        return $this->is_primary;
    }
}
