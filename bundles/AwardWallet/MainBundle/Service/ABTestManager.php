<?php

namespace AwardWallet\MainBundle\Service;

use Doctrine\DBAL\Connection;

class ABTestManager
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string[] $variants
     */
    public function getNextVariant(string $testId, array $variants): string
    {
        if (empty($variants)) {
            throw new \InvalidArgumentException('Variants list should not be empty');
        }

        $this->connection->beginTransaction();

        try {
            $existingVariants = $this->connection->fetchAllKeyValue(
                'SELECT Variant, ExposureCount FROM ABTest WHERE TestID = ?',
                [$testId]
            );

            $variant = null;

            foreach ($variants as $v) {
                if (!isset($existingVariants[$v])) {
                    $variant = $v;
                    $this->connection->insert('ABTest', ['TestID' => $testId, 'Variant' => $variant]);

                    break;
                }
            }

            if (is_null($variant)) {
                asort($existingVariants);
                $variant = key($existingVariants);
            }

            $this->connection->commit();

            return $variant;
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function incrementExposureCount(string $testId, string $variant): void
    {
        $this->connection->executeStatement(
            'UPDATE ABTest SET ExposureCount = ExposureCount + 1 WHERE TestID = ? AND Variant = ?',
            [$testId, $variant]
        );
    }
}
