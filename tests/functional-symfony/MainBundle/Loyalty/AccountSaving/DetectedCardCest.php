<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\DetectedCard;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class DetectedCardCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private string $provider;

    private int $providerId;

    private ?AccountProcessor $accountProcessor;

    private ?Account $account;

    private ?int $creditCardId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->provider = 'testprovider';
        $this->providerId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);

        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.default_entity_manager');

        $this->account = $em->getRepository(Account::class)
            ->find($I->createAwAccount($this->user->getId(), $this->provider, 'login1'));

        $this->accountProcessor = $I->grabService(AccountProcessor::class);

        $this->creditCardId = $I->haveInDatabase("CreditCard", [
            'ProviderID' => $this->providerId,
            'CobrandProviderID' => $this->providerId,
            'Name' => 'Some card for you',
            'DisplayNameFormat' => 'Some {number_ending}',
            'Patterns' => 'Some card for you',
            'MatchingOrder' => 1,
        ]);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        if ($this->user) {
            $I->executeQuery("DELETE FROM Usr WHERE UserID = " . $this->user->getId());
            $this->user = null;
        }

        $I->executeQuery("DELETE FROM CreditCard WHERE CreditCardID = " . $this->creditCardId);

        $this->account = null;
        $this->accountProcessor = null;
    }

    public function testProcess(\TestSymfonyGuy $I)
    {
        foreach ($this->detectedCards() as $example) {
            $this->accountProcessor->saveAccount(
                $this->account,
                $this->getCheckAccountResponse($example['detectedCards'], $example['status'])
            );

            foreach ($example['see'] as $dc) {
                $I->seeInDatabase('DetectedCard', [
                    'Code' => $dc->getCode(),
                    'DisplayName' => $dc->getDisplayname(),
                    'Description' => $dc->getCarddescription(),
                ]);
            }

            foreach ($example['dontSee'] as $dc) {
                $I->dontSeeInDatabase('DetectedCard', [
                    'Code' => $dc->getCode(),
                    'DisplayName' => $dc->getDisplayname(),
                    'Description' => $dc->getCarddescription(),
                ]);
            }
        }
    }

    private function detectedCards()
    {
        $dc1 = (new DetectedCard())
            ->setCode($this->provider . '1234')
            ->setDisplayname('Some card for you')
            ->setCarddescription('Does not earn points');
        $dc2 = (new DetectedCard())
            ->setCode($this->provider . '1256')
            ->setDisplayname('Other card for you')
            ->setCarddescription('Does not earn points');
        $dc3 = (new DetectedCard())
            ->setCode($this->provider . '1256')
            ->setDisplayname('New other card')
            ->setCarddescription('Cancelled');

        return [
            ['detectedCards' => [$dc1, $dc2], 'status' => ACCOUNT_CHECKED, 'see' => [$dc1, $dc2], 'dontSee' => [$dc3]],
            [
                'detectedCards' => [$dc1, $dc3],
                'status' => ACCOUNT_INVALID_PASSWORD,
                'see' => [$dc1, $dc2],
                'dontSee' => [$dc3],
            ],
            ['detectedCards' => [$dc1, $dc3], 'status' => ACCOUNT_CHECKED, 'see' => [$dc1, $dc3], 'dontSee' => [$dc2]],
            ['detectedCards' => [$dc1], 'status' => ACCOUNT_CHECKED, 'see' => [$dc1], 'dontSee' => [$dc2, $dc3]],
        ];
    }

    private function getCheckAccountResponse(array $detectedCards, int $state): CheckAccountResponse
    {
        $response = new CheckAccountResponse();
        $response
            ->setUserdata(new UserData($this->account->getId()))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setDetectedcards($detectedCards)
            ->setState($state)
            ->setBalance(200);

        return $response;
    }
}
