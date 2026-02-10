<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170216122245 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->processProvider(function (array $providerRow) {
            if (
                isset($providerRow['CheckInReminderOffsetsDecoded']['push'][1])
                && 3 === $providerRow['CheckInReminderOffsetsDecoded']['push'][1]
            ) {
                $providerRow['CheckInReminderOffsetsDecoded']['push'][1] = 4;

                return $providerRow;
            }

            return null;
        });
    }

    public function down(Schema $schema): void
    {
        $this->processProvider(function (array $providerRow) {
            if (
                isset($providerRow['CheckInReminderOffsetsDecoded']['push'][1])
                && 4 === $providerRow['CheckInReminderOffsetsDecoded']['push'][1]
            ) {
                $providerRow['CheckInReminderOffsetsDecoded']['push'][1] = 3;

                return $providerRow;
            }

            return null;
        });
    }

    protected function processProvider(\Closure $processor)
    {
        $stmt = $this->connection->executeQuery('SELECT ProviderID, CheckInReminderOffsets FROM Provider');

        $update = [];

        while ($providerRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $providerRow['CheckInReminderOffsetsDecoded'] = @json_decode($providerRow['CheckInReminderOffsets'], true);

            if (!isset($providerRow['CheckInReminderOffsetsDecoded'])) {
                throw new \RuntimeException('Invalid json for providerId:' . $providerRow['ProviderID']);
            }

            if (is_array($result = $processor($providerRow))) {
                $this->addSql('UPDATE Provider SET CheckInReminderOffsets = ? WHERE ProviderID = ? AND CheckInReminderOffsets = ?',
                    [
                        json_encode($result['CheckInReminderOffsetsDecoded']),
                        $providerRow['ProviderID'],
                        $providerRow['CheckInReminderOffsets'],
                    ],
                    [
                        \PDO::PARAM_STR,
                        \PDO::PARAM_INT,
                        \PDO::PARAM_STR,
                    ]
                );
            }
        }
    }
}
