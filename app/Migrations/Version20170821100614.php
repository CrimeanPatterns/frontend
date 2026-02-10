<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\ArrayUtils;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpCompiler;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170821100614 extends AbstractMigration
{
    /**
     * @var string
     */
    protected $stopwords;

    public function up(Schema $schema): void
    {
        $this->stopwords = (new RegexpCompiler())->compileStopwords();
        $stmt = $this->connection->executeQuery(' select ProviderID, KeyWords from Provider');
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        while ($provider = $stmt->fetch()) {
            if (StringUtils::isEmpty($provider['KeyWords'])) {
                continue;
            }

            $oldKeywords = ArrayUtils::sort(array_map('trim', explode(',', $provider['KeyWords'])));
            $newKeywords = ArrayUtils::sort($this->filterKeywords($oldKeywords));

            if ($oldKeywords !== $newKeywords) {
                $this->write("Removed keywords for provider {$provider['ProviderID']} " . json_encode($oldKeywords) . " -- " . json_encode(array_values(array_diff($oldKeywords, $newKeywords))));
                $this->connection->executeUpdate(
                    'update Provider set KeyWords = ? where ProviderID = ? and KeyWords = ?',
                    [implode(', ', $newKeywords), $provider['ProviderID'], $provider['KeyWords']],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    protected function filterKeywords(array $keywords): array
    {
        $newKeywords = [];

        foreach ($keywords as $keyword) {
            if (!preg_match($this->stopwords, $keyword)) {
                $newKeywords[] = $keyword;
            }
        }

        return $newKeywords;
    }
}
