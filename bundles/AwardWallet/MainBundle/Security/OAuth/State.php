<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @NoDI()
 */
class State
{
    /**
     * @var string
     * @Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $type;
    /**
     * @var int
     * @Type("int")
     */
    private $userId;
    /**
     * @var int
     * @Type("int")
     */
    private $agentId;
    /**
     * @var string
     * @Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $platform;
    /**
     * @var string
     * @Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $host;
    /**
     * @var bool
     * @Type("bool")
     * @Assert\NotNull
     */
    private $mailboxAccess;
    /**
     * @var bool
     * @Type("bool")
     * @Assert\NotNull
     */
    private $profileAccess;
    /**
     * @var string
     * @Type("string")
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $action;
    /**
     * @var string
     * @Type("string")
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $csrf;
    /**
     * @var array
     * @Type("array<string,string>")
     * @Assert\NotNull
     */
    private $query;
    /**
     * @var bool
     * @Type("bool")
     * @Assert\NotNull
     */
    private $rememberMe;

    public function __construct(
        string $type,
        ?int $userId,
        ?int $agentId,
        string $platform,
        string $host,
        bool $mailboxAccess,
        bool $profileAccess,
        string $action,
        string $csrf,
        array $query,
        bool $rememberMe
    ) {
        $this->type = $type;
        $this->userId = $userId;
        $this->agentId = $agentId;
        $this->platform = $platform;
        $this->host = $host;
        $this->mailboxAccess = $mailboxAccess;
        $this->profileAccess = $profileAccess;
        $this->action = $action;
        $this->csrf = $csrf;
        $this->query = $query;
        $this->rememberMe = $rememberMe;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getAgentId(): ?int
    {
        return $this->agentId;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function isMailboxAccess(): bool
    {
        return $this->mailboxAccess;
    }

    public function isProfileAccess(): bool
    {
        return $this->profileAccess;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCsrf(): string
    {
        return $this->csrf;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function isAllFieldsSet(): bool
    {
        foreach ($this as $key => $value) {
            if ($value === null) {
                return false;
            }
        }

        return true;
    }
}
