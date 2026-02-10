<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

class SelectCardsView extends AbstractView
{
    public string $description;

    /**
     * @var CardView[]
     */
    public array $cards;

    public ?AutoDetectCardsView $autoDetectCards;

    /**
     * @param CardView[] $cards
     */
    public function __construct(string $description, array $cards, ?AutoDetectCardsView $autoDetectCards)
    {
        $this->description = $description;
        $this->cards = $cards;
        $this->autoDetectCards = $autoDetectCards;
    }
}
