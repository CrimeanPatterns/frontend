<?php

namespace AwardWallet\Tests\Unit\Email;

use AwardWallet\MainBundle\Email\DiscoveredAccountMigrator;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class MigrateAccountTest extends BaseUserTest
{
    public const DL_ID = 7;
    public const DL_NUMBER_ID = 10;
    public const DL_NUMBER_CODE = 'Number';
    public const DL_LEVEL_ID = 11;
    public const DL_LEVEL_CODE = 'Level';
    public const DL_MILES_ID = 15;
    public const DL_MILES_CODE = 'MillionMiles';

    public const UA_ID = 26;
    public const UA_NUMBER_ID = 17;
    public const UA_NUMBER_CODE = 'Number';
    public const UA_LEVEL_ID = 1602;
    public const UA_LEVEL_CODE = 'MemberStatus';
    public const UA_MILES_ID = 20;
    public const UA_MILES_CODE = 'EliteMiles';

    /** @var DiscoveredAccountMigrator */
    private $migrator;

    private $famId;

    public function _before()
    {
        parent::_before();
        $this->migrator = new DiscoveredAccountMigrator($this->em, new NullLogger());
        $this->famId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Doe');
    }

    public function testMigrateBalanceAndProperties()
    {
        $targetDelta = $this->createAwAcc(self::DL_ID, 'ABC123', ACCOUNT_ENABLED, null, null, [self::DL_LEVEL_CODE => 'Silver']);
        /* source */ $this->createAwAcc(self::DL_ID, 'ABC123', ACCOUNT_PENDING, $this->famId, 444, [self::DL_MILES_CODE => 10]);
        $this->createAwAcc(self::DL_ID, '', ACCOUNT_PENDING, null, 333, [self::DL_MILES_CODE => 2]);
        $this->createAwAcc(self::DL_ID, 'XXXXX', ACCOUNT_ENABLED, null, 222, [self::DL_MILES_CODE => 3]);

        $targetUnited = $this->createAwAcc(self::UA_ID, 'unitedlogin', ACCOUNT_ENABLED, null, 555, [self::UA_NUMBER_CODE => 'ABC123']);
        /* source */ $this->createAwAcc(self::UA_ID, '', ACCOUNT_PENDING, null, 666, [self::UA_NUMBER_CODE => 'ABC123', self::UA_MILES_CODE => 20]);
        $this->createAwAcc(self::UA_ID, 'YYYYY', ACCOUNT_PENDING, $this->famId, 333, [self::UA_MILES_CODE => 11]);
        $this->createAwAcc(self::UA_ID, 'XXXXX', ACCOUNT_PENDING, $this->famId, 222, [self::UA_MILES_CODE => 12]);

        $this->migrator->migrateDoublesOf($targetDelta);
        $this->migrator->migrateDoublesOf($targetUnited);

        $this->db->seeInDatabase('Account', ['AccountID' => $targetDelta->getId(), 'Balance' => null]);
        $this->assertEquals(2, $this->db->grabCountFromDatabase('Account', ['UserID' => $this->user->getId(), 'ProviderID' => self::DL_ID]));
        $this->db->seeInDatabase('AccountProperty', ['AccountID' => $targetDelta->getId(), 'ProviderPropertyID' => self::DL_LEVEL_ID, 'Val' => 'Silver']);
        $this->db->dontSeeInDatabase('AccountProperty', ['AccountID' => $targetDelta->getId(), 'ProviderPropertyID' => self::DL_MILES_ID, 'Val' => 10]);

        $this->db->seeInDatabase('Account', ['AccountID' => $targetUnited->getId(), 'Balance' => 555]);
        $this->assertEquals(1, $this->db->grabCountFromDatabase('Account', ['UserID' => $this->user->getId(), 'ProviderID' => self::UA_ID]));
        $this->db->seeInDatabase('AccountProperty', ['AccountID' => $targetUnited->getId(), 'ProviderPropertyID' => self::UA_NUMBER_ID, 'Val' => 'ABC123']);
    }

    public function testMigrateMasked()
    {
        $this->markTestSkipped('matching disabled');
        $targetDelta = $this->createAwAcc(self::DL_ID, 'ABC123', ACCOUNT_ENABLED, null, null, []);
        $this->createAwAcc(self::DL_ID, '', ACCOUNT_PENDING, null, 333, []);
        /* source */ $this->createAwAcc(self::DL_ID, '****123', ACCOUNT_PENDING, null, 444, []);

        $targetUnited = $this->createAwAcc(self::UA_ID, 'unitedlogin', ACCOUNT_ENABLED, null, 555, [self::UA_NUMBER_CODE => 'XYZ432']);
        $this->createAwAcc(self::UA_ID, '***XYZ', ACCOUNT_PENDING, null, 333, [self::UA_MILES_CODE => 11]);
        /* source */ $this->createAwAcc(self::UA_ID, '', ACCOUNT_PENDING, null, 666, [self::UA_NUMBER_CODE => 'XYZ***', self::UA_MILES_CODE => 20]);

        $this->migrator->migrateDoublesOf($targetDelta);
        $this->migrator->migrateDoublesOf($targetUnited);

        $this->db->seeInDatabase('Account', ['AccountID' => $targetDelta->getId(), 'Balance' => 444]);
        $this->assertEquals(0, $this->db->grabCountFromDatabase('Account', ['UserID' => $this->user->getId(), 'ProviderID' => self::DL_ID, 'State' => ACCOUNT_PENDING]));

        $this->db->seeInDatabase('Account', ['AccountID' => $targetUnited->getId(), 'Balance' => 555]);
        $this->assertEquals(1, $this->db->grabCountFromDatabase('Account', ['UserID' => $this->user->getId(), 'ProviderID' => self::UA_ID, 'State' => ACCOUNT_PENDING]));
        $this->db->seeInDatabase('AccountProperty', ['AccountID' => $targetUnited->getId(), 'ProviderPropertyID' => self::UA_MILES_ID, 'Val' => 20]);
    }

    private function createAwAcc($providerId, $login, $state, $uaId, $balance, $properties): Account
    {
        $accId = $this->aw->createAwAccount($this->user->getId(), $providerId, $login, '', ['Balance' => $balance, 'State' => $state, 'UserAgentID' => $uaId]);

        foreach ($properties as $code => $value) {
            $this->aw->createAccountProperty($code, $value, ['AccountID' => $accId], $providerId);
        }

        return $this->em->getRepository(Account::class)->find($accId);
    }
}
