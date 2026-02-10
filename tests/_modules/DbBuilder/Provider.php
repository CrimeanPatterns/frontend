<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class Provider extends AbstractDbEntity
{
    /**
     * @var ProviderProperty[]
     */
    private array $properties;

    private ?UserPointValue $userPointValue = null;

    public function __construct(?string $name = null, array $fields = [], array $properties = [])
    {
        if (is_null($name)) {
            $name = 'Test Program ' . StringHandler::getRandomCode(6);
        }

        parent::__construct(array_merge([
            'DisplayName' => $name,
            'ShortName' => $name,
            'LoginURL' => '',
            'State' => PROVIDER_ENABLED,
            'Code' => 'test' . bin2hex(random_bytes(6)),
        ], $fields, [
            'Name' => $name,
        ]));

        $this->properties = $properties;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function getUserPointValue(): ?UserPointValue
    {
        return $this->userPointValue;
    }

    public function setUserPointValue(?UserPointValue $userPointValue): self
    {
        $this->userPointValue = $userPointValue;

        return $this;
    }

    public static function createWithCode(string $code, array $fields = []): self
    {
        return new self(null, array_merge($fields, ['Code' => $code]));
    }
}
