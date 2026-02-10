<?php
/**
 * Created by PhpStorm.
 * User: ANelyudov
 * Date: 27.03.18
 * Time: 9:39.
 */

namespace AwardWallet\MainBundle\Entity;

class Room
{
    /**
     * @var string
     */
    private $shortDescription;

    /**
     * @var string|null
     */
    private $longDescription;

    /**
     * @var string|null
     */
    private $rate;

    /**
     * @var string|null
     */
    private $rateDescription;

    /**
     * Room constructor.
     */
    public function __construct(
        ?string $shortDescription,
        ?string $longDescription,
        ?string $rate,
        ?string $rateDescription
    ) {
        $this->shortDescription = $shortDescription;
        $this->longDescription = $longDescription;
        $this->rate = $rate;
        $this->rateDescription = $rateDescription;
    }

    public function __toString(): string
    {
        $description = $this->shortDescription ?? $this->longDescription;
        $rate = $this->getRate() ? $this->getRate() : '';

        if ($description && $rate) {
            return "{$description} ($rate)";
        } elseif ($description) {
            return $description;
        } elseif ($rate) {
            return $rate;
        }

        return '';
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function getRateDescription(): ?string
    {
        return $this->rateDescription;
    }
}
