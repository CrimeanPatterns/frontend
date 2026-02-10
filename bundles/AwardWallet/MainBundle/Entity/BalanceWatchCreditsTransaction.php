<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BalanceWatchCreditsTransaction.
 *
 * @ORM\Table(name="BalanceWatchCreditsTransaction")
 * @ORM\Entity
 */
class BalanceWatchCreditsTransaction
{
    public const TYPE_YEAR_CHARGE = 1;
    public const TYPE_PURCHASE = 2;
    public const TYPE_SPEND = 3;
    public const TYPE_GIFT = 4;
    public const TYPE_REFUND = 5;

    public const TRANSACTION_TYPES = [
        self::TYPE_YEAR_CHARGE,
        self::TYPE_PURCHASE,
        self::TYPE_SPEND,
        self::TYPE_GIFT,
        self::TYPE_REFUND,
    ];

    /**
     * @var int
     * @ORM\Column(name="BalanceWatchCreditsTransactionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    private $account;

    /**
     * @var int
     * @ORM\Column(name="TransactionType", type="integer", columnDefinition="ENUM(1, 2, 3, 4, 5)", nullable=false)
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(name="Amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var int
     * @ORM\Column(name="Balance", type="integer", nullable=false)
     */
    private $balance;

    public function __construct(Usr $user, int $type, int $amount)
    {
        $this->user = $user;
        $this->type = $type;
        $this->amount = $amount;

        if (\in_array($type, [self::TYPE_YEAR_CHARGE, self::TYPE_PURCHASE, self::TYPE_GIFT])) {
            $this->setBalance($user->getBalanceWatchCredits() + $amount);
        } elseif (\in_array($type, [self::TYPE_SPEND, self::TYPE_REFUND])) {
            $this->setBalance($user->getBalanceWatchCredits() - $amount);
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUser(?Usr $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    /**
     * Set accountId.
     */
    public function setAccount(?Account $account = null): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setType(int $type): self
    {
        if (!\in_array($type, self::TRANSACTION_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid type');
        }

        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance < 0 ? 0 : $balance;

        return $this;
    }

    public function getBalance(): int
    {
        return $this->balance < 0 ? 0 : $this->balance;
    }
}
