<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use Doctrine\DBAL\Connection;

class CardLink
{
    private array $creditCards;

    public function __construct(Connection $connection)
    {
        $this->creditCards = $connection->executeQuery("select CreditCardID, Name from CreditCard")->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function format(int $cardId): string
    {
        return "<a href=\"/manager/edit.php?Schema=CreditCard&ID={$cardId}\" target='_blank'>{$cardId} - {$this->creditCards[$cardId]}</a>";
    }
}
