<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class PostCheckAccountPackageResponse
{
    /**
     * @var PostCheckAccountResponse[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse>")
     */
    private $package;

    /**
     * @var PostCheckErrorResponse[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\PostCheckErrorResponse>")
     */
    private $errors;

    /**
     * @param array
     * @return $this
     */
    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @return array
     */
    public function getPackage()
    {
        return $this->package;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
