<?php

namespace AwardWallet\Tests\Unit\BalanceFormatter;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\Tests\Unit\BaseUserTest;

class AbstractBalanceFormatterTest extends BaseUserTest
{
    /**
     * @var BalanceFormatter
     */
    protected $formatter;

    /**
     * @var int
     */
    protected $providerId;

    public function _before()
    {
        parent::_before();
        $this->formatter = $this->container->get(BalanceFormatter::class);
        $this->providerId = $this->aw->createAwProvider(null, null, ['AllowFloat' => true]);
    }

    public function _after()
    {
        $this->formatter = null;
        parent::_after();
    }

    protected function createAccountWithBalance($balance, $providerId = null): ?Account
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                $providerId ?? $this->providerId,
                'test',
                null,
                [
                    'Balance' => $balance,
                ]
            )
        );
    }

    protected function createSubAccountWithBalance($balance, $providerId = null, $code = null): ?Subaccount
    {
        $account = $this->createAccountWithBalance(null, $providerId);

        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class)->find(
            $this->aw->createAwSubAccount($account->getAccountid(), array_merge([
                'Balance' => $balance,
            ], !is_null($code) ? ['Code' => $code] : []))
        );
    }

    protected function createAccountFieldsWithBalance($balance, $providerId = null)
    {
        $account = $this->createAccountWithBalance($balance, $providerId);

        return $this->createAccountFields($account);
    }

    protected function createAccountFields(Account $account)
    {
        $accountFields = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->accountsToArrayFields([$account]);

        if (sizeof($accountFields) === 0) {
            throw new \InvalidArgumentException('Invalid account');
        }

        $accountFields = $accountFields[0];
        $accountProps = [];

        foreach ($this->em->getRepository(\AwardWallet\MainBundle\Entity\Accountproperty::class)->findBy([
            'accountid' => $account,
            'subaccountid' => null,
        ]) as $property) {
            /** @var Accountproperty $property */
            $accountProps[$property->getProviderpropertyid()->getCode()] = $property->getVal();
        }

        return [$accountFields, $accountProps, $account];
    }

    protected function getCurrency(string $name, ?string $code = null, ?string $sign = null): Currency
    {
        return (new Currency())
            ->setName($name)
            ->setCode($code)
            ->setSign($sign);
    }
}
