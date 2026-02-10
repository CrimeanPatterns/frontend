<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Constants
{
    public const CARD_ID_UNITED_EXPLORER = 50;
    public const CARD_ID_MARRIOTT = 51;
    public const CARD_ID_HYATT = 52;
    public const CARD_ID_CSP = 6;
    public const CARD_ID_CSR = 5;
    public const CARD_ID_CFU = 1;
    public const CARD_ID_CS = 37;

    public const CARD_IDS = [
        self::CARD_ID_UNITED_EXPLORER,
        self::CARD_ID_MARRIOTT,
        self::CARD_ID_HYATT,
        self::CARD_ID_CFU,
        self::CARD_ID_CSP,
        self::CARD_ID_CSR,
        self::CARD_ID_CS,
    ];

    public const CARD_NAMES = [
        self::CARD_ID_UNITED_EXPLORER => 'united',
        self::CARD_ID_MARRIOTT => 'marriott',
        self::CARD_ID_HYATT => 'hyatt',
        self::CARD_ID_CSP => 'csp',
        self::CARD_ID_CSR => 'csr',
        self::CARD_ID_CFU => 'cfu',
        self::CARD_ID_CS => 'cs',
    ];

    /**
     * if user has some card, then exclude some other cards.
     */
    public const CARD_EXCLUDES = [
        self::CARD_ID_CSR => [self::CARD_ID_CSP],
        self::CARD_ID_CS => [self::CARD_ID_CSP],
    ];

    /**
     * used to select what card to send to user
     * card will be selected from top to bottom
     * [
     *      [CardID1 => percentage, CardID2 => percentage] - will balance cards with same priority according percentage
     *      ...
     * ].
     */
    public const CARD_PRIORITY = [
        [self::CARD_ID_UNITED_EXPLORER => 100],
        [self::CARD_ID_MARRIOTT => 100],
        [self::CARD_ID_HYATT => 100],
        [self::CARD_ID_CFU => 35, self::CARD_ID_CSP => 65],
    ];

    public const CARD_TEMPLATES = [
        self::CARD_ID_UNITED_EXPLORER => ['UnitedExplorerA'],
        self::CARD_ID_MARRIOTT => ['MarriottBonvoyBoundlessCardA'],
        self::CARD_ID_HYATT => ['WorldOfHyattCardA', 'WorldOfHyattCardB'],
        self::CARD_ID_CFU => ['FreedomUnlimitedA', 'FreedomUnlimitedB'],
        self::CARD_ID_CSP => ['SapphirePreferredA', 'SapphirePreferredB'],
    ];
}
