<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardPatternsSource;
use AwardWallet\MainBundle\Service\CreditCards\PatternsParser;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class CreditCardPatternsSourceCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testLoad(\TestSymfonyGuy $I)
    {
        $provider = new Provider(1);

        $card1 = new CreditCard(1);
        $card1->setPatterns("#Citi Prestige#i\n#regexp with <tags>#i\nSome word\n Some trimmed word \nReplaced Ⓒ symbol\nStripped <a> tags\n\n\n");
        $card1->setProvider($provider);
        $card1->setMatchingOrder(100);

        $card2 = new CreditCard(2);
        $card2->setPatterns("#Citi Prestige Two#i");
        $card2->setProvider($provider);
        $card2->setMatchingOrder(200);

        $cardRepo = $I->stubMakeEmpty('AwardWallet\MainBundle\Repository\CreditCardRepository', [
            'findBy' => [
                $card1,
                $card2,
            ],
        ]);

        $cacheManager = $I->stubMakeEmpty(CacheManager::class, [
            'load' => function (CacheItemReference $cacheItemReference) {
                return $cacheItemReference->loadData([]);
            },
        ]);

        $source = new CreditCardPatternsSource($cardRepo, $cacheManager, new NullLogger(), new PatternsParser());
        $I->assertEquals([
            1 => [
                1 => [
                    'Patterns' => [
                        '#Citi Prestige#i',
                        '#regexp with <tags>#i',
                        'Some word',
                        'Some trimmed word',
                        'Replaced symbol',
                        'Stripped tags',
                    ],
                    'MatchingOrder' => 100,
                ],
                2 => [
                    'Patterns' => [
                        '#Citi Prestige Two#i',
                    ],
                    'MatchingOrder' => 200,
                ],
            ],
        ], $source->getPatterns());
    }
}
