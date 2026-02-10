<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\SubAccountProcessor;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount as LoyaltySubAccount;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\Tests\Unit\BaseUserTest;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class SubAccountProcessorTest extends BaseUserTest
{
    public const CC_TEST_PROVIDER_ID = Provider::CHASE_ID;
    public const CC_TEST_NAME = 'Test Credit Card %d';

    private ?Account $account;

    private ?SubAccountProcessor $subAccountProcessor;

    public function _before()
    {
        parent::_before();

        $this->account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                self::CC_TEST_PROVIDER_ID,
                'test',
                null,
                [
                    'Balance' => null,
                ]
            )
        );
        $this->subAccountProcessor = $this->container->get(SubAccountProcessor::class);
    }

    public function _after()
    {
        $this->subAccountProcessor = null;
        $this->account = null;

        parent::_after();
    }

    public function testSuccessCreditCardMatching()
    {
        $ccIds = [];
        $subAccountCodes = [];

        foreach ([1, 2, 3] as $itemId) {
            $subAccountCodes[] = $code = sprintf('test_code_%d', $itemId);
            $this->db->haveInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'DisplayName' => $name = sprintf(self::CC_TEST_NAME, $itemId),
                'Code' => $code,
            ]);

            $ccIds[] = $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $this->account->getProviderid()->getId(),
                'Name' => $name,
                'Patterns' => $name,
                'MatchingOrder' => 1,
            ]);
        }

        $this->db->seeNumRecords(3, 'SubAccount', ['AccountID' => $this->account->getId()]);

        foreach ($subAccountCodes as $code) {
            $this->db->seeInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'Code' => $code,
                'CreditCardID' => null,
            ]);
        }

        $response = new CheckAccountResponse();
        $response->setSubaccounts(it($subAccountCodes)->map(function (string $code) {
            return (new LoyaltySubAccount())
                ->setCode($code)
                ->setDisplayname('Test');
        })->toArray());
        $this->subAccountProcessor->process($this->account, $response);

        foreach ($ccIds as $ccId) {
            $this->db->seeInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'CreditCardID' => $ccId,
            ]);
        }
    }

    public function testUpdateDisplayName()
    {
        $ccIds = [];
        $subAccountCodes = [];

        foreach ([1, 2, 3] as $itemId) {
            $subAccountCodes[$itemId] = $code = sprintf('test_code_%d', $itemId);
            $this->db->haveInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'DisplayName' => sprintf("Wrong card name %d", $itemId),
                'Code' => $code,
            ]);

            $ccIds[] = $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $this->account->getProviderid()->getId(),
                'Name' => sprintf(self::CC_TEST_NAME, $itemId),
                'Patterns' => sprintf(self::CC_TEST_NAME, $itemId),
                'MatchingOrder' => 1,
            ]);
        }

        $this->db->seeNumRecords(3, 'SubAccount', ['AccountID' => $this->account->getId()]);

        foreach ($subAccountCodes as $code) {
            $this->db->seeInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'Code' => $code,
                'CreditCardID' => null,
            ]);
        }

        $response = new CheckAccountResponse();
        $response->setState(ACCOUNT_CHECKED);
        $response->setSubaccounts(it($subAccountCodes)->mapIndexed(function (string $code, int $index) {
            return (new LoyaltySubAccount())
                ->setCode($code)
                ->setDisplayname(sprintf(self::CC_TEST_NAME, $index));
        })->toArray());
        $userData = new UserData($this->account->getId());
        $userData->setPriority(1);
        $response->setUserdata($userData);
        $response->setRequestid('test_' . \bin2hex(\random_bytes(16)));

        $converter = $this->container->get(Converter::class);
        $callback = new CheckAccountCallback();
        $callback->setResponse([$response]);
        $converter->processCallbackPackage($callback);

        foreach ($ccIds as $ccId) {
            $this->db->seeInDatabase('SubAccount', [
                'AccountID' => $this->account->getId(),
                'CreditCardID' => $ccId,
            ]);
        }
    }

    public function testRemoveSubAccounts()
    {
        $testSubAccountCode = 'test_code_sub';
        $this->db->haveInDatabase('SubAccount', [
            'AccountID' => $this->account->getId(),
            'DisplayName' => 'Test',
            'Code' => $testSubAccountCode,
        ]);
        $this->db->seeNumRecords(1, 'SubAccount', ['AccountID' => $this->account->getId()]);
        $this->subAccountProcessor->process($this->account, new CheckAccountResponse());
        $this->db->seeNumRecords(0, 'SubAccount', ['AccountID' => $this->account->getId()]);
    }
}
