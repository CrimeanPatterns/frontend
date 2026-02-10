<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\FunctionalUtils;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170510203004 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $providersStmt = $this->connection->executeQuery("
            select 
                p.ProviderID,
                p.ShortName,
                p.Name,
                p.KeyWords 
            from Provider p 
            where 
                p.KeyWords is not null and 
                p.KeyWords <> ''
        ");

        while ($provider = $providersStmt->fetch(\PDO::FETCH_ASSOC)) {
            $keyWordsExploded = explode(',', $provider['KeyWords']);
            $keyWords = array_map(function ($word) { return htmlspecialchars_decode(trim($word)); }, $keyWordsExploded);
            $keyWords = array_filter($keyWords, FunctionalUtils::not([StringUtils::class, 'isEmpty']));
            $newKeyWords = $keyWordsExploded;

            foreach (['Name', 'ShortName'] as $nameKey) {
                $nameValue = strtolower(trim(preg_replace(["/[^a-zA-Z0-9\-]/ims", "/\s{2,}/"], [" ", " "], htmlspecialchars_decode($provider[$nameKey]))));

                if (
                    !StringUtils::isEmpty($nameValue)
                    && !in_array($nameValue, $keyWords, true)
                    && !in_array($nameValue, $newKeyWords, true)
                ) {
                    $newKeyWords[] = $nameValue;
                }
            }

            if ($keyWordsExploded !== $newKeyWords) {
                $this->write("New keywords for provider {$provider['ProviderID']} " . json_encode($keyWordsExploded) . " ++ " . json_encode(array_values(array_diff($newKeyWords, $keyWordsExploded))));

                $this->connection->executeUpdate('
                    update Provider
                    set KeyWords = ?
                    where 
                        ProviderID = ? and 
                        KeyWords = ?',
                    [
                        implode(',', $newKeyWords),
                        $provider['ProviderID'],
                        implode(',', $keyWordsExploded),
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
