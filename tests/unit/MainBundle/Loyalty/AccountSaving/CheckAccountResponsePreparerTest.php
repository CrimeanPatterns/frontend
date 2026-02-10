<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\AccountSaving\CheckAccountResponsePreparer;
use AwardWallet\MainBundle\Loyalty\AccountSaving\IndirectAccountUpdater;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\Property;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount;
use AwardWallet\Tests\Modules\DbBuilder\Account as DBAccount;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class CheckAccountResponsePreparerTest extends BaseUserTest
{
    private ?CheckAccountResponsePreparer $preparer;

    private ?Account $account;

    public function _before()
    {
        parent::_before();

        $this->preparer = new CheckAccountResponsePreparer(
            $this->container->get(IndirectAccountUpdater::class),
            $this->makeEmpty(LoggerInterface::class)
        );
        $this->account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                $this->aw->createAwProvider(),
                'test',
                null,
                [
                    'Balance' => null,
                ]
            )
        );
    }

    public function _after()
    {
        $this->preparer = null;
        $this->account = null;

        parent::_after();
    }

    public function testUpdateAccountViaAccountNumber()
    {
        $providerCode = 'delta';
        $providerId = $this->db->grabFromDatabase('Provider', 'ProviderID', ['Code' => $providerCode]);
        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 250,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                        ['ProviderAccountNumber', 100500],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->db->dontSeeInDatabase('SubAccount', ['AccountID' => $this->account->getId()]);

        $accountId = $this->db->grabFromDatabase('Account', 'AccountID', [
            'ProviderID' => $providerId,
            'Login' => 100500,
            'Balance' => 250,
        ]);
        $this->assertNotEmpty($accountId);

        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 999,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                        ['ProviderAccountNumber', 100500],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->db->dontSeeInDatabase('SubAccount', ['AccountID' => $this->account->getId()]);
        $this->db->seeInDatabase('Account', [
            'AccountID' => $accountId,
            'Login' => 100500,
            'Balance' => 999,
        ]);
    }

    public function testUpdateAccountViaOwner()
    {
        $providerCode = 'mileageplus';

        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 500,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(1, $response->getSubaccounts());

        $targetAccountId = $this->aw->createAwAccount(
            $this->user->getId(),
            $providerCode,
            'test',
            null,
            [
                'Balance' => null,
            ]
        );
        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 250,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->db->seeInDatabase('Account', [
            'AccountID' => $targetAccountId,
            'Login' => 'test',
            'Balance' => 250,
        ]);

        $secondAccountId = $this->aw->createAwAccount(
            $this->user->getId(),
            $providerCode,
            'test2',
            null,
            [
                'Balance' => null,
            ]
        );
        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 500,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(1, $response->getSubaccounts());

        $uaId = $this->aw->createFamilyMember($this->user->getId(), 'John', 'Smith');
        $this->db->updateInDatabase('Account', ['UserAgentID' => $uaId], ['AccountID' => $secondAccountId]);
        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 130,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                        ['ProviderUserName', 'John M Smith'],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->db->seeInDatabase('Account', [
            'AccountID' => $secondAccountId,
            'Login' => 'test2',
            'Balance' => 130,
        ]);

        $this->aw->createAwAccount(
            $this->user->getId(),
            $providerCode,
            'test2',
            null,
            [
                'UserAgentID' => $uaId,
                'Balance' => null,
            ]
        );
        $this->preparer->prepare(
            $this->account,
            $response = $this->createResponse([
                [
                    'Balance' => 400,
                    'Props' => [
                        ['ProviderCode', $providerCode],
                        ['ProviderUserName', 'John Smith'],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(1, $response->getSubaccounts());
        $this->db->seeInDatabase('Account', [
            'AccountID' => $secondAccountId,
            'Login' => 'test2',
            'Balance' => 130,
        ]);
    }

    public function testUpdateAccountViaOwnerWithSharing()
    {
        $targetProviderId = $this->db->grabFromDatabase('Provider', 'ProviderID', ['Code' => $targetProviderCode = 'mileageplus']);
        $accountId = $this->dbBuilder->makeAccount(
            new DBAccount(
                $user = new User(null, false, ['FirstName' => 'John', 'LastName' => 'Smith']),
                new Provider('Test Provider' . StringHandler::getRandomCode(5)),
            )
        );
        $account = $this->em->getRepository(Account::class)->find($accountId);
        $targetAccountId = $this->dbBuilder->makeAccount(
            new DBAccount(
                $user,
                null,
                [],
                ['ProviderID' => $targetProviderId]
            )
        );
        $this->dbBuilder->makeAccount(
            new DBAccount(
                UserAgent::familyMember($user, 'Kirsi', 'Smith'),
                null,
                [],
                ['ProviderID' => $targetProviderId]
            )
        );
        $this->dbBuilder->makeAccount(
            (new DBAccount(
                $user2 = new User(null, false, ['FirstName' => 'Clarette', 'LastName' => 'Smith']),
                null,
                [],
                ['ProviderID' => $targetProviderId]
            ))
                ->shareTo(new UserAgent($user, $user2))
        );

        $this->preparer->prepare(
            $account,
            $response = $this->createResponse([
                [
                    'Balance' => 130,
                    'Props' => [
                        ['ProviderCode', $targetProviderCode],
                        ['ProviderUserName', 'John M Smith'],
                    ],
                ],
            ]),
            false
        );
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->db->seeInDatabase('Account', [
            'AccountID' => $targetAccountId,
            'Balance' => 130,
        ]);
    }

    public function testConvertSingleSubaccountToMainAccount()
    {
        $response = $this->createResponse([['Balance' => 250]]);
        $response->setBalance(0);
        $this->preparer->prepare($this->account, $response);
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(1, $response->getSubaccounts());

        $response = $this->createResponse([['Balance' => 250]]);
        $response->setBalance(null);
        $this->preparer->prepare($this->account, $response);
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->assertEquals(250, $response->getBalance());

        $response = $this->createResponse([['Balance' => 250]]);
        $response->setBalance(null);
        $response->setProperties([
            (new Property())
                ->setCode('CombineSubAccounts')
                ->setValue(false),
        ]);
        $this->preparer->prepare($this->account, $response);
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(1, $response->getSubaccounts());
        $this->assertNull($response->getBalance());

        $response = $this->createResponse([['Balance' => 250]]);
        $response->setBalance(null);
        $response->setProperties([
            (new Property())
                ->setCode('CombineSubAccounts')
                ->setValue(true),
        ]);
        $this->preparer->prepare($this->account, $response);
        $this->assertIsArray($response->getSubaccounts());
        $this->assertCount(0, $response->getSubaccounts());
        $this->assertEquals(250, $response->getBalance());
    }

    private function createResponse(array $subAccounts): CheckAccountResponse
    {
        return (new CheckAccountResponse())
            ->setState(ACCOUNT_CHECKED)
            ->setSubaccounts(
                array_map(function ($subAccount) {
                    return (new SubAccount())
                        ->setCode($code = StringHandler::getRandomCode(8))
                        ->setDisplayname(shell_exec("test display name $code"))
                        ->setBalance($subAccount['Balance'] ?? null)
                        ->setProperties(
                            array_map(function (array $prop) {
                                return (new Property())
                                    ->setCode($prop[0])
                                    ->setValue($prop[1]);
                            }, $subAccount['Props'] ?? [])
                        );
                }, $subAccounts)
            );
    }
}
