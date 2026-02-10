<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;

/**
 * @NoDI()
 */
class OfferQuery
{
    public const SOURCE_PARAM_NAME = 'cid';
    // Spend Analytics page
    public const SOURCE_WEB_SPEND_ANALYTICS = 'spend-analysis&mid=web&source=aw_app'; // 'webSpendAnalytics';
    public const SOURCE_MOBILE_SPEND_ANALYTICS = 'spend-analysis&mid=mobile&source=aw_app'; // 'mobileSpendAnalytics';
    // Merchant Lookup Tool page
    public const SOURCE_WEB_MCC = 'merchant-lookup&mid=web&source=aw_app'; // 'webAwMcc';
    public const SOURCE_MOBILE_MCC = 'merchant-lookup&mid=mobile&source=aw_app'; // 'mobileAwMcc';
    // History page
    public const SOURCE_WEB_HISTORY = 'transaction-history&mid=web'; // 'webHistory';
    public const SOURCE_MOBILE_HISTORY = 'transaction-history&mid=mobile'; // 'mobileHistory';
    // Transaction Analyzer page
    public const SOURCE_WEB_TRANSACTION_ANALYZER = 'transaction-analyzer&mid=web';
    // Mobile speific pages
    public const SOURCE_MOBILE_BARCODE_RECOMMENDATION = 'barcode-recommendation&mid=mobile';

    public const AVAILABLE_SOURCES = [
        self::SOURCE_MOBILE_HISTORY, self::SOURCE_WEB_HISTORY,
        self::SOURCE_MOBILE_MCC, self::SOURCE_WEB_MCC,
        self::SOURCE_MOBILE_SPEND_ANALYTICS, self::SOURCE_WEB_SPEND_ANALYTICS,
    ];

    /** @var int[]|null */
    private $offerCards; // CreditCardIDs to offer
    /** @var Merchant */
    private $merchant;
    /** @var \DateTime */
    private $date;
    /** @var float */
    private $amount;
    /** @var float */
    private $miles;
    /** @var string */
    private $description;
    /** @var string - some url params, like transaction-history&mid=web */
    private $source;
    /** @var Usr */
    private $user;
    private ?float $pointValue;
    private ?float $multiplier;

    /**
     * @param string $source - some url params, like transaction-history&mid=web
     */
    public function __construct(
        Merchant $merchant,
        \DateTime $date,
        float $amount,
        ?float $miles,
        string $description,
        string $source,
        ?array $offerCards = null,
        ?Usr $user = null,
        ?float $pointValue = null,
        ?float $multiplier = null
    ) {
        $this->merchant = $merchant;
        $this->date = $date;
        $this->amount = $amount;
        $this->miles = $miles;
        $this->description = $description;
        $this->source = $source;

        if (!empty($offerCards)) {
            array_map(function ($id) {
                return (int) $id;
            }, $offerCards);
        }
        $this->offerCards = $offerCards;
        $this->user = $user;
        $this->pointValue = $pointValue;
        $this->multiplier = $multiplier;
    }

    public static function createFromHistoryRow(
        AccountHistory $row,
        string $source,
        ?array $offerCards = null,
        MileValueCards $mileValueCards
    ) {
        $tx = Transaction::createFromAccountHistory($row, fn () => null, null, $mileValueCards);

        return new self(
            $row->getMerchant(),
            $row->getPostingdate(),
            $row->getAmount(),
            $tx->miles,
            $row->getDescription(),
            $source,
            $offerCards,
            $row->getAccount()->getUser(),
            $tx->pointsValue,
            $tx->multiplier,
        );
    }

    /**
     * @return int[]|null
     */
    public function getOfferCards(): ?array
    {
        return $this->offerCards;
    }

    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getMiles(): ?float
    {
        return $this->miles;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function getPointValue(): ?float
    {
        return $this->pointValue;
    }

    public function getMultiplier(): ?float
    {
        return $this->multiplier;
    }
}
