<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150519142652 extends AbstractMigration
{
    private static $providerGroups = [
        'continental' => 'continental',
        'delta' => 'delta',
        'goldpassport' => 'hyatt',
        'jetblue' => 'jetblue',
        'triprewards' => 'wyndham',
        'marriott' => 'Marriott',
        'alaskaair' => 'alaska',
        'hhonors' => 'hilton',
        'spg' => 'starwood',
        'mileageplus' => 'United',
        'amtrak' => 'amtrak',
        'british' => 'BritishAirways',
        'qantas' => 'Qantas',
        'choice' => 'choice',
        'klm' => 'klm',
        'lufthansa' => 'lufthansa',
        'avis' => 'Avis',
        'skywards' => 'emirates',
        'thankyou' => 'thankyou',
        'airmiles' => 'Airmiles',
        'windham' => 'wyndham',
        'bankofamerica' => 'bofa',
        'capitalone' => 'CapitalOne',
        'amex' => 'amex',
        'airberlin' => 'airberlin',
        'dinersclub' => 'dinersclub',
        'capitalcards' => 'CapitalOne',
        'airmilesca' => 'Airmiles',
        'searsclub' => 'sears',
        'czech' => 'Czech',
        'deltacorp' => 'delta',
        'rewardone' => 'continental',
        'royalcaribbean' => 'RoyalCaribbean',
        'alaskabiz' => 'alaska',
        'onbusiness' => 'BritishAirways',
        'swisscorporate' => 'lufthansa',
        'searsshop' => 'sears',
        'klbbluebiz' => 'klm',
        'hsbc' => 'hsbc',
        'perksplus' => 'United',
        'longos' => 'thankyou',
        'nordic' => 'choice',
        'bermuda' => 'hsbc',
        'czechcorporate' => 'Czech',
        'outback' => 'outback',
        'airberlinbusiness' => 'airberlin',
        'citybank' => 'thankyou',
        'companyblue' => 'jetblue',
        'amtrakbiz' => 'amtrak',
        'hiltongvc' => 'hilton',
        'orbitz' => 'orbitz',
        'outbackb' => 'outback',
        'maritz' => 'bofa',
        'emirateskids' => 'emirates',
        'hyattvc' => 'hyatt',
        'amc' => 'amc',
        'starwoodbusiness' => 'starwood',
        'caribbeanvisa' => 'RoyalCaribbean',
        'chasesears' => 'chase',
        'amcstubs' => 'amc',
        'amexgc' => 'amex',
        'whiteboard' => 'delta',
        'stationcasino' => 'Casinos',
        'citirewards' => 'thankyou',
        'marriottgift' => 'Marriott',
        'amexbb' => 'amex',
        'orbitzbus' => 'orbitz',
        'amextravel' => 'amex',
        'avisfirst' => 'Avis',
        'mbpass' => 'Casinos',
        'avispref' => 'Avis',
        'amexserve' => 'amex',
        'aquire' => 'Qantas',
        'testprovider' => 'testprovidergroup',
        'testprovidergroup' => 'testprovidergroup',
    ];

    public function up(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery("SELECT COUNT(*) as cnt FROM Provider WHERE ProviderGroup = 'testprovidergroup'");

        if ($stmt->fetchColumn(0) < 5) {
            return;
        }

        $this->addSql('UPDATE Provider SET ProviderGroup = NULL');

        foreach (self::$providerGroups as $code => $group) {
            $this->addSql('UPDATE Provider SET ProviderGroup = ? WHERE Code = ?', [$group, $code], [\PDO::PARAM_STR, \PDO::PARAM_STR]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
