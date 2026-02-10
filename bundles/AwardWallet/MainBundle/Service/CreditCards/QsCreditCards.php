<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class QsCreditCards
{
    private \CurlDriver $curlDriver;
    private Connection $connection;

    public function __construct(\CurlDriver $curlDriver, Connection $connection)
    {
        $this->curlDriver = $curlDriver;
        $this->connection = $connection;
    }

    public function getCreditCard(array $feeds): ?array
    {
        $cards = [];
        $http = new \HttpBrowser('none', $this->curlDriver);

        foreach ($feeds as $key => $feed) {
            $http->GetURL($feed, [], 10);

            if (200 === $http->Response['code']) {
                $data = html_entity_decode(trim($http->Response['original_body']));
                $data = json_decode($data, true);

                if (!empty($data)) {
                    if (isset($data[0]['CardName'])) {
                        $cards[$key] = $data;

                        continue;
                    }

                    if (isset($data['ResultSet']['Listings']['Listing'])) {
                        $cards[$key] = $data['ResultSet']['Listings']['Listing'];

                        continue;
                    }

                    if (isset($data['ResultSet']['Listing'])) {
                        $cards[$key] = $data['ResultSet']['Listing'];

                        continue;
                    }

                    throw new \Exception("Unsupported data\r\n{$feed}\r\n" . $http->Response['original_body']);
                }
            }
        }

        return $cards;
    }

    public function update($feedCards): array
    {
        $log = [
            'insert' => 0,
            'update' => 0,
            'rename' => [],
        ];

        $exist = $this->connection->fetchAllAssociative('
            SELECT QsCreditCardID, QsCardInternalKey, CardName, BonusMilesFull, Slug, ForeignTransactionFee
            FROM QsCreditCard
        ');

        $existedCards = [];

        foreach ($exist as $card) {
            $existedCards[$card['QsCardInternalKey']] = $card;
        }

        $awCards = $this->connection->fetchAllKeyValue('SELECT CreditCardID, Name FROM CreditCard');
        $this->connection->executeQuery('UPDATE QsCreditCard SET IsHidden = 1');
        $updatedCardId = [];

        foreach ($feedCards as $feedKey => $cards) {
            foreach ($cards as $card) {
                $updatedCardId[] = $card['CreditCardID'];
                $card['CardName'] = strip_tags($card['CardName']);

                if (empty($card['CardName'])) {
                    echo "\r\nEMPTY CardName:";
                    print_r($card);

                    continue;
                }

                if (array_key_exists($card['CreditCardID'], $existedCards)) {
                    $needUpdate = false;

                    if ($existedCards[$card['CreditCardID']]['CardName'] !== $card['CardName']
                        || (isset($card['BonusMilesFull']) && $existedCards[$card['CreditCardID']]['BonusMilesFull'] !== $card['BonusMilesFull'])
                    ) {
                        $this->connection->executeQuery('
                            INSERT INTO `QsCreditCardHistory` (`QsCreditCardID`, `CardName`, `BonusMilesFull`, `CreationDate`)
                                VALUES (:qsCreditCardId, :cardName, :bonusMilesFull, NOW())
                        ',
                            [
                                'qsCreditCardId' => $existedCards[$card['CreditCardID']]['QsCreditCardID'],
                                'cardName' => $existedCards[$card['CreditCardID']]['CardName'],
                                'bonusMilesFull' => $existedCards[$card['CreditCardID']]['BonusMilesFull'],
                            ],
                            [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]
                        );

                        if ($existedCards[$card['CreditCardID']]['CardName'] !== $card['CardName']) {
                            $log['rename'][] = 'QsCreditCardID = ' . $card['CreditCardID'] . ' |CardName| was renamed from "' . $existedCards[$card['CreditCardID']]['CardName'] . '" to "' . $card['CardName'] . '"';
                        }

                        if (isset($card['BonusMilesFull']) && $existedCards[$card['CreditCardID']]['BonusMilesFull'] !== $card['BonusMilesFull']) {
                            $log['rename'][] = 'QsCreditCardID = ' . $card['CreditCardID'] . ' |BonusMilesFull| was changed from "' . $existedCards[$card['CreditCardID']]['BonusMilesFull'] . '" to "' . $card['BonusMilesFull'] . '"';
                        }

                        $needUpdate = true;
                    }

                    if ((isset($card['Slug']) && $card['Slug'] !== $existedCards[$card['CreditCardID']]['Slug'])
                        || (isset($card['ForeignTransactionFee']) && $card['ForeignTransactionFee'] !== $existedCards[$card['CreditCardID']]['ForeignTransactionFee'])
                    ) {
                        $needUpdate = true;
                    }

                    if ($needUpdate) {
                        $this->connection->executeQuery('
                            UPDATE `QsCreditCard`
                            SET CardName = :cardName, BonusMilesFull = :bonusMilesFull,  Slug = :slug, ForeignTransactionFee = :ForeignTransactionFee
                            WHERE
                                    QsCreditCardID = :qsCreditCardId',
                            [
                                'cardName' => $card['CardName'],
                                'bonusMilesFull' => $card['BonusMilesFull'],
                                'slug' => (empty($card['Slug']) ? null : $card['Slug']),
                                'qsCreditCardId' => $existedCards[$card['CreditCardID']]['QsCreditCardID'],
                                'ForeignTransactionFee' => $card['ForeignTransactionFee'] ?? null,
                            ],
                            [
                                ParameterType::STRING,
                                ParameterType::STRING,
                                ParameterType::STRING,
                                ParameterType::INTEGER,
                                ParameterType::STRING,
                            ]
                        );

                        ++$log['update'];
                    }

                    continue;
                }

                $awCardId = array_search($card['CardName'], $awCards);

                $this->connection->executeQuery('
                    INSERT INTO `QsCreditCard` (`QsCardInternalKey`, `CardName`, `BonusMilesFull`, `Slug`, `AwCreditCardID`, `IsManual`, `ForeignTransactionFee`)
                        VALUES (:qsCardId, :cardName, :bonusMilesFull, :slug, :awCardId, :isManual, :ForeignTransactionFee)
                    ',
                    [
                        'qsCardId' => $card['CreditCardID'],
                        'cardName' => $card['CardName'],
                        'bonusMilesFull' => $card['BonusMilesFull'],
                        'slug' => (empty($card['Slug']) ? null : $card['Slug']),
                        'awCardId' => (empty($awCardId) ? null : $awCardId),
                        'isManual' => (array_key_exists('IsManual', $card) ? $card['IsManual'] : 0),
                        'ForeignTransactionFee' => $card['ForeignTransactionFee'] ?? null,
                    ],
                    [
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                        ParameterType::STRING,
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::INTEGER,
                        ParameterType::STRING,
                    ]);

                ++$log['insert'];
            }
        }

        $updatedCardId = array_unique($updatedCardId);
        $this->connection->executeQuery('
            UPDATE QsCreditCard
            SET IsHidden = 0, UpdateDate = NOW()
            WHERE QsCardInternalKey IN (' . implode(',', $updatedCardId) . ')
        ');

        return $log;
    }
}
