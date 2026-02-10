<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class AccountProcessorTest extends BaseUserTest
{
    private ?AccountProcessor $accountProcessor;

    private ?Account $account;

    public function _before()
    {
        parent::_before();

        $this->accountProcessor = $this->container->get(AccountProcessor::class);
        $this->account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                'testprovider',
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
        $this->account = null;
        $this->accountProcessor = null;

        parent::_after();
    }

    public function testResponseUserData()
    {
        $response = new CheckAccountResponse();
        $response
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setState(ACCOUNT_CHECKED)
            ->setBalance(100)
            ->setUserdata(
                (new UserData($this->account->getId()))
                    ->setCheckPastIts(true)
            );
        $this->accountProcessor->saveAccount($this->account, $response);
        $this->em->refresh($this->account);
        $this->assertNotNull($this->account->getLastCheckPastItsDate());
    }

    public function testUndeletedItineraries()
    {
        $userData = new UserData($this->account->getId());
        $userData->setCheckIts(true);
        $response = new CheckAccountResponse();
        $response
            ->setState(ACCOUNT_CHECKED)
            ->setUserdata($userData)
            ->setNoitineraries(true);

        /** @var Rental $rental */
        $rental = $this->em->getRepository(Rental::class)->find($this->db->haveInDatabase('Rental', [
            'AccountID' => $this->account->getId(),
            'Number' => StringHandler::getRandomCode(20) . 'aptest',
            'UserID' => $this->account->getUser()->getId(),
            'PickupLocation' => 'Test from',
            'PickupDatetime' => date("Y-m-d H:i:s", strtotime('+1 hour')),
            'DropoffLocation' => 'Test to',
            'DropoffDatetime' => date("Y-m-d H:i:s", strtotime('+24 hour')),
            'Hidden' => 0,
            'Undeleted' => 1,
        ]));
        $this->accountProcessor->saveAccount($this->account, $response);
        $this->db->seeInDatabase('Rental', [
            'RentalID' => $rental->getId(),
            'Hidden' => 0,
        ]);

        $rental->setUndeleted(false);
        $this->em->flush();
        $this->accountProcessor->saveAccount($this->account, $response);
        $this->db->seeInDatabase('Rental', [
            'RentalID' => $rental->getId(),
            'Hidden' => 1,
        ]);
    }
}
