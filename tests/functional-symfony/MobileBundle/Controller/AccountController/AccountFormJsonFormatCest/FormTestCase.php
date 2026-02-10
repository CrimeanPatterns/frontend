<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\AccountController\AccountFormJsonFormatCest;

/**
 * @psalm-type CallableBefore = callable(\TestSymfonyGuy, FormTestCase): mixed
 * @psalm-type CallableAfter = callable(\TestSymfonyGuy, FormTestCase,  int, mixed): void
 */
class FormTestCase
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private string $name;
    private $provider;
    private string $mobileVersion;
    private string $jsonPath;
    /**
     * @var CallableBefore
     */
    private $callableBefore;
    private ?string $login = null;
    private ?string $password = null;
    private array $fields = [];
    /**
     * @var CallableAfter
     */
    private $callableAfter;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function new(string $name): FormTestCase
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider($provider): FormTestCase
    {
        $this->provider = $provider;

        return $this;
    }

    public function getMobileVersion(): string
    {
        return $this->mobileVersion;
    }

    public function setMobileVersion(string $mobileVersion): FormTestCase
    {
        $this->mobileVersion = $mobileVersion;

        return $this;
    }

    public function getJsonPath(): string
    {
        return $this->jsonPath;
    }

    public function setJsonPath(string $jsonPath): FormTestCase
    {
        $this->jsonPath = $jsonPath;

        return $this;
    }

    /**
     * @return ?CallableBefore
     */
    public function getCallableBefore(): ?callable
    {
        return $this->callableBefore;
    }

    public function setCallableBefore(callable $callableBefore): FormTestCase
    {
        $this->callableBefore = $callableBefore;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): FormTestCase
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): FormTestCase
    {
        $this->password = $password;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): FormTestCase
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return ?CallableAfter
     */
    public function getCallableAfter(): ?callable
    {
        return $this->callableAfter;
    }

    public function setCallableAfter(callable $callableAfter): FormTestCase
    {
        $this->callableAfter = $callableAfter;

        return $this;
    }

    public function toCase(): array
    {
        return ['case' => $this];
    }
}
