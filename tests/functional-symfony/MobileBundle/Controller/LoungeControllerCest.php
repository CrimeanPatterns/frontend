<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Providercoupon as ProvidercouponEntity;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\AmexPlatinum;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\DragonPass;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\LoungeKey;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\PriorityPass;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use AwardWallet\Tests\Modules\DbBuilder\CreditCard;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @group frontend-functional
 * @group mobile
 */
class LoungeControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use JsonHeaders;

    private ?RouterInterface $router;

    private ?CsrfTokenManagerInterface $csrfTokenManager;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->csrfTokenManager = $I->grabService('security.csrf.token_manager');

        $I->haveHttpHeader('x-aw-version', '4.44.0');
        $I->haveHttpHeader('x-aw-platform', 'ios');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $this->router = null;
        $this->csrfTokenManager = null;
    }

    public function testSelectCards(\TestSymfonyGuy $I)
    {
        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(403);
        $this->loginUser($I, $this->user);
        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(200);
        // user has no cards
        $I->seeInDatabase('Usr', [
            'UserID' => $this->user->getId(),
            'AvailableCardsUpdateDate' => null,
            'HaveDragonPassCard' => 0,
        ]);
        $I->dontSeeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
        ]);

        // add 3 credit cards + DragonPass + PriorityPass + LoungeKey
        $provider = new Provider();
        $cc1Id = $I->makeCreditCard(new CreditCard($provider, 'Test Credit Card #1'));
        $cc2Id = $I->makeCreditCard(new CreditCard($provider, 'Test Credit Card #2'));
        $cc3Id = $I->makeCreditCard(new CreditCard($provider, 'Test Credit Card #3'));
        $I->haveInDatabase('CreditCardLoungeCategory', [
            'CreditCardID' => $cc2Id,
            'LoungeCategoryID' => AmexPlatinum::getCategoryId(),
        ]);

        // submit form, checked 3 cards
        $this->sendSelectCardsRequest($I, 'POST', [
            'selectedCards' => ["cc{$cc1Id}", "cc{$cc2Id}", "cc{$cc3Id}", DragonPass::getCardId(), PriorityPass::getCardId(), LoungeKey::getCardId()],
        ]);
        $I->seeResponseCodeIs(200);
        $updateDate = $I->grabColumnFromDatabase('Usr', 'AvailableCardsUpdateDate', [
            'UserID' => $this->user->getId(),
            'HaveDragonPassCard' => 1,
            'HaveLoungeKeyCard' => 1,
            'HavePriorityPassCard' => 1,
        ]);
        $I->assertNotEmpty($updateDate);
        $I->assertNotEmpty($updateDate[0]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc1Id,
        ]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc2Id,
        ]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc3Id,
        ]);

        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(200);

        // uncheck 2 cards
        $this->sendSelectCardsRequest($I, 'POST', [
            'selectedCards' => ["cc{$cc2Id}"],
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc1Id,
        ]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc2Id,
        ]);
        $I->dontSeeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc3Id,
        ]);
    }

    public function testSelectCardsWithDetect(\TestSymfonyGuy $I)
    {
        $this->loginUser($I, $this->user);
        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['autoDetectCards' => ['enabled' => false]]);

        // user has no cards
        $I->seeInDatabase('Usr', [
            'UserID' => $this->user->getId(),
            'AvailableCardsUpdateDate' => null,
            'HaveDragonPassCard' => 0,
        ]);
        $I->dontSeeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
        ]);

        // add 2 credit cards + DragonPass + PriorityPass + LoungeKey
        $provider = new Provider();
        $cc1Id = $I->makeCreditCard(new CreditCard($provider, 'Test Credit Card #1'));
        $cc2Id = $I->makeCreditCard(new CreditCard($provider, 'Test Credit Card #2'));
        $I->haveInDatabase('CreditCardLoungeCategory', [
            'CreditCardID' => $cc2Id,
            'LoungeCategoryID' => AmexPlatinum::getCategoryId(),
        ]);

        // submit form, checked 3 cards
        $this->sendSelectCardsRequest($I, 'POST', [
            'selectedCards' => ["cc{$cc1Id}", "cc{$cc2Id}", DragonPass::getCardId(), PriorityPass::getCardId(), LoungeKey::getCardId()],
        ]);
        $I->seeResponseCodeIs(200);
        $updateDate = $I->grabColumnFromDatabase('Usr', 'AvailableCardsUpdateDate', [
            'UserID' => $this->user->getId(),
            'HaveDragonPassCard' => 1,
            'HaveLoungeKeyCard' => 1,
            'HavePriorityPassCard' => 1,
        ]);
        $I->assertNotEmpty($updateDate);
        $I->assertNotEmpty($updateDate[0]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc1Id,
        ]);
        $I->seeInDatabase('UserCard', [
            'UserID' => $this->user->getId(),
            'CreditCardID' => $cc2Id,
        ]);

        // check auto detect
        $this->sendSelectCardsRequest($I, 'POST', [
            'autoDetect' => 1,
            'selectedCards' => ["cc{$cc1Id}", "cc{$cc2Id}", DragonPass::getCardId(), PriorityPass::getCardId()],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase('Usr', [
            'UserID' => $this->user->getId(),
            'AutoDetectLoungeCards' => 1,
            'HaveDragonPassCard' => 1,
            'HaveLoungeKeyCard' => 0,
            'HavePriorityPassCard' => 1,
        ]);

        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'cards' => [
                [
                    'id' => 'card_pp',
                    'selected' => true,
                    'autoSelected' => false,
                ],
            ],
            'autoDetectCards' => ['enabled' => true],
        ]);

        // add Priority Pass document
        $I->makeProviderCoupon(
            new ProviderCoupon('Priority Pass', null, null, PROVIDER_KIND_AIRLINE, [
                'UserID' => $this->user->getId(),
                'TypeID' => ProvidercouponEntity::TYPE_PRIORITY_PASS,
            ])
        );

        $this->sendSelectCardsRequest($I, 'GET');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'cards' => [
                [
                    'id' => 'card_pp',
                    'selected' => true,
                    'autoSelected' => true,
                ],
            ],
            'autoDetectCards' => ['enabled' => true],
        ]);
    }

    private function sendSelectCardsRequest(\TestSymfonyGuy $I, string $method, array $data = [])
    {
        $route = $this->router->generate('awm_timeline_lounge_select_cards');
        $I->haveHttpHeader('X-XSRF-TOKEN', $this->csrfTokenManager->getToken('')->getValue());
        $I->send($method, $route, $data);
    }
}
