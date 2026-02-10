<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoader;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ShoppingCategoryGroupFinder
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function updateAll(): void
    {
        $patternsByGroup = $this->loadPatternsByGroup();
        $changed = 0;

        stmtAssoc(
            $this->connection->executeQuery(
                "select 
                        SC.ShoppingCategoryID, 
                        SC.ShoppingCategoryGroupID,
                        SC.Name,
                        SCG.Name as GroupName
                    from 
                        ShoppingCategory SC
                        left outer join ShoppingCategoryGroup SCG on SC.ShoppingCategoryGroupID = SCG.ShoppingCategoryGroupID
                    where 
                        (
                            SC.LinkedToGroupBy <> " . ShoppingCategory::LINKED_TO_GROUP_BY_MANUALLY . "
                            or
                            SC.LinkedToGroupBy is null
                        )"
            )
        )
            ->apply(function (array $category) use ($patternsByGroup, &$changed) {
                $group = $this->findMatchingGroup($category['Name'], $patternsByGroup);
                $groupId = $group ? $group['ShoppingCategoryGroupID'] : null;

                if ($groupId !== $category['ShoppingCategoryGroupID']) {
                    $this->logger->info("{$category['Name']}: {$category['GroupName']} -> " . ($group ? $group['Name'] : "None"));
                    $changed++;
                    $this->connection->executeStatement(
                        "update ShoppingCategory 
                            set 
                                ShoppingCategoryGroupID = :groupId,
                                LinkedToGroupBy = " . ShoppingCategory::LINKED_TO_GROUP_BY_PATTERNS . "
                            where ShoppingCategoryID = :categoryId",
                        [
                            "groupId" => $groupId,
                            'categoryId' => $category['ShoppingCategoryID'],
                        ]
                    );
                }
            });

        $this->logger->info("changed $changed categories");
    }

    public function findMatchingGroupId(string $categoryName): ?int
    {
        $patternsByGroup = $this->loadPatternsByGroup();
        $group = $this->findMatchingGroup($categoryName, $patternsByGroup);

        if ($group) {
            $this->logger->info("{$categoryName} matched to group {$group['Name']}");

            return $group['ShoppingCategoryGroupID'];
        }

        $this->logger->info("{$categoryName} did not match any group");

        return null;
    }

    private function findMatchingGroup(string $categoryName, array $patternsByGroup): ?array
    {
        foreach ($patternsByGroup as $group) {
            if (PatternLoader::matchLoaded($categoryName, $group['PreparedPatterns'])) {
                return $group;
            }
        }

        return null;
    }

    private function loadPatternsByGroup(): array
    {
        return
            stmtAssoc(
                $this->connection->executeQuery(
                    "select 
                            ShoppingCategoryGroupID, 
                            Name, 
                            Patterns 
                        from 
                            ShoppingCategoryGroup
                        where
                            Patterns is not null
                        order by
                            Priority desc"
                )
            )
            ->map(function (array $row) {
                $row['PreparedPatterns'] = PatternLoader::load($row['Patterns']);

                return $row;
            })
            ->toArray()
        ;
    }
}
