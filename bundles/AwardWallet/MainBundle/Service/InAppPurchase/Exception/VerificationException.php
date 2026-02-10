<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\Exception;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderInterface;

class VerificationException extends \RuntimeException
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var array|object
     */
    private $requestData;

    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * @var bool
     */
    private $temporary = false;

    public function __construct(?Usr $user = null, $requestData, ProviderInterface $provider, string $message, int $code = 0, ?\Throwable $throwable = null)
    {
        $this->user = $user;
        $this->requestData = $requestData;
        $this->provider = $provider;
        parent::__construct($message, $code, $throwable);
    }

    public static function withThrowable(\Throwable $throwable, ?Usr $user = null, $requestData, ProviderInterface $provider): self
    {
        return new static($user, $requestData, $provider, $throwable->getMessage(), $throwable->getCode(), $throwable);
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    /**
     * @return array|object
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function setTemporary(bool $temporary): self
    {
        $this->temporary = $temporary;

        return $this;
    }

    public function getFormattedMessage(): string
    {
        return sprintf(
            "platform: %s, message: \"%s\", userId: %d, userName: %s, file: %s:%d\nrequestData: %s",
            $this->provider->getPlatformId(),
            $this->getMessage(),
            isset($this->user) ? $this->user->getUserid() : 0,
            isset($this->user) ? $this->user->getFullName() : 'null',
            $this->getFile(),
            $this->getLine(),
            print_r($this->requestData, true)
        );
    }

    public function getContext(): array
    {
        return array_merge(LoggerContext::get($this->user), [
            'message' => $this->getMessage(),
            'platform' => $this->provider->getPlatformId(),
            'requestData' => print_r($this->requestData, true),
        ]);
    }
}
