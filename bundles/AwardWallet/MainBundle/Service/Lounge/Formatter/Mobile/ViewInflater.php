<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\AirportTerminalMatcher\Matcher;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\DragonPass;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\LoungeKey;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevel\PriorityPass;
use AwardWallet\MainBundle\Service\Lounge\Category\AccessLevelInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\DTO\Icon;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\Builder;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidTimezone;
use Spatie\OpeningHours\Exceptions\MaximumLimitExceeded;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class ViewInflater
{
    public const STAGE_DEP = 'dep';
    public const STAGE_ARR = 'arr';

    private EntityManagerInterface $em;

    private Connection $conn;

    private TranslatorInterface $translator;

    private Finder $finder;

    private Matcher $matcher;

    private OpeningHoursScheduleBuilder $openingHoursScheduleBuilder;

    private ApiVersioningService $apiVersioningService;

    private Logger $logger;

    /**
     * @var CategoryInterface[]|AccessLevelInterface[]
     */
    private iterable $loungeAccessCategories;

    /**
     * @var CategoryInterface[]|ReviewInterface[]
     */
    private iterable $loungeReviewCategories;

    private LazyVal $blogPostList;

    private array $cards = [];

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        Finder $finder,
        Matcher $matcher,
        OpeningHoursScheduleBuilder $openingHoursScheduleBuilder,
        ApiVersioningService $apiVersioningService,
        Logger $logger,
        iterable $loungeAccessCategories,
        iterable $loungeReviewCategories,
        BlogPostInterface $blogPost,
        SafeExecutorFactory $safeExecutorFactory
    ) {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->translator = $translator;
        $this->finder = $finder;
        $this->matcher = $matcher;
        $this->openingHoursScheduleBuilder = $openingHoursScheduleBuilder;
        $this->apiVersioningService = $apiVersioningService;
        $this->logger = $logger;
        $this->loungeAccessCategories = $loungeAccessCategories;
        $this->loungeReviewCategories = it($loungeReviewCategories)
            ->usort(fn (ReviewInterface $a, ReviewInterface $b) => $a::getPriority() <=> $b::getPriority())
            ->toArray();
        $this->blogPostList = lazy(function () use ($blogPost, $safeExecutorFactory) {
            return $safeExecutorFactory->make(
                function () use ($blogPost) {
                    return $blogPost->fetchPostById(
                        it($this->loungeReviewCategories)
                            ->map(fn (ReviewInterface $matcher) => $matcher::getBlogPostId())
                            ->sort()
                            ->unique()
                            ->joinToString(',')
                    ) ?? [];
                }
            )->runOrValue([]);
        });
    }

    public function listCards(Usr $user): \JsonSerializable
    {
        $autoDetectFeature = $this->apiVersioningService->supports(MobileVersions::LOUNGE_AUTO_DETECT_CARDS);
        $autoDetectEnabled = $autoDetectFeature && $user->isAutoDetectLoungeCards();
        $userCards = $this->userHaveCards($user, !$autoDetectEnabled);
        $detectCards = $this->detectUserHaveCards($user);
        $cards = [];

        $cards[] = new CardView(
            PriorityPass::getCardId(),
            PriorityPass::getCardName(),
            PriorityPass::getCardIcon($this->apiVersioningService)->getPath(),
            isset($userCards[PriorityPass::getCardId()]),
            $autoDetectFeature ? isset($detectCards[PriorityPass::getCardId()]) : null
        );
        $cards[] = new CardView(
            DragonPass::getCardId(),
            DragonPass::getCardName(),
            DragonPass::getCardIcon($this->apiVersioningService)->getPath(),
            isset($userCards[DragonPass::getCardId()]),
            null
        );
        $cards[] = new CardView(
            LoungeKey::getCardId(),
            LoungeKey::getCardName(),
            LoungeKey::getCardIcon($this->apiVersioningService)->getPath(),
            isset($userCards[LoungeKey::getCardId()]),
            null
        );

        $cards = it($cards)
            ->chain(
                array_map(function (CreditCard $creditCard) use ($userCards, $autoDetectFeature, $detectCards) {
                    return new CardView(
                        $id = "cc{$creditCard->getId()}",
                        $creditCard->getName(),
                        $creditCard->getPicturePath(),
                        isset($userCards[$id]),
                        $autoDetectFeature ? isset($detectCards[$id]) : null
                    );
                }, $this->getAvailableCreditCards())
            )
            ->toArray();

        return new SelectCardsView(
            $this->translator->trans(/** @Desc("Please select which cards you have so that we can better organize the results for you:") */ 'lounge.select-cards.desc', [], 'trips'),
            $cards,
            $autoDetectFeature ? new AutoDetectCardsView(
                $autoDetectEnabled,
                $this->translator->trans(/** @Desc("Let AwardWallet choose my cards based on the accounts in my AwardWallet profile.") */ 'lounge.auto-detect-cards', [], 'trips')
            ) : null
        );
    }

    /**
     * @return \JsonSerializable|ArrayBlockView
     */
    public function listLounges(
        Usr $user,
        Tripsegment $tripSegment,
        ?string $stage,
        ?string $arrivalTerminal = null,
        ?string $departureTerminal = null,
        ?string $time = 'now',
        bool $withDetails = false
    ): \JsonSerializable {
        if ($stage === self::STAGE_DEP) {
            $airportCode = $tripSegment->getDepcode();
            $departureTerminal = $this->prepareTerminal($tripSegment->getDepartureTerminal(), $airportCode);
            $arrivalTerminal = null;
            $title = sprintf('%s (%s)', $airportCode, $tripSegment->getDepAirportName(false));
        } else {
            $airportCode = $tripSegment->getArrcode();
            $title = sprintf('%s (%s)', $airportCode, $tripSegment->getArrAirportName(false));

            if ($stage === self::STAGE_ARR) {
                $arrivalTerminal = $this->prepareTerminal($tripSegment->getArrivalTerminal(), $airportCode);
                $departureTerminal = null;
            } else {
                $arrivalTerminal = $this->prepareTerminal($arrivalTerminal, $airportCode);
                $departureTerminal = $this->prepareTerminal($departureTerminal, $airportCode);
            }
        }

        if (empty($airportCode)) {
            return new ArrayBlockView([]);
        }

        $userCards = $this->userHaveCards($user);
        $blocks = [
            new AirportListItemView($title),
        ];
        $nullableOpeningHours = $this->apiVersioningService->supports(MobileVersions::LOUNGE_NULLABLE_OPENED);
        $extendedIconInfo = $this->apiVersioningService->supports(MobileVersions::LOUNGE_PRIORITY_PASS_RESTAURANT);

        $sorted = it($this->finder->getLounges($airportCode))
            ->map(function (Lounge $lounge) use ($userCards, $airportCode, $extendedIconInfo) {
                $access = $this->getLoungeAccess($lounge, $userCards, $extendedIconInfo);
                $openingHours = $lounge->getOpeningHoursFinal();
                $hours = $openingHours instanceof StructuredOpeningHours ? $this->buildStructuredOpeningHours($openingHours) : null;

                return [
                    'lounge' => $lounge,
                    'terminal' => $this->prepareTerminal($lounge->getTerminal(), $airportCode),
                    'access' => $access,
                    'hours' => $hours,
                ];
            })
            ->usort(function (array $a, array $b) {
                /** @var Builder $hoursA */
                $hoursA = $a['hours'];
                /** @var Builder $hoursB */
                $hoursB = $b['hours'];

                return [
                    $a['terminal'],
                    $b['access']['isGranted'],
                    !is_null($hoursB) && $hoursB->opened(),
                    !is_null($hoursB) && $hoursB->mayBeOpened(),
                ] <=> [
                    $b['terminal'],
                    $a['access']['isGranted'],
                    !is_null($hoursA) && $hoursA->opened(),
                    !is_null($hoursA) && $hoursA->mayBeOpened(),
                ];
            })
            ->groupAdjacentBy(function (array $a, array $b) {
                return $a['terminal'] <=> $b['terminal'];
            })
            ->reindexByPropertyPath('[0][terminal]')
            ->uasort(function (array $a, array $b) use ($arrivalTerminal, $departureTerminal) {
                return [
                    $b[0]['terminal'] === $departureTerminal,
                    $b[0]['terminal'] === $arrivalTerminal,
                ] <=> [
                    $a[0]['terminal'] === $departureTerminal,
                    $a[0]['terminal'] === $arrivalTerminal,
                ];
            })
            ->toArrayWithKeys();

        foreach ($sorted as $terminal => $lounges) {
            $blocks[] = new TerminalListItemView(
                $this->translator->trans(/** @Desc("Terminal %name%") */ 'terminal.title', [
                    '%name%' => $terminal,
                ], 'trips')
            );

            foreach ($lounges as $loungeData) {
                /** @var Lounge $lounge */
                $lounge = $loungeData['lounge'];
                $blogLinks = null;

                if ($this->apiVersioningService->supports(MobileVersions::LOUNGE_BLOG_LINKS)) {
                    $blogLinks = $this->getBlogLinks($lounge, $user);
                }

                if ($this->apiVersioningService->supports(MobileVersions::LOUNGE_OPENING_HOURS)) {
                    /** @var Builder $hours */
                    $hours = $loungeData['hours'];
                    $nextEvent = null;

                    if (is_null($hours)) {
                        $opened = $nullableOpeningHours ? null : false;
                    } else {
                        $oh = $hours->getOpeningHours();
                        $now = new \DateTime($time, $hours->getTimeZone());

                        if ($nullableOpeningHours) {
                            $opened = $hours->opened($now);
                        } else {
                            $opened = $hours->mayBeOpened($now);
                        }
                        $dayData = $oh->forDate($now)->getData();

                        if ($opened) {
                            $range = $oh->currentOpenRange($now);
                            $rangeData = $range ? $range->getData() : null;

                            if ($range && (!is_array($dayData) || !in_array($dayData['code'] ?? null, [Builder::CODE_HOURS_VARY]))) {
                                if (!is_array($rangeData) || !in_array($rangeData['code'] ?? null, [Builder::CODE_RANGE_UNKNOWN_END, Builder::CODE_RANGE_UNKNOWN_BOTH, Builder::CODE_UNKNOWN, Builder::CODE_MERGED])) {
                                    $end = $oh->nextClose($now);
                                    $diff = $end->getTimestamp() - $now->getTimestamp();

                                    if ($diff > 0 && $diff <= (3600 * 6)) {
                                        $nextOpen = $oh->nextOpen($now);

                                        if ($nextOpen->getTimestamp() - $end->getTimestamp() > 60) {
                                            $nextEvent = $end;
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($nullableOpeningHours && $hours->mayBeOpened($now)) {
                                $opened = null;
                            }

                            if (is_bool($opened)) {
                                try {
                                    $nextOpen = $oh->nextOpen($now);
                                    $range = $oh->currentOpenRange($nextOpen);
                                    $rangeData = $range ? $range->getData() : null;
                                    $dayData = $oh->forDate($nextOpen)->getData();

                                    if ($range && (!is_array($dayData) || !in_array($dayData['code'] ?? null, [Builder::CODE_HOURS_VARY]))) {
                                        if (!is_array($rangeData) || !in_array($rangeData['code'] ?? null, [Builder::CODE_RANGE_UNKNOWN_START, Builder::CODE_RANGE_UNKNOWN_BOTH, Builder::CODE_UNKNOWN, Builder::CODE_MERGED])) {
                                            $diff = $nextOpen->getTimestamp() - $now->getTimestamp();

                                            if ($diff > 0 && $diff <= (3600 * 6)) {
                                                $nextEvent = $nextOpen;
                                            }
                                        }
                                    }
                                } catch (MaximumLimitExceeded $e) {
                                }
                            }
                        }
                    }

                    $blocks[] = new LoungeListItemView(
                        $lounge,
                        array_map(function (AccessIconView $view) {
                            $clone = clone $view;
                            $clone->description = null;

                            return $clone;
                        }, $loungeData['access']['views']),
                        is_null($time) ? null : $opened,
                        is_null($time) ? null : ($nextEvent ?? null),
                        $withDetails ? $this->details($user, $lounge, $loungeData['access']) : null,
                        $blogLinks
                    );
                } else {
                    $blocks[] = new LoungeListItemView(
                        $lounge,
                        array_map(function (AccessIconView $view) {
                            $clone = clone $view;
                            $clone->description = null;

                            return $clone;
                        }, $loungeData['access']['views']),
                        is_null($time) ? null : false,
                        null,
                        null,
                        $blogLinks
                    );
                }
            }
        }

        return new ArrayBlockView($blocks);
    }

    public function details(Usr $user, Lounge $lounge, ?array $access = null): \JsonSerializable
    {
        $gate = $lounge->getGate();
        $gate2 = $lounge->getGate2();
        $existsGate = !empty($gate) || !empty($gate2);
        $blocks = [
            new LoungeTitleView($lounge->getName()),
            new AirportDetailsView($this->translator->trans(/** @Desc("Airport") */ 'airport', [], 'trips'), $lounge->getAirportCode()),
            new TerminalDetailsView(new TerminalHeaderView(
                $this->translator->trans(/** @Desc("Terminal") */ 'terminal', [], 'trips'),
                $lounge->getTerminal() ?? Matcher::MAIN_TERMINAL,
                $existsGate ? $this->translator->trans('itineraries.trip.air.gate', [], 'trips') : null,
                $existsGate ? array_filter([$gate, $gate2]) : null
            ), $lounge->getFinalLocation()),
        ];

        $addOldOpeningHours = function (Lounge $lounge) use (&$blocks) {
            if (!empty($openingHours = $lounge->getOpeningHours()) && $openingHours instanceof RawOpeningHours) {
                $blocks[] = new OpeningHoursDetailsView(
                    $this->translator->trans('lounge.opening-hours', [], 'trips'),
                    $openingHours->getRaw()
                );
            }
        };

        $openingHours = $lounge->getOpeningHoursFinal();
        $extendedIconInfo = $this->apiVersioningService->supports(MobileVersions::LOUNGE_PRIORITY_PASS_RESTAURANT);

        if (
            $this->apiVersioningService->supports(MobileVersions::LOUNGE_OPENING_HOURS)
            && $openingHours instanceof StructuredOpeningHours
        ) {
            $hours = $this->buildStructuredOpeningHours($openingHours);

            if ($hours) {
                $blocks[] = new OpeningHoursDetailsView(
                    $this->translator->trans('lounge.opening-hours', [], 'trips'),
                    null,
                    $this->openingHoursScheduleBuilder->build($hours, $user->getLocale())
                );
            } else {
                $addOldOpeningHours($lounge);
            }
        } else {
            $addOldOpeningHours($lounge);
        }

        if (is_null($access)) {
            $access = $this->getLoungeAccess($lounge, $this->userHaveCards($user), $extendedIconInfo);
        }

        if (count($access['views']) > 0) {
            $blocks[] = new AccessDetailsView(
                $this->translator->trans(/** @Desc("Access") */ 'lounge.access', [], 'trips'),
                new AccessDescriptionView($access['views'])
            );
        }

        if ($this->apiVersioningService->supports(MobileVersions::LOUNGE_BLOG_LINKS)) {
            $blogLinks = $this->getBlogLinks($lounge, $user);

            if (!is_null($blogLinks)) {
                $blocks[] = new BlogLinksView($blogLinks);
            }
        }

        return new ArrayBlockView($blocks);
    }

    private function buildStructuredOpeningHours(StructuredOpeningHours $openingHours): ?Builder
    {
        try {
            return $openingHours->build();
        } catch (Exception $e) {
            $this->logger->warning(sprintf('Error while building structured opening hours: %s', $e->getMessage()), [
                'tz' => $openingHours->getTz(),
                'openingHours' => $openingHours->getData(),
            ]);
        } catch (InvalidTimezone $e) {
            $this->logger->error(sprintf('Invalid timezone: %s', $e->getMessage()));
        }

        return null;
    }

    private function userHaveCards(Usr $user, bool $enableAutoDetect = true): array
    {
        $cards = [];

        if ($user->isHavePriorityPassCard()) {
            $cards[PriorityPass::getCardId()] = true;
        }

        if ($user->isHaveDragonPassCard()) {
            $cards[DragonPass::getCardId()] = true;
        }

        if ($user->isHaveLoungeKeyCard()) {
            $cards[LoungeKey::getCardId()] = true;
        }

        foreach ($this->conn->fetchFirstColumn("SELECT CreditCardID FROM UserCard WHERE UserID = ?", [$user->getId()]) as $cardId) {
            $cards["cc{$cardId}"] = true;
        }

        $autoDetect =
            is_null($user->getAvailableCardsUpdateDate())
            || (
                $this->apiVersioningService->supports(MobileVersions::LOUNGE_AUTO_DETECT_CARDS)
                && $user->isAutoDetectLoungeCards()
            );

        if ($enableAutoDetect && $autoDetect) {
            $cards = array_merge($cards, $this->detectUserHaveCards($user));
        }

        return $cards;
    }

    private function detectUserHaveCards(Usr $user): array
    {
        $cards = [];

        // priority pass
        $ppCardsCount = $this->conn->fetchOne("
            SELECT
                COUNT(*)
            FROM
                ProviderCoupon
            WHERE
                UserID = :user
                AND UserAgentID IS NULL
                AND TypeID = :type
                AND (
                    ExpirationDate IS NULL
                    OR ExpirationDate > NOW()
                )
        ", [
            'user' => $user->getId(),
            'type' => Providercoupon::TYPE_PRIORITY_PASS,
        ], [
            'user' => \PDO::PARAM_INT,
            'type' => \PDO::PARAM_INT,
        ]);

        if ($ppCardsCount > 0) {
            $cards[PriorityPass::getCardId()] = true;
        }

        // other cards
        foreach ($this->conn->fetchFirstColumn("SELECT CreditCardID FROM UserCreditCard WHERE UserID = ?", [$user->getId()]) as $cardId) {
            $cards["cc{$cardId}"] = true;
        }

        return $cards;
    }

    /**
     * @return CreditCard[]
     */
    private function getAvailableCreditCards(): array
    {
        $ids = $this->conn->fetchFirstColumn("
            SELECT
                cc.CreditCardID
            FROM
                CreditCard cc
                JOIN CreditCardLoungeCategory ccl ON ccl.CreditCardID = cc.CreditCardID
            GROUP BY cc.CreditCardID
        ");

        return $this->em->getRepository(CreditCard::class)->findBy(['id' => $ids], ['sortIndex' => 'ASC']);
    }

    private function getLoungeAccess(Lounge $lounge, array $userCards, bool $extendedIconInfo): array
    {
        $access = [
            'views' => [],
            'isGranted' => false,
        ];

        foreach ($this->loungeAccessCategories as $matcher) {
            if ($matcher->match($lounge)) {
                if ($matcher instanceof PriorityPass) {
                    $isGranted = isset($userCards[PriorityPass::getCardId()]);
                    $access['views'][] = [
                        'sort' => -3,
                        'view' => new AccessIconView(
                            $extendedIconInfo
                                ? PriorityPass::getCardIconByLounge($lounge, $this->apiVersioningService)
                                : PriorityPass::getCardIcon($this->apiVersioningService)->getPath(),
                            $isGranted,
                            PriorityPass::getCardName()
                        ),
                    ];
                    $access['isGranted'] |= $isGranted;
                } elseif ($matcher instanceof DragonPass) {
                    $isGranted = isset($userCards[DragonPass::getCardId()]);
                    $access['views'][] = [
                        'sort' => -2,
                        'view' => new AccessIconView(
                            $extendedIconInfo
                                ? DragonPass::getCardIconByLounge($lounge, $this->apiVersioningService)
                                : DragonPass::getCardIcon($this->apiVersioningService)->getPath(),
                            $isGranted,
                            DragonPass::getCardName()
                        ),
                    ];
                    $access['isGranted'] |= $isGranted;
                } elseif ($matcher instanceof LoungeKey) {
                    $isGranted = isset($userCards[LoungeKey::getCardId()]);
                    $access['views'][] = [
                        'sort' => -1,
                        'view' => new AccessIconView(
                            $extendedIconInfo
                                ? LoungeKey::getCardIconByLounge($lounge, $this->apiVersioningService)
                                : LoungeKey::getCardIcon($this->apiVersioningService)->getPath(),
                            $isGranted,
                            LoungeKey::getCardName()
                        ),
                    ];
                    $access['isGranted'] |= $isGranted;
                } else {
                    /** @var CreditCard $card */
                    foreach ($this->getCreditCards($matcher::getCategoryId()) as $card) {
                        $isGranted = isset($userCards["cc{$card->getId()}"]);
                        $access['views'][] = [
                            'sort' => $card->getSortIndex(),
                            'view' => new AccessIconView(
                                $extendedIconInfo
                                    ? new Icon($card->getPicturePath())
                                    : $card->getPicturePath(),
                                $isGranted,
                                $card->getName()
                            ),
                        ];
                        $access['isGranted'] |= $isGranted;
                    }
                }
            }
        }

        $access['views'] = it($access['views'])
            ->usort(fn (array $a, array $b) => $a['sort'] <=> $b['sort'])
            ->map(fn (array $data) => $data['view'])
            ->toArray();

        return $access;
    }

    /**
     * @return BlogLinkView[]|null
     */
    private function getBlogLinks(Lounge $lounge, Usr $user): ?array
    {
        $links = [];

        foreach ($this->loungeReviewCategories as $matcher) {
            if ($matcher->match($lounge)) {
                $blogPostId = $matcher::getBlogPostId();
                $blogTitle = $this->blogPostList[$blogPostId]['title'] ?? null;
                $blogPostUrl = $this->blogPostList[$blogPostId]['postURL'] ?? null;
                $blogPostImage = $this->blogPostList[$blogPostId]['imageURL'] ?? null;

                if (!$blogTitle || !$blogPostUrl || !$blogPostImage) {
                    continue;
                }

                $parsedUrl = parse_url($blogPostUrl);
                $queryParams = [];
                $queryParams['cid'] = 'lounges';
                $queryParams['mid'] = 'mobile';
                $queryParams['rkbtyn'] = $user->getRefcode() ?? '';

                $blogPostUrl = ($parsedUrl['scheme'] ?? '')
                    . '://'
                    . ($parsedUrl['host'] ?? '')
                    . ($parsedUrl['path'] ?? '')
                    . '?' . http_build_query($queryParams);

                $links[] = new BlogLinkView($blogTitle, $blogPostUrl, $blogPostImage);
            }
        }

        return empty($links) ? null : $links;
    }

    private function prepareTerminal(?string $terminal, string $airportCode): ?string
    {
        if (is_null($terminal)) {
            $terminal = Matcher::MAIN_TERMINAL;
        }

        $terminal = trim($terminal);

        return $this->matcher->match($airportCode, $terminal);
    }

    /**
     * @return CreditCard[]
     */
    private function getCreditCards(int $categoryId): array
    {
        if (isset($this->cards[$categoryId])) {
            return $this->cards[$categoryId];
        }

        $ids = $this->conn->fetchFirstColumn("
            SELECT
                cc.CreditCardID
            FROM
                CreditCard cc
                JOIN CreditCardLoungeCategory ccl ON ccl.CreditCardID = cc.CreditCardID
            WHERE
                LoungeCategoryID = ?
        ", [$categoryId]);

        return $this->cards[$categoryId] = $this->em->getRepository(CreditCard::class)->findBy(['id' => $ids], ['sortIndex' => 'ASC']);
    }
}
