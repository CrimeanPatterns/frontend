<?php

namespace AwardWallet\MainBundle\Security;

class RealUserDetectorResult
{
    /**
     * @var float
     */
    private $registrationDateScore;
    /**
     * @var float
     */
    private $validAccountsScore;
    /**
     * @var float
     */
    private $invalidPasswordsScore;
    /**
     * @var float
     */
    private $mobileAppScore;
    /**
     * @var float
     */
    private $providersScore;
    /**
     * @var float
     */
    private $total;
    /**
     * @var float
     */
    private $mailboxScore;

    /**
     * each param is float in range [0 ... 1], 0 - bot, 1 - real user.
     */
    public function __construct(float $registrationDateScore, float $validAccountsScore, float $invalidPasswordsScore, float $mobileAppScore, float $providersScore, float $mailboxScore)
    {
        $this->registrationDateScore = round($registrationDateScore, 3);
        $this->validAccountsScore = round($validAccountsScore, 3);
        $this->invalidPasswordsScore = round($invalidPasswordsScore, 3);
        $this->mobileAppScore = round($mobileAppScore, 3);
        $this->providersScore = $providersScore;
        $this->mailboxScore = $mailboxScore;

        $this->total = round(
            $this->registrationDateScore * 0.1
            + $this->validAccountsScore * 0.2
            // do not count no invalid checks as a plus, when there are no valid passwords
            + ($this->validAccountsScore === 0 ? 0 : $this->invalidPasswordsScore) * 0.3
            + $this->mobileAppScore * 0.1
            + $this->providersScore * 0.2
            + $this->mailboxScore * 0.1, 5);
    }

    /**
     * @return float - float in range [0 ... 1], 0 - bot, 1 - real user
     */
    public function getRegistrationDateScore(): float
    {
        return $this->registrationDateScore;
    }

    /**
     * @return float - float in range [0 ... 1], 0 - bot, 1 - real user
     */
    public function getValidAccountsScore(): float
    {
        return $this->validAccountsScore;
    }

    /**
     * @return float - float in range [0 ... 1], 0 - bot, 1 - real user
     */
    public function getInvalidPasswordsScore(): float
    {
        return $this->invalidPasswordsScore;
    }

    /**
     * @return float - float in range [0 ... 1], 0 - bot, 1 - real user
     */
    public function getMobileAppScore(): float
    {
        return $this->mobileAppScore;
    }

    /**
     * @return float - float in range [0 ... 1], 0 - bot, 1 - real user
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
