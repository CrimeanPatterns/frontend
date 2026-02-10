<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Account as AccountSource;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\ConfirmationNumber;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Tripit;

class SavingOptions
{
    public const INITIALIZED_BY_USER = true;
    public const INITIALIZED_AUTOMATICALLY = false;

    public const PARTIAL_UPDATE = true;
    public const FULL_UPDATE = false;

    /**
     * @var string[]|null
     */
    private $confirmationFields;

    /**
     * @var Owner
     */
    private $owner;

    /**
     * @var Account|null
     */
    private $account;

    /**
     * @var bool
     */
    private $userInitialized;

    /**
     * True if the data we're updating with may be incomplete.
     *
     * @var bool
     */
    private $partialUpdate;

    /**
     * @var SourceInterface
     */
    private $source;
    /**
     * @var bool
     */
    private $silent;
    /**
     * @var bool
     */
    private $updateOnly;

    private ?string $providerCode;

    private function __construct(
        Owner $owner,
        ?Account $account,
        bool $userInitialized,
        bool $partialUpdate,
        ?array $confirmationFields,
        SourceInterface $source,
        bool $silent,
        bool $updateOnly,
        ?string $providerCode = null
    ) {
        $this->owner = $owner;
        $this->account = $account;
        $this->userInitialized = $userInitialized;
        $this->partialUpdate = $partialUpdate;
        $this->confirmationFields = $confirmationFields;
        $this->source = $source;
        $this->silent = $silent;
        $this->updateOnly = $updateOnly;
        $this->providerCode = $providerCode;
    }

    /**
     * @return string[]|null
     */
    public function getConfirmationFields(): ?array
    {
        return $this->confirmationFields;
    }

    public function getOwner(): Owner
    {
        return $this->owner;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function isInitializedByUser(): bool
    {
        return $this->userInitialized;
    }

    public function isPartialUpdate(): bool
    {
        return $this->partialUpdate;
    }

    public function isUserInitialized(): bool
    {
        return $this->userInitialized;
    }

    public function getSource(): SourceInterface
    {
        return $this->source;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function isUpdateOnly(): bool
    {
        return $this->updateOnly;
    }

    public function getProviderCode(): ?string
    {
        return $this->providerCode;
    }

    public static function savingByAccount(Account $account, bool $initializedByUser): self
    {
        return new self(
            $account->getOwner(),
            $account,
            $initializedByUser,
            self::FULL_UPDATE,
            null,
            new AccountSource($account->getId()),
            false,
            false
        );
    }

    public static function savingByConfirmationNumber(
        Owner $owner,
        string $providerCode,
        array $confirmationFields
    ): self {
        return new self(
            $owner,
            null,
            self::INITIALIZED_BY_USER,
            self::FULL_UPDATE,
            $confirmationFields,
            new ConfirmationNumber($providerCode, $confirmationFields),
            false,
            false
        );
    }

    public static function savingByTripit(Owner $owner, string $email): self
    {
        return new self(
            $owner,
            null,
            self::INITIALIZED_BY_USER,
            self::FULL_UPDATE,
            null,
            new Tripit(new \DateTimeImmutable(), $email),
            false,
            false
        );
    }

    public static function savingByEmail(
        Owner $owner,
        string $messageId,
        ?ParsedEmailSource $parsedEmailSource,
        bool $initializedByUser = true,
        bool $silent = false,
        bool $updateOnly = false,
        ?\DateTimeInterface $receivedDate = null,
        ?string $providerCode = null
    ): self {
        return new self(
            $owner,
            null,
            $initializedByUser,
            self::PARTIAL_UPDATE,
            null,
            new Email(
                $messageId,
                $parsedEmailSource ? $parsedEmailSource->getEmailRequestId() : null,
                $parsedEmailSource,
                $receivedDate
            ),
            $silent,
            $updateOnly,
            $providerCode
        );
    }
}
