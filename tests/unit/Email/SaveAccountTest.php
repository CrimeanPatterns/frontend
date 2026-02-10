<?php

namespace AwardWallet\Tests\Unit\Email;

use AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount;
use AwardWallet\Common\API\Email\V2\Loyalty\Property;
use AwardWallet\MainBundle\Email\StatementSaver;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\Tests\Unit\BaseUserTest;
use Clock\ClockInterface;
use Clock\ClockTest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group frontend-unit
 */
class SaveAccountTest extends BaseUserTest
{
    private ?ClockInterface $clock;

    private ?StatementSaver $saver;

    public function _before()
    {
        parent::_before();

        $this->saver = new StatementSaver(
            new NullLogger(),
            $this->em,
            $this->em->getRepository(Account::class),
            $this->em->getRepository(Providerproperty::class),
            new BalanceProcessor($this->em, $this->clock = new ClockTest(), $this->makeEmpty(LoggerInterface::class)),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->container->get(AccountFactory::class),
            $this->container->get(DoctrineRetryHelper::class)
        );
    }

    public function _after()
    {
        $this->clock = null;
        $this->saver = null;

        parent::_after();
    }

    public function testSaveSuccess()
    {
        $acc = $this->createAcc('delta', '', []);
        $acc->setUpdatedate(new \DateTime('-10 days'));
        $data = $this->getLoyaltyData(
            'delta',
            123,
            new \DateTime('-5 days 13:30'),
            new \DateTime('+30 days 00:00'),
            'login',
            null,
            null,
            'number',
            null,
            ['Level' => 'Silver']);
        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->db->seeInDatabase('Account', [
            'AccountID' => $acc->getId(),
            'Login' => 'login',
            'Balance' => 123,
            'UpdateDate' => (new \DateTime('-5 days 13:30'))->format('Y-m-d H:i:s'),
            'SuccessCheckDate' => (new \DateTime('-5 days 13:30'))->format('Y-m-d H:i:s'),
            'CheckedBy' => Account::CHECKED_BY_EMAIL,
            'ExpirationDate' => (new \DateTime('+30 days 00:00'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertEquals(2, $this->db->grabCountFromDatabase('AccountProperty', ['AccountID' => $acc->getId()]));
        $this->db->seeInDatabase('AccountProperty', [
            'AccountID' => $acc->getId(),
            'ProviderPropertyID' => 10,
            'Val' => 'number', ]);
        $this->db->seeInDatabase('AccountProperty', [
            'AccountID' => $acc->getId(),
            'ProviderPropertyID' => 11,
            'Val' => 'Silver', ]);
        $this->assertEquals(1, $this->db->grabCountFromDatabase('AccountBalance', ['AccountID' => $acc->getId()]));
        $this->db->seeInDatabase('AccountBalance', [
            'AccountID' => $acc->getId(),
            'UpdateDate' => $this->clock->current()->getAsDateTime()->format('Y-m-d H:i:s'),
            'Balance' => 123,
        ]);

        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->assertEquals(1, $this->db->grabCountFromDatabase('AccountBalance', ['AccountID' => $acc->getId()]));

        $data->balance = 124;
        $data->balanceDate = (new \DateTime('-4 days 13:30'))->format('Y-m-d H:i:s');
        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->assertEquals(2, $this->db->grabCountFromDatabase('AccountBalance', ['AccountID' => $acc->getId()]));

        $data->balance = 125;
        $data->balanceDate = (new \DateTime('-5 days 13:30'))->format('Y-m-d H:i:s');
        $this->assertFalse($this->saver->save($acc, $data, null));
        $this->assertEquals(2, $this->db->grabCountFromDatabase('AccountBalance', ['AccountID' => $acc->getId()]));

        $this->db->seeInDatabase('Account', [
            'AccountID' => $acc->getId(),
            'Login' => 'login',
            'Balance' => 124,
            'UpdateDate' => (new \DateTime('-4 days 13:30'))->format('Y-m-d H:i:s'),
            'ExpirationDate' => (new \DateTime('+30 days 00:00'))->format('Y-m-d H:i:s'),
        ]);
    }

    public function testSaveRegularProvider()
    {
        $acc = $this->createAcc('aeroflot', 'login', []);
        $acc->setUpdatedate(new \DateTime('-15 days'));
        $acc->setState(ACCOUNT_ENABLED);
        $data = $this->getLoyaltyData(
            'aeroflot',
            123,
            new \DateTime('-5 day'),
            null,
            'login',
            null,
            null,
            'number',
            null,
            []);
        $this->assertFalse($this->saver->save($acc, $data, null));

        $acc->setUpdatedate(new \DateTime('-40 days'));
        $this->assertTrue($this->saver->save($acc, $data, null));

        $acc->setUpdatedate(new \DateTime('-15 days'));
        $acc->setState(ACCOUNT_PENDING);
        $this->assertTrue($this->saver->save($acc, $data, null));
    }

    public function testRewriteNumber()
    {
        $acc = $this->createAcc('delta', 'login', []);
        $numberId = $this->db->grabFromDatabase('ProviderProperty', 'ProviderPropertyID', ['ProviderID' => $acc->getProviderid()->getProviderid(), 'Code' => 'Number']);
        $acc->setState(ACCOUNT_ENABLED);
        $data = $this->getLoyaltyData(
            'delta',
            123,
            null,
            null,
            'ABC',
            'left',
            null,
            '123',
            'right',
            []);
        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->db->seeInDatabase('AccountProperty', [
            'AccountID' => $acc->getId(),
            'Val' => '123****',
            'ProviderPropertyID' => $numberId, ]);
        $this->db->seeInDatabase('Account', [
            'AccountID' => $acc->getId(),
            'Login' => 'login',
        ]);

        $data->number = '123456';
        $data->numberMask = null;
        $data->login2 = 'usa';
        $acc->setState(ACCOUNT_PENDING);
        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->db->seeInDatabase('Account', [
            'AccountID' => $acc->getId(),
            'Login2' => 'usa',
        ]);
        $this->db->seeInDatabase('AccountProperty', [
            'AccountID' => $acc->getId(),
            'Val' => '123456',
            'ProviderPropertyID' => $numberId, ]);
    }

    public function testNullBalance()
    {
        $data = $this->getLoyaltyData(
            'delta',
            null,
            null,
            null,
            'ABC',
            null,
            null,
            null,
            null,
            []);
        $acc = $this->saver->createDiscoveredAccount(
            new Owner($this->user),
            $this->em->getRepository(Provider::class)->findOneByCode('delta'),
            $data, 'email@email.com');
        $this->db->seeInDatabase('Account', ['AccountID' => $acc->getId(), 'Balance' => null, 'SuccessCheckDate' => null]);

        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->db->seeInDatabase('Account', ['AccountID' => $acc->getId(), 'Balance' => null, 'SuccessCheckDate' => null]);
        $this->assertEquals(SAVE_PASSWORD_DATABASE, $this->db->grabFromDatabase('Account', "SavePassword", ["AccountID" => $acc->getId()]));

        $data->balance = 0;
        $this->assertTrue($this->saver->save($acc, $data, null));
        $this->db->seeInDatabase('Account', ['AccountID' => $acc->getId(), 'Balance' => 0]);
        $this->assertTrue(3 > time() - strtotime($this->db->grabFromDatabase('Account', 'SuccessCheckDate', ["AccountID" => $acc->getId()])));
    }

    private function createAcc($code, $login, $fields): Account
    {
        $accId = $this->aw->createAwAccount($this->user->getId(), $code, $login, '', $fields);

        return $this->em->getRepository(Account::class)->find($accId);
    }

    private function getLoyaltyData($code, $balance, ?\DateTime $bDate, ?\DateTime $expDate, $login, $loginMask, $login2, $number, $numberMask, $properties): LoyaltyAccount
    {
        $data = new LoyaltyAccount();
        $data->providerCode = $code;
        $data->balance = $balance;

        if ($bDate) {
            $data->balanceDate = $bDate->format('Y-m-d\TH:i:s');
        }

        if ($expDate) {
            $data->expirationDate = $expDate->format('Y-m-d\TH:i:s');
        }
        $data->login = $login;
        $data->loginMask = $loginMask;
        $data->login2 = $login2;
        $data->number = $number;
        $data->numberMask = $numberMask;

        foreach ($properties as $k => $v) {
            $data->properties[] = new Property($k, null, null, $v);
        }

        return $data;
    }
}
