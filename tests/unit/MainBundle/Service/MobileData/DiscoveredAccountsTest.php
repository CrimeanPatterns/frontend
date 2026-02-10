<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\MobileData;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\MobileData\DiscoveredAccounts;
use AwardWallet\Tests\Modules\Utils\Prophecy\ArgumentExtended;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

/**
 * @coversDefaultClass \DiscoveredAccounts
 * @group frontend-unit
 */
class DiscoveredAccountsTest extends BaseTest
{
    public function testGetList()
    {
        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'provider' => 'First acc provider',
                    'login' => 'login',
                    'email' => 'source@email.com',
                ],
                [
                    'id' => 2,
                    'provider' => 'Second acc provider',
                    'login' => 'account number',
                    'email' => 'First Last',
                ],
            ],
            (new DiscoveredAccounts($this->getAccountRepository([
                $this
                    ->getAccountById(1)
                    ->setProviderid(
                        (new Provider())
                            ->setDisplayname('First acc provider')
                    )
                    ->setLogin('login')
                    ->setSourceEmail('source@email.com'),
                $this
                    ->getAccountById(2)
                    ->setProviderid(
                        (new Provider())
                            ->setDisplayname('Second acc provider')
                    )
                    ->setProperties(new ArrayCollection([
                        (new Accountproperty())
                            ->setVal('account number')
                            ->setProviderpropertyid(
                                (new Providerproperty())
                                    ->setKind(PROPERTY_KIND_NUMBER)
                            ),
                    ])),
            ])))->getList(
                (new Usr())
                    ->setFirstname('First')
                    ->setLastname('Last')
            )
        );
    }

    /**
     * @param Account[] $accounts
     */
    protected function getAccountRepository(array $accounts): AccountRepository
    {
        $newThis = $this;
        $accountRepo = $this->prophesize(AccountRepository::class);
        $accountRepo
            ->getPendingsQuery(ArgumentExtended::cetera())
            ->will(function () use ($accounts, $newThis) {
                $queryBuilder = $newThis->prophesize(QueryBuilder::class);
                $queryBuilder
                    ->getQuery()
                    ->will(function () use ($accounts, $newThis) {
                        $query = $newThis->prophesize(AbstractQuery::class);
                        $query
                            ->execute()
                            ->willReturn($accounts);

                        return $query->reveal();
                    });

                return $queryBuilder->reveal();
            });

        return $accountRepo->reveal();
    }

    protected function getAccountById(int $id): Account
    {
        return new class($id) extends Account {
            /**
             * @var int
             */
            private $id;

            public function __construct(int $id)
            {
                $this->id = $id;
            }

            public function getAccountid()
            {
                return $this->id;
            }
        };
    }
}
