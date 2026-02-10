<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\FunctionalUtils;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170630084244 extends AbstractMigration
{
    /**
     * Migration adds Name, ShortName, Site.
     */
    public function up(Schema $schema): void
    {
        $providersStmt = $this->connection->executeQuery("
            select 
                p.ProviderID,
                p.ShortName,
                p.Name,
                p.Site,
                p.KeyWords
            from Provider p
        ");

        while ($provider = $providersStmt->fetch(\PDO::FETCH_ASSOC)) {
            if (StringUtils::isNotEmpty($provider['KeyWords'])) {
                $keyWordsExploded = explode(',', $provider['KeyWords']);
            } else {
                $keyWordsExploded = [];
            }

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

            if (
                (false !== ($parsed = parse_url($provider['Site'])))
                && isset($parsed['host'])
                && !in_array($host = preg_replace('/^www\./ims', '', $parsed['host']), $newKeyWords)
            ) {
                $newKeyWords[] = $host;
            }

            if ($keyWordsExploded !== $newKeyWords) {
                $this->write("New keywords for provider {$provider['ProviderID']} " . json_encode($keyWordsExploded) . " ++ " . json_encode(array_values(array_diff($newKeyWords, $keyWordsExploded))));

                $this->connection->executeUpdate('
                    update Provider
                    set KeyWords = ?
                    where 
                        ProviderID = ? and ' .
                        (StringUtils::isNotEmpty($provider['KeyWords']) ?
                            'KeyWords = ?' :
                            "(KeyWords = '' or Keywords is null or null = ?)"
                        ),
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
        // this down() migration is auto-generated, please modify it to your needs
    }
}
