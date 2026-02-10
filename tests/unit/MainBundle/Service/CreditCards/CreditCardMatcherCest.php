<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardPatternsSource;
use Codeception\Stub\Expected;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class CreditCardMatcherCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testMultipleMatches(\TestSymfonyGuy $I)
    {
        $doubleLogged = false;
        $logger = $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Expected::atLeastOnce(function (string $message, array $context = []) use (&$doubleLogged, $I) {
                if ($message === "cc_multiple_matches") {
                    $doubleLogged = true;
                    $I->assertEquals([
                        "Name" => "Citi Prestige Two 1234",
                        "Matches" => [["CreditCardID" => 1, "Pattern" => "#Citi Prestige#i", "MatchingOrder" => 100], ["CreditCardID" => 2, "Pattern" => "#Citi Prestige Two#i", "MatchingOrder" => 100]],
                        "MatchedCreditCards" => "[1][2]",
                        "MatchedPatterns" => "[1-#Citi Prestige#i][2-#Citi Prestige Two#i]",
                        "CreditCardID" => 1,
                    ], $context);
                }
            }),
        ]);

        $patterns = [
            1 => [
                1 => [
                    'Patterns' => [
                        '#Citi Prestige#i',
                    ],
                    'MatchingOrder' => 100,
                ],
                2 => [
                    'Patterns' => [
                        '#Citi Prestige Two#i',
                    ],
                    'MatchingOrder' => 100,
                ],
            ],
        ];
        $patternsSource = $I->stubMakeEmpty(CreditCardPatternsSource::class, [
            'getPatterns' => Expected::once($patterns),
        ]);

        $matcher = new CreditCardMatcher(
            $logger,
            $patternsSource
        );

        $cardId = $matcher->identify("Citi Prestige Two 1234", 1);
        $I->assertEquals(1, $cardId);
        $I->verifyMocks();
        $I->assertTrue($doubleLogged);
    }

    public function testDifferentMatchigOrder(\TestSymfonyGuy $I)
    {
        $doubleLogged = false;
        $logger = $I->stubMakeEmpty(LoggerInterface::class, [
            'info' => Expected::never(),
        ]);

        $patterns = [
            1 => [
                2 => [
                    'Patterns' => [
                        '#Citi Prestige Two#i',
                    ],
                    'MatchingOrder' => 100,
                ],
                1 => [
                    'Patterns' => [
                        '#Citi Prestige#i',
                    ],
                    'MatchingOrder' => 200,
                ],
            ],
        ];
        $patternsSource = $I->stubMakeEmpty(CreditCardPatternsSource::class, [
            'getPatterns' => Expected::once($patterns),
        ]);

        $matcher = new CreditCardMatcher(
            $logger,
            $patternsSource
        );

        $cardId = $matcher->identify("Citi Prestige Two 1234", 1);
        $I->assertEquals(2, $cardId);
        $I->verifyMocks();
    }

    public function testSingleMatch(\TestSymfonyGuy $I)
    {
        $doubleLogged = false;
        $logger = $I->stubMakeEmpty(LoggerInterface::class);

        $patterns = [
            1 => [
                1 => [
                    'Patterns' => [
                        '#Citi Economy#i',
                    ],
                    'MatchingOrder' => 100,
                ],
                2 => [
                    'Patterns' => [
                        '#Citi Prestige#i',
                    ],
                    'MatchingOrder' => 100,
                ],
            ],
        ];
        $patternsSource = $I->stubMakeEmpty(CreditCardPatternsSource::class, [
            'getPatterns' => Expected::once($patterns),
        ]);

        $matcher = new CreditCardMatcher(
            $logger,
            $patternsSource
        );

        $cardId = $matcher->identify("Citi Prestige Two 1234", 1);
        $I->assertEquals(2, $cardId);
        $I->verifyMocks();
        $I->assertFalse($doubleLogged);
    }
}
