<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\BlogUserPost;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Image\Image;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\MainBundle\Service\UserAvatar;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Learn implements TranslationContainerInterface
{
    public const MAX_POST_EXPIRING = 5;
    public const COUNT_POST = 20;
    public const OFFER_CARDS_LIMIT = 4;

    public const SLUG_PROVIDER = 'provider';
    public const SLUG_EXPIRING = 'expiring';
    public const SLUG_TRAVELS = 'travels';
    public const SLUG_SUBACCOUNT = 'subAccount';

    private LoggerInterface $logger;
    private \Memcached $cache;
    private OptionsFactory $optionsFactory;
    private AccountListManager $accountListManager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;
    private DesktopListMapper $desktopListMapper;
    private BlogPostInterface $blogPost;
    private BlogApi $blogApi;
    private RouterInterface $router;
    private UserAvatar $userAvatar;
    private TranslatorInterface $translator;
    private LearnTimeline $learnTimeline;
    private LocalizeService $localizeService;
    private UserPost $userPost;
    private Formatter $intervalFormatter;

    private array $accounts = [];
    private array $accountProviders;
    private array $agents;
    private array $currencyLocalizedList;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        \Memcached $memcached,
        BlogPostInterface $blogPost,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        OptionsFactory $optionsFactory,
        DesktopListMapper $desktopListMapper,
        AccountListManager $accountListManager,
        BlogApi $blogApi,
        RouterInterface $router,
        UserAvatar $userAvatar,
        TranslatorInterface $translator,
        LearnTimeline $learnTimeline,
        LocalizeService $localizeService,
        UserPost $userPost,
        Formatter $intervalFormatter
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cache = $memcached;
        $this->blogPost = $blogPost;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->optionsFactory = $optionsFactory;
        $this->desktopListMapper = $desktopListMapper;
        $this->accountListManager = $accountListManager;
        $this->blogApi = $blogApi;
        $this->router = $router;
        $this->userAvatar = $userAvatar;
        $this->translator = $translator;
        $this->learnTimeline = $learnTimeline;
        $this->localizeService = $localizeService;
        $this->userPost = $userPost;
        $this->intervalFormatter = $intervalFormatter;
    }

    public function getMenu(): array
    {
        $menu = $this->blogApi->getRestApiData(Constants::API_URL_GET_MENU, [BlogApi::OPTION_EXPIRATION => 86400]);

        return $this->normalizeMenuItems($menu);
    }

    public function getLabels(): array
    {
        $result = [
            [
                'name' => $this->translator->trans('recommended'),
                'link' => $this->router->generate('aw_blog_learn'),
                'icon' => 'star',
            ],
        ];

        $result = array_merge($result, [
            [
                'name' => $this->translator->trans('latest-posts'),
                'link' => $this->router->generate('aw_blog_learn_latestposts'),
            ],
            [
                'name' => 'Tips & Tricks',
                'link' => $this->router->generate(
                    'aw_blog_learn_category',
                    ['category' => Constants::CATEGORY_SLUG[Constants::CATEGORY_AW_TIPS_AND_TRICKS_ID]]
                ),
            ],
            [
                'name' => 'News',
                'link' => $this->router->generate(
                    'aw_blog_learn_category',
                    ['category' => Constants::CATEGORY_SLUG[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID]]
                ),
            ],
            [
                'name' => 'Credit Cards',
                'link' => $this->router->generate(
                    'aw_blog_learn_category',
                    ['category' => Constants::CATEGORY_SLUG[Constants::CATEGORY_CARDS_OFFERS_AND_GUIDES_ID]]
                ),
            ],
        ]);

        $popular = $this->getPopularity();

        foreach ($popular as $provider) {
            $result[] = [
                'name' => $provider['displayName'],
                'link' => $this->router->generate(
                    'aw_blog_learn_provider',
                    ['providerCode' => $provider['code']]
                ),
            ];
        }

        return $result;
    }

    public function fetchLatestNews(): array
    {
        $posts = $this->fetchPostByOptions([
            BlogPost::OPTION_KEY_AFTER_DATE => (new \DateTime())->sub(new \DateInterval('P14D')),
            BlogPost::OPTION_KEY_CATEGORY_ID => Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID,
        ]);

        $list = [];

        foreach ($posts as $post) {
            $list[] = $this->normalizePost($post);
        }

        return [
            'title' => $this->translator->trans('latest-news'),
            'more' => [
                'text' => $this->translator->trans('see-all-posts'),
                'link' => $this->router->generate(
                    'aw_blog_learn_category',
                    ['category' => Constants::CATEGORY_SLUG[Constants::CATEGORY_NEWS_AND_PROMOTIONS_ID]]
                ),
            ],
            'posts' => $list,
        ];
    }

    public function fetchByExpiring(int $maxPostExpiring = self::MAX_POST_EXPIRING): array
    {
        $providers = $this->fetchProvidersByAccounts();
        $accounts = $this->sortAccountsByExpiration($this->getAccounts());

        if (empty($accounts)) {
            return [];
        }

        $expirationPostsId = array_column($providers, 'BlogIdsMileExpiration');
        $expirationPostsId = array_map(static fn ($item) => explode(',', $item), $expirationPostsId);
        $expirationPostsId = array_merge(...$expirationPostsId);
        $expirationPostsId = array_filter(array_unique($expirationPostsId));

        $expirationPosts = $this->fetchPostByOptions([BlogPost::OPTION_KEY_POST_ID => $expirationPostsId]);

        if (empty($expirationPosts)) {
            return [];
        }
        $expirationPosts = array_column($expirationPosts, null, 'id');

        $list = $uniquePostId = [];

        foreach ($accounts as $account) {
            $providerId = $account['ProviderID'] ?? 0;

            if (!$providerId || empty($providers[$providerId]['BlogIdsMileExpiration'])) {
                continue;
            }

            $blogIdsMileExpiration = StringUtils::getIntArrayFromString($providers[$providerId]['BlogIdsMileExpiration']);

            if (empty($blogIdsMileExpiration)) {
                continue;
            }

            foreach ($blogIdsMileExpiration as $postId) {
                if (!array_key_exists($postId, $expirationPosts) || array_key_exists($postId, $uniquePostId)) {
                    continue;
                }

                $uniquePostId[$postId] = true;
                $post = $this->normalizePost($expirationPosts[$postId]);

                if (!isset($post['meta']) || !array_key_exists(self::SLUG_EXPIRING, $post['meta'])) {
                    $post['meta'][self::SLUG_EXPIRING] = [];
                }

                $post['meta'][self::SLUG_EXPIRING][] = [
                    'providerId' => $providerId,
                    'providerCode' => $account['ProviderCode'] ?? null,
                    'displayName' => strip_tags($account['DisplayNameFormated']),
                    'owner' => $this->getOwner($account['AccountOwner']),
                    // 'accountNumber' => $account['LoginFieldFirst'],
                    'balance' => $this->getAccountBalance($account),
                    // 'eliteLevel' => !empty($account['EliteStatuses']) && $account['EliteLevelsCount'] > 0 && isset($account['Elitism']) ? ($account['EliteStatuses'][$account['Elitism']] ?? null) : null,
                    'expirationState' => $account['_expiringState'] ?? null,
                    // 'expirationDate' => $account['ExpirationDate'] ?? null,
                    'expirationDate' => $account['_expiringDate'] ?? null,
                    'expirationDateShort' => $account['ExpirationKnown']
                        ? $this->dateShortExpiration(new \DateTime($account['ExpirationDateYMD'] ?? '@' . $account['ExpirationDateTs']))
                        : null,
                    'link' => $this->router->generate('aw_account_list', ['account' => $account['ID']]),
                    'logo' => $this->getProviderLogo($account['ProviderCode']),
                ];

                $list[] = $post;
            }

            if (count($list) >= $maxPostExpiring) {
                break;
            }
        }

        if (empty($list)) {
            return [];
        }

        $result = [
            'title' => 'Learn how to save your points and miles from expiring',
            'more' => [
                'text' => $this->translator->trans('see-all-posts'),
                'link' => $this->router->generate('aw_blog_learn_expiring'),
            ],
            'posts' => $list,
        ];

        if ($maxPostExpiring !== self::MAX_POST_EXPIRING) {
            unset($result['more']);
        }

        return $result;
    }

    public function getAccounts(): array
    {
        if (!empty($this->accounts)) {
            return $this->accounts;
        }

        $user = $this->getUser();

        if (null === $user) {
            return [];
        }

        $accounts = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_LOAD_SUBACCOUNTS, true)
                    ->set(Options::OPTION_FORMATTER, $this->desktopListMapper)
                    ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                    ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
                    ->set(Options::OPTION_LOAD_BLOG_POSTS, true)
                    ->set(Options::OPTION_LOAD_PROPERTIES, false)
            )
            ->getAccounts();

        foreach ($accounts as &$account) {
            $providerId = $account['ProviderID'] ?? 0;

            if (0 === $providerId
                && !empty($account['DisplayName'])
                && AmericanAirlinesAAdvantageDetector::isMatchByName($account['DisplayName'])
            ) {
                $account['ProviderID'] = Provider::AA_ID;
                $account['ProviderCode'] = Provider::AA_CODE;
                $account['ProviderCurrency'] = $this->getCurrencyList()[Currency::MILES_ID][1] ?? '';
                $account['ProviderCurrencies'] = $this->getCurrencyList()[Currency::MILES_ID][2] ?? '';
            }
        }

        $this->accounts = $this->sortAccountsByBalance($accounts);

        return $this->accounts;
    }

    public function sortAccountsByBalance(array $accounts): array
    {
        usort($accounts, static function ($a, $b) {
            $valA = $a['TotalUSDCashRaw'] ?? $a['USDCashRaw'] ?? 0;
            $valB = $b['TotalUSDCashRaw'] ?? $b['USDCashRaw'] ?? 0;

            if ($valA === $valB) {
                $balanceA = $a['TotalBalance'];
                $balanceB = $b['TotalBalance'];

                if ($balanceA === $balanceB) {
                    $rankA = $a['Rank'] ?? 0;
                    $rankB = $b['Rank'] ?? 0;

                    return $rankB <=> $rankA;
                }

                return $balanceB <=> $balanceA;
            }

            return $valB <=> $valA;
        });

        return $accounts;
    }

    public function sortAccountsByExpiration(array $accounts): array
    {
        $max = null;

        $knownAccounts = [];

        foreach ($accounts as &$account) {
            $account['_expireNext'] = $max;

            //            if (!$account['IsActive']) {
            //                continue;
            //            }

            if ($account['ExpirationKnown'] && $account['ExpirationDateTs']) {
                $ts = (int) $account['ExpirationDateTs'];

                if (null === $account['_expireNext'] || $ts < $account['_expireNext']) {
                    $account['_expireNext'] = $ts;
                }

                if (null !== ($expiration = $this->getExpirationDate($ts, $account['ExpirationDate']))) {
                    $account['_expiringState'] = $expiration['state'];
                    $account['_expiringDate'] = $expiration['expiration'];
                    $account['_expiringDateShort'] = $expiration['expirationShort'];
                    $knownAccounts[] = $account;
                }
            }
            /*
            if (empty($account['SubAccountsArray'])) {
                continue;
            }

            foreach ($account['SubAccountsArray'] as $subAccount) {
                if (!$subAccount['ExpirationKnown']) {
                    continue;
                }

                $ts = (int) $subAccount['ExpirationDateTs'];
                if (null === $account['_expireNext'] || $ts < $account['_expireNext']) {
                    $account['_expireNext'] = $ts;
                }
            }
            */
        }

        $max = time() + (86400 * 365 * 30);
        usort($knownAccounts, static function ($a, $b) use ($max) {
            $valA = $a['_expireNext'] ?? null;
            $valB = $b['_expireNext'] ?? null;

            if (null === $valA) {
                $valA = $max;
            }

            if (null === $valB) {
                $valB = $max;
            }

            if ($valA === $valB) {
                $balanceA = $a['TotalUSDCashRaw'] ?? $a['USDCashRaw'] ?? $a['TotalBalance'];
                $balanceB = $b['TotalUSDCashRaw'] ?? $b['USDCashRaw'] ?? $b['TotalBalance'];

                if ($balanceA === $balanceB) {
                    $rankA = $a['Rank'] ?? 0;
                    $rankB = $b['Rank'] ?? 0;

                    return $rankB <=> $rankA;
                }

                return $balanceB <=> $balanceA;
            }

            return $valA <=> $valB;
        });

        return $knownAccounts;
    }

    public function getCardOffers(): array
    {
        // temporary data for creating a layout
        // TODO: remove after release
        if (BlogPost::IS_DEV_TEST) {
            return json_decode(file_get_contents(__DIR__ . '/../../../../../tests/_data/Blog/cardOffers.json'), true);
        }

        $offer = [];
        $excludeCardsId = $this->getUserExcludeCardsId();

        if (empty($excludeCardsId)) {
            $excludeCardsId = [-1];
        }

        $limit = self::OFFER_CARDS_LIMIT * 2;
        $cardCondition = '
                    cc.CreditCardID NOT IN (:excludeCardsId)
                AND cc.QsCreditCardID IS NOT NULL
                AND cc.DirectClickURL IS NOT NULL
        ';
        $cards = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                cc.CreditCardID, cc.CardFullName, cc.Description, cc.DirectClickURL, cc.PictureVer, cc.PictureExt, cc.QsCreditCardID, cc.RankIndex,
                qcc.QsCardInternalKey
            FROM CreditCard cc
            JOIN QsCreditCard qcc ON (cc.QsCreditCardID = qcc.QsCreditCardID)
            WHERE
                    ' . $cardCondition . '
                AND cc.RankIndex IS NOT NULL
            ORDER BY cc.RankIndex ASC
            LIMIT :limit
        ',
            ['excludeCardsId' => $excludeCardsId, 'limit' => $limit],
            ['excludeCardsId' => Connection::PARAM_INT_ARRAY, 'limit' => \PDO::PARAM_INT]
        );

        if (count($cards) < self::OFFER_CARDS_LIMIT) {
            $excludeCardsId = array_merge($excludeCardsId, array_column($cards, 'CreditCardID'));
            $extCards = $this->entityManager->getConnection()->fetchAllAssociative('
                SELECT
                    cc.CreditCardID, cc.CardFullName, cc.Description, cc.DirectClickURL, cc.PictureVer, cc.PictureExt, cc.QsCreditCardID,
                    qcc.QsCardInternalKey
                FROM CreditCardOffer co
                JOIN CreditCard cc ON (cc.CreditCardID = co.CreditCardID)
                JOIN QsCreditCard qcc ON (qcc.QsCreditCardID = cc.QsCreditCardID)
                WHERE
                        co.IsMonetized = 1
                    AND (co.EndDate > NOW() OR co.EndDate IS NULL)
                    AND ' . $cardCondition . '
                ORDER BY cc.RankIndex ASC, co.SubjectiveValue DESC
                LIMIT :limit
            ',
                ['excludeCardsId' => $excludeCardsId, 'limit' => self::OFFER_CARDS_LIMIT - count($cards)],
                ['excludeCardsId' => Connection::PARAM_INT_ARRAY, 'limit' => \PDO::PARAM_INT]
            );
            $cards = array_merge($cards, $extCards);
        }
        $cards = array_chunk($cards, self::OFFER_CARDS_LIMIT)[0] ?? [];

        $qsCardsId = array_column($cards, 'QsCardInternalKey');
        $cardsBlog = $this->blogApi->getRestApiData(Constants::API_URL_GET_CREDIT_CARDS, [
            'queryData' => ['cardId' => implode(',', $qsCardsId)],
        ]);

        $linkArg = [
            'cid' => 'learn',
            'mid' => 'web',
        ];

        if (null !== $this->getUser()) {
            $linkArg['rkbtyn'] = $this->getUser()->getRefcode();
        }

        foreach ($cards as $card) {
            $cardId = $card['CreditCardID'];

            $offer[$cardId] = [
                'name' => $card['CardFullName'],
                'description' => $card['Description'],
                'details' => null,
                'thumb' => Image::getPath($cardId,
                    CreditCard::PICTURE_FOLDERNAME,
                    'medium',
                    $card['PictureVer'],
                    $card['PictureExt']
                ),
                'ratesFees' => null,
                'annualFees' => null,
                'creditScoreNeeded' => null,
                'applyLink' => null,
                'applyText' => null,
                'applyNow' => StringHandler::replaceVarInLink($card['DirectClickURL'], $linkArg), // TODO: remove
            ];

            $qsCardId = $card['QsCardInternalKey'];

            if ($qsCardId && is_array($cardsBlog) && array_key_exists($qsCardId, $cardsBlog)) {
                $ratesFees = $cardsBlog[$qsCardId]->ratesFeesFCW;
                $ratesFees = false === strpos($ratesFees, 'target=')
                    ? str_replace('href=', 'target="_blank" href=', $ratesFees)
                    : preg_replace('/(target=")[^"]*(")/', '${1}_blank${2}', $ratesFees);

                $offer[$cardId] = array_merge($offer[$cardId], [
                    'description' => $cardsBlog[$qsCardId]->welcomeOffer,
                    'details' => $cardsBlog[$qsCardId]->Description,
                    'ratesFees' => $ratesFees,
                    'annualFees' => $cardsBlog[$qsCardId]->AnnualFees,
                    'creditScoreNeeded' => $cardsBlog[$qsCardId]->CreditScoreNeeded,
                    'applyLink' => StringHandler::replaceVarInLink($cardsBlog[$qsCardId]->applyLink, $linkArg),
                    'applyText' => $cardsBlog[$qsCardId]->applyText,
                ]);
            }
        }

        return [
            'title' => 'Top Offers for You',
            'cards' => $offer,
            'more' => [
                'text' => 'See All Cards',
                'link' => 'https://awardwallet.com/blog/credit-cards',
            ],
        ];
    }

    public function getRecommendedOffer(): array
    {
        // temporary data for creating a layout
        // TODO: remove after release
        if (BlogPost::IS_DEV_TEST) {
            return json_decode(file_get_contents(__DIR__ . '/../../../../../tests/_data/Blog/recommended.json'), true);
        }

        $excludeCardsId = $this->getUserExcludeCardsId();

        if (empty($excludeCardsId)) {
            $excludeCardsId = [-1];
        }

        $offers = $this->entityManager->getConnection()->fetchAllAssociative('
                SELECT
                    co.PrimaryPostID, co.SupportingPostID
                FROM CreditCardOffer co
                JOIN CreditCard cc ON (cc.CreditCardID = co.CreditCardID)
                WHERE
                        co.CreditCardID NOT IN (:excludeCardsId)
                    AND co.IsMonetized = 1
                    AND (co.EndDate > NOW() OR co.EndDate IS NULL)
                    AND co.PrimaryPostID IS NOT NULL
                ORDER BY cc.RankIndex ASC, co.SubjectiveValue DESC
            ',
            ['excludeCardsId' => $excludeCardsId],
            ['excludeCardsId' => Connection::PARAM_INT_ARRAY]
        );

        if (empty($offers)) {
            return [];
        }

        $blogPostIds = [];

        foreach ($offers as $offer) {
            $blogPostIds[] = $offer['PrimaryPostID'];

            if (!empty($offer['SupportingPostID'])) {
                $blogPostIds = array_merge(
                    $blogPostIds,
                    StringUtils::getIntArrayFromString($offer['SupportingPostID'])
                );
            }
        }

        $blogPostIds = array_unique($blogPostIds);
        $blogPosts = $this->fetchPostByOptions([BlogPost::OPTION_KEY_POST_ID => $blogPostIds]);

        $list = [];

        foreach ($offers as $offer) {
            $post = $blogPosts[$offer['PrimaryPostID']] ?? null;

            if (null === $post) {
                continue;
            }

            $post = $this->normalizePost($post);

            if (!empty($offer['SupportingPostID'])) {
                $supportingPostId = StringUtils::getIntArrayFromString($offer['SupportingPostID']);

                foreach ($supportingPostId as $supPostId) {
                    $supPost = $blogPosts[$supPostId] ?? null;

                    if (null === $supPost) {
                        continue;
                    }

                    if (!array_key_exists('supporting', $post)) {
                        $post['supporting'] = [];
                    }

                    $post['supporting'][] = [
                        'title' => $supPost->getTitle(),
                        'description' => $supPost->getDescription(),
                        'link' => $supPost->getLink(),
                    ];
                }
            }

            $provider = null;
            $tags = $post['tags'] ?? [];

            foreach ($tags as $tag) {
                if (!empty($tag['meta']['provider'])) {
                    $provider = $tag['meta']['provider'];
                }
            }

            if (!empty($provider)) {
                $accounts = $this->getAccounts();
                $hasAccount = false;

                foreach ($accounts as $account) {
                    if ((int) $provider['id'] === (int) $account['ProviderID']) {
                        $hasAccount = true;
                        $post['account'] = [
                            'logo' => $this->getProviderLogo($account['ProviderCode']),
                            'owner' => $this->getOwner($account['AccountOwner']),
                            'balance' => $this->getAccountBalance($account),
                            'expirationState' => $account['_expiringState'] ?? null,
                            'expirationDate' => $account['_expiringDate'] ?? null,
                            'expirationDateShort' => $account['_expiringDateShort'] ?? null,
                            'link' => $this->router->generate('aw_account_list', ['account' => $account['ID']]),
                        ];

                        break;
                    }
                }

                if (!$hasAccount) {
                    $provider = $this->getProvider((int) $provider['id']);
                    $post['provider'] = [
                        'logo' => $this->getProviderLogo($provider['Code']),
                        'displayName' => $provider['DisplayName'],
                        'connectLink' => $this->router->generate(
                            'aw_account_add',
                            ['providerId' => $provider['ProviderID']]
                        ),
                    ];
                }
            }

            $list[] = $post;
        }

        return [
            'title' => 'Recommended Offer',
            'posts' => $list,
        ];
    }

    public function getUserData(): ?array
    {
        $user = $this->getUser();

        if (null === $user) {
            return null;
        }

        return [
            'fullName' => $user->getFullName(),
            'avatar' => [
                'image' => $this->userAvatar->getUserUrl($user),
                'link' => $this->router->generate('aw_profile_personal'),
            ],
            'post' => [
                'read' => [
                    'title' => BlogUserPost::TYPES[BlogUserPost::TYPE_MARK_READ],
                    'link' => $this->router->generate('aw_blog_learn_read'),
                    'count' => count($this->userPost->get(BlogUserPost::TYPE_MARK_READ)),
                ],
                'favorite' => [
                    'title' => BlogUserPost::TYPES[BlogUserPost::TYPE_FAVORITE],
                    'link' => $this->router->generate('aw_blog_learn_favorite'),
                    'count' => count($this->userPost->get(BlogUserPost::TYPE_FAVORITE)),
                ],
            ],
        ];
    }

    public function fetchByTravels(): array
    {
        $flights = $this->learnTimeline->fetchFlights($this->getUser());

        if (empty($flights)) {
            return [];
        }

        /** @var PostItem[] $posts */
        $posts = $this->fetchPostByOptions([
            BlogPost::OPTION_KEY_AFTER_DATE => (new \DateTime())->sub(new \DateInterval('P3M')),
        ]);

        $posts = $this->learnTimeline->assignPostWithFlights($posts, $flights);

        $postsWithFlights = [];

        foreach ($posts as $post) {
            if (null === $post->getMeta(Constants::META_FLIGHT_ROUTE_KEY)) {
                continue;
            }

            $postsWithFlights[] = $this->normalizePost($post);
        }

        return $postsWithFlights;
    }

    public function fetchBySubAccount($limit = 3, $limitSubAccountsByPost = 4): array
    {
        $subAccountMatches = [];
        $blogPostIds = [];

        foreach ($this->getAccounts() as $account) {
            if (empty($account['SubAccountsArray'])) {
                continue;
            }

            foreach ($account['SubAccountsArray'] as $subAccount) {
                if (!empty($subAccount['Blogs']['BlogIds'])) {
                    $match = $subAccount;
                    $match['account'] = $account;
                    $match['blogPostId'] = array_column($subAccount['Blogs']['BlogIds'], 'id');

                    $blogPostIds = array_merge($blogPostIds, $match['blogPostId']);

                    $subAccountMatches[] = $match;
                }
            }
        }

        foreach ($subAccountMatches as &$subAccountMatch) {
            $subAccountMatch['_expiration'] = time() + (365 * 86400);

            if ($subAccountMatch['ExpirationKnown'] && !empty($subAccountMatch['ExpirationDateTs'])) {
                $subAccountMatch['_expiration'] = $subAccountMatch['ExpirationDateTs'];
            }
        }

        usort($subAccountMatches, static function ($a, $b) {
            if ($a['_expiration'] === $b['_expiration']) {
                $balanceA = (int) str_replace('$', '', $a['Balance'] ?? $a['account']['BalanceRaw'] ?? 0);
                $balanceB = (int) str_replace('$', '', $b['Balance'] ?? $b['account']['BalanceRaw'] ?? 0);

                return $balanceB <=> $balanceA;
            }

            return $a['_expiration'] <=> $b['_expiration'];
        });

        $blogPosts = $this->fetchPostByOptions([BlogPost::OPTION_KEY_POST_ID => $blogPostIds]);
        $list = [];

        foreach ($blogPosts as &$blogPost) {
            $post = $this->normalizePost($blogPost);

            foreach ($subAccountMatches as $subAccount) {
                if (!in_array($post['id'], $subAccount['blogPostId'])) {
                    continue;
                }

                if (!isset($post['meta']) || !array_key_exists(self::SLUG_SUBACCOUNT, $post['meta'])) {
                    $post['meta'][self::SLUG_SUBACCOUNT] = [];
                }

                if ($subAccount['ExpirationKnown'] && !empty($subAccount['ExpirationDate'])) {
                    $expiration = $this->getExpirationDate(
                        (int) $subAccount['ExpirationDateTs'],
                        $subAccount['ExpirationDate'],
                        true
                    );

                    if (null !== $expiration) {
                        $subAccount['_expiringState'] = $expiration['state'];
                        $subAccount['_expiringDate'] = $expiration['expiration'];
                        $subAccount['_expiringDateShort'] = $expiration['expirationShort'];
                    }
                }

                if (isset($post['meta'][self::SLUG_SUBACCOUNT]) && count($post['meta'][self::SLUG_SUBACCOUNT]) >= $limitSubAccountsByPost) {
                    continue;
                }

                $post['meta'][self::SLUG_SUBACCOUNT][] = [
                    'displayName' => strip_tags($subAccount['DisplayName']),
                    'balance' => $subAccount['Balance'],
                    'owner' => $this->getOwner($subAccount['account']['AccountOwner']),
                    'expirationState' => $subAccount['_expiringState'] ?? null,
                    'expirationDate' => $subAccount['_expiringDate'] ?? null,
                    'expirationDateShort' => $subAccount['_expiringDateShort'] ?? null,
                    'link' => $this->router->generate('aw_account_list', [
                        'account' => $subAccount['account']['ID'],
                    ]),
                    'logo' => $this->getProviderLogo($subAccount['account']['ProviderCode']),
                ];
            }

            if (!empty($post['meta'][self::SLUG_SUBACCOUNT])) {
                $list[] = $post;
            }

            if (count($list) >= $limit) {
                break;
            }
        }

        return [
            'title' => 'Learn how to earn more miles through credit card offers',
            'more' => [
                'text' => $this->translator->trans('see-all-posts'),
                'link' => $this->router->generate('aw_blog_learn_more_offers'),
            ],
            'posts' => $list,
        ];
    }

    public function getCategoryPost(string $category, int $page): array
    {
        if (null === ($categoryId = $this->getCategoryIdBySlug($category))) {
            return [];
        }

        return [
            'title' => Constants::CATEGORY_NAMES[$categoryId],
            'more' => [
                'text' => $this->translator->trans('see-all-posts'),
                'link' => $this->router->generate('aw_blog_learn_category', ['category' => $category]),
            ],
            'posts' => $this->getPostByCategory($category, [
                BlogPost::OPTION_KEY_PAGE => $page,
                BlogPost::OPTION_KEY_LIMIT => self::COUNT_POST,
            ]),
            'nextPage' => $this->router->generate('aw_blog_learn_category_page', [
                'category' => $category,
                'page' => 1 + $page,
            ]),
        ];
    }

    public function getPostByCategory(string $category, array $options = []): array
    {
        $options = array_merge($options, [
            BlogPost::OPTION_KEY_CATEGORY_ID => $this->getCategoryIdBySlug($category),
        ]);

        $posts = $this->fetchPostByOptions($options);

        return $this->normalizeAllPost($posts);
    }

    public function getPostByProvider(string $providerCode): array
    {
        $provider = null;
        $providers = $this->fetchProvidersByAccounts();

        foreach ($providers as $item) {
            if ($providerCode === $item['Code']) {
                $provider = $item;

                break;
            }
        }

        if (null === $provider) {
            return [];
        }

        $postIds = StringUtils::getIntArrayFromString($provider['BlogPostID'] . ',' . $provider['BlogIdsPromos']);
        $posts = $this->fetchPostByOptions([BlogPost::OPTION_KEY_POST_ID => $postIds]);

        return [
            'title' => $provider['DisplayName'],
            'posts' => $this->normalizeAllPost($posts),
        ];
    }

    public function getLatestPosts(int $page = 1): array
    {
        $posts = $this->fetchPostByOptions([
            BlogPost::OPTION_KEY_PAGE => $page,
            BlogPost::OPTION_KEY_LIMIT => self::COUNT_POST,
        ]);

        return [
            'title' => $this->translator->trans('latest-posts'),
            'more' => [
                'text' => $this->translator->trans('see-all-posts'),
                'link' => $this->router->generate('aw_blog_learn_latestposts'),
            ],
            'posts' => $this->normalizeAllPost($posts),
            'nextPage' => $this->router->generate('aw_blog_learn_latestposts_page', [
                BlogPost::OPTION_KEY_PAGE => 1 + $page,
            ]),
        ];
    }

    public function getUserPosts(int $type): array
    {
        $ids = $this->userPost->get($type);

        if (empty($ids)) {
            return [];
        }

        $learnedPostId = BlogUserPost::TYPE_FAVORITE === $type
            ? $this->userPost->get(BlogUserPost::TYPE_MARK_READ)
            : $ids;

        $posts = $this->fetchPostByOptions([
            BlogPost::OPTION_KEY_POST_ID => $ids,
            BlogPost::OPTION_KEY_IGNORE_POST_ID => true,
        ]);

        if (empty($posts)) {
            return [];
        }

        $posts = $this->normalizeAllPost($posts);

        foreach ($posts as &$post) {
            if (in_array($post['id'], $learnedPostId)) {
                $post['isLearned'] = true;
            }
        }

        return [
            'title' => BlogUserPost::TYPES[$type] ?? '',
            'posts' => $posts,
        ];
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('discover-world-with-aw'))->setDesc('Discover World with AwardWallet Learn'),
            (new Message('recommended'))->setDesc('Recommended'),
            (new Message('reviewed-by'))->setDesc('Reviewed By'),
            (new Message('latest-news'))->setDesc('Latest News'),
            (new Message('latest-posts'))->setDesc('Latest Posts'),
            (new Message('see-all-posts'))->setDesc('See All Posts'),
        ];
    }

    private function getUser(): ?Usr
    {
        return $this->tokenStorage->getUser();
    }

    private function normalizeAllPost(array $posts): array
    {
        $result = [];

        foreach ($posts as $post) {
            $result[] = $this->normalizePost($post);
        }

        return $result;
    }

    private function normalizePost(PostItem $post): array
    {
        $pubDate = $this->localizeService->formatDateTime($post->getPubDate(),
            LocalizeService::FORMAT_MEDIUM,
            null
        );

        $item = [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'thumbnail' => $post->getThumbnail(),
            'pubDate' => $pubDate,
            'dateAgo' => $this->dateAgo($post, $pubDate),
            'link' => $post->getLink(),
            'commentsCount' => $post->getCommentsCount(),
            'authors' => $post->getAuthors(),
            'reviewed' => $post->getReviewed(),
            'meta' => [],
            'isFavorite' => $this->userPost->has($post->getId(), BlogUserPost::TYPE_FAVORITE),
        ];

        if (null !== ($flight = $post->getMeta(Constants::META_FLIGHT_ROUTE_KEY))) {
            $item['meta'][Constants::META_FLIGHT_ROUTE_KEY] = $flight;
        }

        if (empty($item['meta'])) {
            unset($item['meta']);
        }

        return $item;
    }

    private function dateAgo(PostItem $post, string $pubDate): string
    {
        $now = (new \DateTime());
        $difference = $now->diff($post->getPubDate());

        //        if ($difference->days > 100) {
        //            return $pubDate;
        //        }

        return $this->intervalFormatter->longFormatViaDateTimes($now, $post->getPubDate());
    }

    private function dateShortExpiration(\DateTime $expirationDate): ?string
    {
        $date = $this->intervalFormatter->shortFormatViaDates(new \DateTime(), $expirationDate, true, true, 'en');

        return str_replace(
            ['months', 'month', 'days', 'day', 'years', 'year'],
            ['mo.', 'mo.', 'd.', 'd.', 'y.', 'y.'],
            $date
        );
    }

    private function fetchProvidersByAccounts(): array
    {
        if (!empty($this->accountProviders)) {
            return $this->accountProviders;
        }

        !empty($this->accounts) ?: $this->getAccounts();

        $providerIds = array_column($this->accounts, 'ProviderID');
        $providerIds = array_filter(array_unique($providerIds));

        $blogIdFields = empty($providerIds)
            ? []
            : $this->entityManager->getConnection()->fetchAllAssociative('
                SELECT ProviderID, Code, DisplayName, BlogTagsID, BlogPostID, BlogIdsMilesPurchase, BlogIdsMilesTransfers, BlogIdsPromos, BlogIdsMileExpiration, BalanceFormat, Currency
                FROM Provider
                WHERE ProviderID IN (?)
              ',
                [$providerIds],
                [Connection::PARAM_INT_ARRAY]
            );
        $blogIdFields = array_column($blogIdFields, null, 'ProviderID');

        $providers = [];
        $sortRank = 0;

        foreach ($this->accounts as $account) {
            $providerId = $account['ProviderID'] ?? 0;
            $isEmptyDisplayName = empty($account['DisplayName']);

            if (empty($providerId)
                || $isEmptyDisplayName) {
                continue;
            }

            if (!array_key_exists($providerId, $providers)) {
                $providers[$providerId] = array_key_exists($providerId, $blogIdFields)
                    ? $blogIdFields[$providerId]
                    : [];
                $providers[$providerId]['sortRank'] = ++$sortRank;
                $providers[$providerId]['accounts'] = [];
                $providers[$providerId]['posts'] = [];
            }

            $providers[$providerId]['accounts'][] = $account;
        }

        $this->accountProviders = $providers;

        return $this->accountProviders;
    }

    private function normalizeMenuItems($items): array
    {
        $result = [];

        if (empty($items)) {
            return $result;
        }

        foreach ($items as $item) {
            if (in_array($item->title, ['Team', 'Advertiser Disclosure']) || 11 === (int) $item->object_id) {
                continue;
            }

            if (false !== strpos($item->title, '$')) {
                $item->title = str_replace(['$CURRENT_MONTH$'], date('F'), $item->title);
            }

            $data = [
                'title' => $item->title,
            ];

            if (!empty($item->url) && 0 !== strpos($item->url, '#')) {
                $data['url'] = $item->url;
            }

            if (!empty($item->code)) {
                $data['code'] = $this->getProviderLogo($item->code);
            }

            $classes = array_filter($item->classes);

            if (!empty($classes)) {
                $data['classes'] = $classes;
            }

            if (property_exists($item, 'wpse_children')) {
                $data['items'] = $this->normalizeMenuItems($item->wpse_children);
            }

            $result[] = $data;
        }

        return $result;
    }

    private function getOwner($id): ?string
    {
        if (empty($this->agents)) {
            $this->agents = array_column(
                $this->accountListManager->getAgentsInfo($this->tokenStorage->getUser()),
                null,
                'ID'
            );
        }

        return $this->agents[$id]['name'] ?? null;
    }

    private function getPopularity(): array
    {
        $providers = $this->fetchProvidersByAccounts();
        $popular = [];

        foreach ($providers as $provider) {
            $postIds = StringUtils::getIntArrayFromString($provider['BlogPostID'] . ',' . $provider['BlogIdsPromos']);

            if (empty($postIds)) {
                continue;
            }

            $popular[] = [
                'id' => $provider['ProviderID'],
                'code' => $provider['Code'],
                'displayName' => $provider['DisplayName'],
            ];

            if (3 === count($popular)) {
                break;
            }
        }

        return $popular;
    }

    private function getCurrencyList(): array
    {
        if (!empty($this->currencyLocalizedList)) {
            return $this->currencyLocalizedList;
        }

        return $this->currencyLocalizedList = $this->entityManager->getRepository(Currency::class)->getAllPluralLocalizedList($this->translator);
    }

    private function getCategoryIdBySlug(string $category): ?int
    {
        return array_flip(Constants::CATEGORY_SLUG)[$category] ?? null;
    }

    private function getProviderLogo(string $code): string
    {
        return 'images/' . $code . '.png';
    }

    private function getProvider(int $providerId): ?array
    {
        $cacheKey = 'learn_provider_' . $providerId;
        $provider = $this->cache->get($cacheKey);

        if (!$provider) {
            $provider = $this->entityManager->getConnection()->fetchAssociative('SELECT ProviderID, Code, DisplayName FROM Provider WHERE ProviderID = ?',
                [$providerId],
                [\PDO::PARAM_INT]
            );

            $this->cache->set($cacheKey, $provider);
        }

        return $provider ?? null;
    }

    private function getAccountBalance(array $account): string
    {
        $providerId = (int) ($account['ProviderID'] ?? 0);
        $providers = $this->fetchProvidersByAccounts();
        $balance = $account['BalanceRaw'] ?? null;
        $currencyId = $providers[$providerId]['Currency'] ?? null;
        $currency = null === $balance || null === $currencyId || -1 === $balance
            ? ''
            : ' ' . ($balance > 1 ? $this->getCurrencyList()[$currencyId][2] : $this->getCurrencyList()[$currencyId][1]);

        return $account['Balance'] . $currency;
    }

    private function getExpirationDate(int $timeStamp, string $expirationDate, bool $isSkipDayLimit = false): ?array
    {
        $dayDiff = round((time() - $timeStamp) / 86400);

        if (!$isSkipDayLimit && ($dayDiff > 30 || $dayDiff < -120)) {
            return null;
        }

        if ($dayDiff >= 0) {
            return [
                'state' => 'expired',
                'expiration' => 'Expired ' . $expirationDate,
                'expirationShort' => $this->dateShortExpiration(new \DateTime('@' . $timeStamp)),
            ];
        }

        return [
            'state' => $dayDiff <= -90 ? 'far' : 'soon',
            'expiration' => 'Expiring ' . $expirationDate,
            'expirationShort' => $this->dateShortExpiration(new \DateTime('@' . $timeStamp)),
        ];
    }

    private function getUserExcludeCardsId(): ?array
    {
        $user = $this->getUser();

        if (null === $user) {
            return null;
        }

        $existsCards = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                cc.CreditCardID, cc.ExcludeCardsId,
                ucc.IsClosed
            FROM CreditCard cc
            JOIN UserCreditCard ucc ON (ucc.CreditCardID = cc.CreditCardID)
            WHERE
                    ucc.UserID = :userId
                AND ucc.IsClosed = 0
        ',
            ['userId' => $user->getId()],
            ['userId' => \PDO::PARAM_INT]
        );

        $excludeCardsId = [];

        foreach ($existsCards as $existsCard) {
            $excludeCardsId[] = $existsCard['CreditCardID'];

            if (0 === (int) $existsCard['IsClosed'] && !empty($existsCard['ExcludeCardsId'])) {
                $excludeCardsId = array_merge(
                    $excludeCardsId,
                    StringUtils::getIntArrayFromString($existsCard['ExcludeCardsId'])
                );
            }
        }

        return array_values(array_unique($excludeCardsId));
    }

    private function fetchPostByOptions(array $options): array
    {
        if (!array_key_exists(BlogPost::OPTION_KEY_IGNORE_POST_ID, $options)
            || false === $options[BlogPost::OPTION_KEY_IGNORE_POST_ID]
        ) {
            $readsPosts = $this->userPost->get(BlogUserPost::TYPE_MARK_READ);

            if (!empty($readsPosts)) {
                $options[BlogPost::OPTION_KEY_IGNORE_POST_ID] = $readsPosts;
            }
        }

        return $this->blogPost->fetchPostByOptions($options) ?? [];
    }
}
