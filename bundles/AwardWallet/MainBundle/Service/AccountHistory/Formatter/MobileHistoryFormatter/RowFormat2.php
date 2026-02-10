<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter;

class RowFormat2
{
    use Blocks\KindedTrait;

    public ?string $date;
    public ?string $style;
    public ?string $merchant;
    public string $title;
    public ?string $category;
    public ?string $value;
    public ?string $creditCard;
    public ?string $totalTransactions;
    public array $earned;
    public array $cashEquivalent;
    public array $extraData;
    public ?string $uuid;

    public function __construct()
    {
        $this->kind = Blocks\Kind::KIND_ROW;
    }
}
