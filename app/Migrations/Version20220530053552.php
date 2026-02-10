<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use AwardWallet\MainBundle\Service\HotelPointValue\PatternLoader;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220530053552 extends AbstractMigration
{

    private array $uniqueNames = [];
    private array $uniquePatterns = [];
    private array $merchantToPatternMap = [];
    private array $migratedGroups = [];

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement("delete from MerchantPattern");
        $this->migratePattens();
        $this->connection->executeStatement("delete from MerchantPatternGroup");
        $this->migrateGroups();
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement("delete from MerchantPattern");
        $this->connection->executeStatement("delete from MerchantPatternGroup");
    }

    private function migratePattern(array $oldPattern) : void
    {
        if ($this->isIgnored($oldPattern['Name'])) {
            return;
        }

        $oldPattern = $this->correctOldRecord($oldPattern);
        $newName = $oldPattern["DisplayName"] ?? MerchantDisplayNameGenerator::create($oldPattern['Name']);

        if (!$this->isUniqueName($newName, $oldPattern)) {
            throw new \Exception("Non unique name $newName, add exception to isIgnored");
        }

        $newPattern = $oldPattern["Patterns"] ?? $oldPattern["Name"];
        if (!$this->isUniquePattern($newPattern, $oldPattern)) {
            throw new \Exception("Non unique pattern, add exception to isIgnored");
        }

        $newPattern = [
            "Name" => $newName,
            "Patterns" => $oldPattern["Patterns"] ?? $oldPattern["Name"],
            "ClickUrl" => $oldPattern["ClickURL"],
            "DetectPriority" => ($oldPattern["DetectPriority"] ?? 0) + 100,
        ];

        $this->connection->insert("MerchantPattern", $newPattern);
        $this->merchantToPatternMap[(int) $oldPattern["MerchantID"]] = (int) $this->connection->lastInsertId();
    }

    private function isIgnored(string $name) : bool
    {
        return
            // doubles by DisplayName
            ($name === 'BUFFALO WILD WINGS PARSSI')
            || ($name === 'CHARLEYS PHILLY STEAKS')
            || ($name === 'DUANE READE  NEW YORK NY')
            || ($name === 'DUNKIN  Q35')
            || ($name === 'HANNA ANDERSSON M O DIRECT')
            || ($name === 'HOLIDAY INN RESTAURANT')
            || ($name === 'HY VEE FOODS')
            || ($name === 'HY VEE GAS ELKHORN')
            || ($name === 'LUL TICKET MACHINEHEATHROW T')
            || ($name === 'MARIANO S')
            || ($name === 'PLAID PANTRY 15')
            || ($name === 'POPEYES LOUISIANA KITCHEN')
            || ($name === 'POTBELLY SANDWICH WORKS')
            || ($name === 'RAISING CANE S CHICKEN FINGERS')
            || ($name === 'SHELL OIL')
            || ($name === 'THE FRESH MARKET I')
            || ($name === 'THE NORTH FACE COM')
            || ($name === 'TIM HORTONS')
            || ($name === 'UNTUCKIT NY3  MADISON')
            || ($name === 'WAWA   FREDERICKSBUR VA')
            || ($name === 'ZOE S KITCHEN')

            // doubles by Patterns
            || ($name === 'HANNA ANDERSSON M O DIRECT')
            || ($name === 'HOLIDAY INN RESTAURANT')
            || ($name === 'HYATT')
            || ($name === 'HY VEE GAS ELKHORN')
            || ($name === 'LUL TICKET MACHINEHEATHROW T')
            || ($name === 'PLAID PANTRY 15')
            || ($name === 'PRESSED JUICERY 52')
            || ($name === 'RALPH S GROCERY')
            || ($name === 'SHAKE SHACK 4GS04')
            || ($name === 'UNTUCKIT NY3  MADISON')
            || ($name === 'HILTON HOTELS F B')
            || ($name === 'IKEA RESTAURANT')
            || ($name === 'INTERCONTINENTAL RESTAURANT BAR')
            || ($name === 'MARIANOS')

            // double by DisplayName, loaded by MerchantGroupMerchant
            || ($name === 'DELTA')
            || ($name === 'WWW COSTCO COM')
            || ($name === 'FRONTIER AIRLINES')
            || ($name === 'SUPER 8')
        ;
    }

    private function isUniqueName(string $name, array $merchant) : bool
    {
        $uniqueKey = strtoupper($name);
        $existing = $this->uniqueNames[$uniqueKey] ?? null;

        if ($existing !== null) {
            $this->write("Merchant with Name: {$name} already exists:");
            $this->write(json_encode($existing, JSON_PRETTY_PRINT));
            $this->write("Trying to insert:");
            $this->write(json_encode($merchant, JSON_PRETTY_PRINT));

            return false;
        }

        $this->uniqueNames[$uniqueKey] = $merchant;

        return true;
    }

    private function isUniquePattern(string $convertedPattern, array $merchant) : bool
    {
        $uniqueKey = strtoupper($convertedPattern);
        $existing = $this->uniquePatterns[$uniqueKey] ?? null;

        if ($existing !== null) {
            $this->write("Merchant with Patterns: {$convertedPattern} already exists:");
            $this->write(json_encode($existing, JSON_PRETTY_PRINT));
            $this->write("Trying to insert:");
            $this->write(json_encode($merchant, JSON_PRETTY_PRINT));

            return false;
        }

        $this->uniquePatterns[$uniqueKey] = $merchant;

        return true;
    }

    private function correctOldRecord(array $oldPattern) : array
    {
        if ($oldPattern["Name"] === 'H M SHOP') {
            $oldPattern['DisplayName'] = 'H&M Shop';
        }

        if ($oldPattern["Name"] === 'HILTON HOTELS') {
            $oldPattern['DisplayName'] = 'Hilton';
        }

        return $oldPattern;
    }

    private function migratePattens() : void
    {
        $oldPatterns = $this->connection->fetchAllAssociative("
            select
                MerchantID,
                Name,
                DisplayName,
                Patterns,
                DetectPriority,
                ClickURL,
                IsCustomDisplayName,
                Transactions
            from
                Merchant 
            where
                Patterns is not null
                or IsCustomDisplayName = 1
                or MerchantID in (select MerchantID from MerchantGroupMerchant)
        ");

        foreach ($oldPatterns as $oldPattern) {
            $this->migratePattern($oldPattern);
        }
    }

    private function migrateGroups() : void
    {
        $oldGroups = $this->connection->fetchAllAssociative("
            select
                *
            from
                MerchantGroupMerchant 
        ");

        foreach ($oldGroups as $oldGroup) {
            $patternId = $this->getPatternIdForMerchant((int) $oldGroup["MerchantID"]);

            $uniqueKey = "{$patternId}_{$oldGroup["MerchantGroupID"]}";

            if (isset($this->migratedGroups[$uniqueKey])) {
                continue;
            }

            $this->connection->insert("MerchantPatternGroup", [
                "MerchantPatternID" => $patternId,
                "MerchantGroupID" => $oldGroup["MerchantGroupID"],
            ]);
            $this->migratedGroups[$uniqueKey] = true;
        }
    }

    private function getPatternIdForMerchant(int $merchantId) : int
    {
        $map = [
            34006990 => 925619, // lufthansa
            2267 => 245697, // super 8
            2917979 => 106752, // intercontinental
            3866943 => 19416, // HOLIDAY INN
            4086631 => 3877121, // DELTA
            34005770 => 793184, // WWW COSTCO COM
            34006421 => 673024, // FRONTIER AIRLINES
        ];

        $merchantId =  $map[$merchantId] ?? $merchantId;
        $patternId = $this->merchantToPatternMap[$merchantId] ?? null;

        if ($patternId === null) {
            throw new \Exception("Merchant $merchantId was not migrated to MerchantPattern");
        }

        return $patternId;
    }
}
