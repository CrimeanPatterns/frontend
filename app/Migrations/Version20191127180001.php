<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191127180001 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $logFound = [];
        $logNotFound = [];

        $qsCreditCards = $this->connection->fetchAll('SELECT QsCreditCardID, CardName, IsManual FROM QsCreditCard');
        $qsCreditCardsHistory = $this->connection->fetchAll('SELECT QsCreditCardID, CardName FROM QsCreditCardHistory');

        $creditCards = $this->connection->executeQuery('SELECT CreditCardID, Name, CardFullName FROM CreditCard')->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($creditCards as $creditCard) {
            $found = $this->findQsCard($creditCard['Name'], $qsCreditCards, $qsCreditCardsHistory, false)
                ?? $this->findQsCard($creditCard['Name'], $qsCreditCards, $qsCreditCardsHistory, true);

            if (empty($found)) {
                $cleanName = str_replace(['®', '℠', '™', '(R)', '(SM)', '(TM)'], '', $creditCard['Name']);
                $found = $this->findQsCard($cleanName, $qsCreditCards, $qsCreditCardsHistory, false)
                    ?? $this->findQsCard($cleanName, $qsCreditCards, $qsCreditCardsHistory, true);
            }

            $log = 'CreditCardID = ' . $creditCard['CreditCardID'] . ', Name = "' . $creditCard['Name'] . '"';

            if (!empty($found)) {
                $logFound[] = $log . ' assigned to QS card "' . $found['CardName'] . '", QsCreditCardID = ' . $found['QsCreditCardID'];
                $this->connection->update(
                    'CreditCard',
                    ['QsCreditCardID' => $found['QsCreditCardID']],
                    ['CreditCardID' => $creditCard['CreditCardID']]
                );
            } else {
                $logNotFound[] = $log;
            }
        }

        $this->write('SUCCESS: (' . \count($logFound) . ')');
        $this->write(implode(PHP_EOL . ' ', $logFound));
        $this->write('');
        $this->write('FAILURE: (' . \count($logNotFound) . ')');
        $this->write(implode(PHP_EOL . ' ', $logNotFound));
    }

    public function down(Schema $schema): void
    {
    }

    private function findQsCard(string $cardName, array $cards = [], array $cardsHistory = [], bool $isManual): ?array
    {
        foreach ($cards as $card) {
            if ($cardName === $card['CardName'] && (($isManual && 1 === (int) $card['IsManual']) || (!$isManual && 0 === (int) $card['IsManual']))) {
                return $card;
            }
        }

        foreach ($cardsHistory as $card) {
            if ($cardName === $card['CardName']) {
                return $card;
            }
        }

        $cardName = str_ireplace('credit card', 'card', $cardName);

        foreach ($cards as $card) {
            if ($cardName === trim(str_ireplace('credit card', 'card', $card['CardName'])) && (($isManual && 1 === (int) $card['IsManual']) || (!$isManual && 0 === (int) $card['IsManual']))) {
                return $card;
            }
        }

        foreach ($cardsHistory as $card) {
            if ($cardName === trim(str_ireplace('credit card', 'card', $card['CardName']))) {
                return $card;
            }
        }

        foreach ($cards as $card) {
            if ($cardName === trim(str_ireplace('card', '', $card['CardName'])) && (($isManual && 1 === (int) $card['IsManual']) || (!$isManual && 0 === (int) $card['IsManual']))) {
                return $card;
            }
        }

        foreach ($cardsHistory as $card) {
            if ($cardName === trim(str_ireplace('card', '', $card['CardName']))) {
                return $card;
            }
        }

        return null;
    }
}
