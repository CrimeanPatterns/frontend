<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

class ShoppingCategoryMatcher
{
    public const PATTERNS_MEMCACHED_KEY = "credit_cards_shopping_category_patterns";
    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $connection;
    /** @var array */
    private $cache = [];
    /** @var Statement */
    private $updateQuery;
    /** @var \Memcached */
    private $memcached;
    private ShoppingCategoryGroupFinder $categoryGroupFinder;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \Memcached $memcached,
        ShoppingCategoryGroupFinder $categoryGroupFinder
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->memcached = $memcached;
        $this->categoryGroupFinder = $categoryGroupFinder;
    }

    public function identify(?string $name, int $providerId): ?int
    {
        if (empty($name)) {
            return null;
        }

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // проверить в таблице $table
        $item = $this->connection->executeQuery(
            "SELECT * FROM ShoppingCategory WHERE Name = :Name",
            [":Name" => $name]
        )->fetch();

        if (!empty($item)) {
            $this->cache[$name] = intval($item["ShoppingCategoryID"]);

            return $this->cache[$name];
        }

        // добавить в таблицу ShoppingCategory
        if ($this->updateQuery === null) {
            $this->updateQuery = $this->connection->prepare("INSERT INTO ShoppingCategory (Name, ProviderID, MatchingOrder, ShoppingCategoryGroupID) VALUES (:Name, :ProviderID, 0, :ShoppingCategoryGroupID)");
        }

        $this->updateQuery->executeStatement([
            ":Name" => $name,
            ':ProviderID' => $providerId,
            ':ShoppingCategoryGroupID' => $this->categoryGroupFinder->findMatchingGroupId($name),
        ]);
        $this->logger->info("New ShoppingCategory was created: " . $name);

        $this->cache[$name] = intval($this->connection->lastInsertId());

        return $this->cache[$name];
    }
}
