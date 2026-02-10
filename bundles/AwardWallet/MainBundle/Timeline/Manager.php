<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Query\ItineraryChecker;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Globals\ArrayUtils;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlights;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\Formatter\FormatHandlerInterface;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Util\ItineraryUtil;
use AwardWallet\MainBundle\Timeline\Util\TripHelper;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\days;
use function Duration\seconds;

class Manager
{
    /**
     * Segments to prepend before future segments by default.
     */
    public const DEFAULT_PAST_SEGMENTS_AMOUNT = 30;
    public const MAX_FUTURE_SEGMENTS = 700;
    private const START_DATE_OFFSET_DAYS = TRIPS_PAST_DAYS + 2;
    private const HUGE_TIMELINE_START_DATE_OFFSET_DAYS = 1;
    private const START_DATE_CACHE_LIFETIME_DAYS = 14;
    private const HUGE_TIMELINE_SNAP_INTERVAL_DAYS = 2;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Diff\Query
     */
    private $diffQuery;

    /**
     * @var LocalizeService
     */
    private $localizeService;

    /**
     * @var \Memcached
     */
    private $memcached;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ItineraryChecker
     */
    private $itineraryChecker;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var OperatedByResolver
     */
    private $operatedByResolver;

    /**
     * @var AwTwigExtension
     */
    private $twigExt;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var FormatHandlerInterface[]
     */
    private $formatHandlers;
    /**
     * @var TripHelper
     */
    private $tripHelper;

    /** @var MileValueAlternativeFlights */
    private $mileValueAlternativeFlights;
    private ClockInterface $clock;

    private PhoneBookFactory $phoneBookFactory;

    public function __construct(
        EntityManager $em,
        TranslatorInterface $translator,
        Builder $builder,
        Diff\Query $diffQuery,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        LocalizeService $localizeService,
        \Memcached $memcached,
        CacheManager $cacheManager,
        LoggerInterface $logger,
        ItineraryChecker $checker,
        RequestStack $requestStack,
        OperatedByResolver $resolver,
        AwTwigExtension $twigExt,
        TripHelper $tripHelper,
        MileValueAlternativeFlights $mileValueAlternativeFlights,
        ClockInterface $clock,
        PhoneBookFactory $phoneBookFactory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->builder = $builder;
        $this->diffQuery = $diffQuery;
        $this->router = $router;
        $this->localizeService = $localizeService;
        $this->memcached = $memcached;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
        $this->itineraryChecker = $checker;
        $this->requestStack = $requestStack;
        $this->operatedByResolver = $resolver;
        $this->twigExt = $twigExt;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->tripHelper = $tripHelper;
        $this->mileValueAlternativeFlights = $mileValueAlternativeFlights;
        $this->clock = $clock;
        $this->phoneBookFactory = $phoneBookFactory;
    }

    public function queryWithoutMagic(QueryOptions $queryOptions)
    {
        $queryOptions->lock();
        $queryOptions = $this->correctUser($queryOptions);
        $map = $this->getTimelineMap($queryOptions);

        if (!$map) {
            return [];
        }

        $chunkItems = SegmentMapUtils::getChunkByOptions($map, $queryOptions);

        if (!$chunkItems) {
            return [];
        }

        $queryOptions = $queryOptions->setItems($chunkItems);

        return $this->executeQuery($queryOptions);
    }

    public function query(QueryOptions $queryOptions)
    {
        $queryOptions->lock();
        $queryOptions = $this->correctUser($queryOptions);
        $map = $this->getTimelineMap($queryOptions);

        if (!$map) {
            return [];
        }

        $mapLength = \count($map);

        if ($queryOptions->hasFuture() && $queryOptions->getFuture()) {
            if ($queryOptions->hasStartDate() || $queryOptions->hasEndDate()) {
                throw new \InvalidArgumentException("future should not be combined with startDate or endDate");
            }

            $defaultStartDate = $this->getUserStartDate($queryOptions->getUser());
            /** @var \DateTime $endDate */
            $defaultEndDate = clone end($map)['startDate'];

            if ($queryOptions->hasMaxFutureSegments()) {
                $maxFutureSegments = $queryOptions->getMaxFutureSegments();
                $currentDuration = $this->clock->current();
                $currentDateSeconds = $currentDuration
                    ->sub(days(self::HUGE_TIMELINE_START_DATE_OFFSET_DAYS))
                    ->getAsSecondsInt();
                $snapIntervalSeconds = days(self::HUGE_TIMELINE_SNAP_INTERVAL_DAYS)->getAsSecondsInt();
                $currentSnappedDate =
                    seconds(
                        ((int) ($currentDateSeconds / $snapIntervalSeconds))
                        * $snapIntervalSeconds
                    )
                    ->getAsDateTime();
                // As the map contains plan-start\plan-end segments
                $pastItemsLength =
                    ArrayUtils::binarySearchLeftmostRangeWithComparator(
                        $map,
                        fn (array $segment) => $segment['startDate'] <=> $currentSnappedDate
                    )
                    ->getPrefixLength();

                // ($mapLength - $pastItemsLength) is an upper bound estimate for the number of segments in the future,
                // because map-items can include deleted and Plan-Start\Plan-End segments
                if ($mapLength - $pastItemsLength > $maxFutureSegments) {
                    [
                        'futureCount' => $futureSegmentsCount,
                        'futureDeletedCount' => $futureDeletedSegmentsCount,
                    ] = $map[$pastItemsLength];
                    $totalLiveFutureSegmentsCount = $futureSegmentsCount - $futureDeletedSegmentsCount;
                    $maxAllowedPrefix =
                        ArrayUtils::binarySearchLeftmostRangeWithComparator(
                            $map,
                            $queryOptions->isShowDeleted() ?
                                fn (array $segment) =>
                                    ($futureSegmentsCount - $segment['futureCount']) <=> $maxFutureSegments
                                : fn (array $segment) =>
                                    ($totalLiveFutureSegmentsCount - ($segment['futureCount'] - $segment['futureDeletedCount'])) <=> $maxFutureSegments,
                            $pastItemsLength
                        )
                        ->getPrefixLength();
                    $maxFutureIdx = \min($pastItemsLength + $maxAllowedPrefix, $mapLength - 1);
                    $startDate = $currentSnappedDate;
                    $maxFutureIdxDate = $map[$maxFutureIdx]['startDate'];
                    $endDate =
                        seconds(
                            ((int) \ceil($maxFutureIdxDate->getTimestamp() / $snapIntervalSeconds))
                            * $snapIntervalSeconds
                        )
                        ->getAsDateTime();
                } else {
                    $startDate = $defaultStartDate;
                    $endDate = $defaultEndDate;
                }
            } else {
                $startDate = $defaultStartDate;
                $endDate = $defaultEndDate;
            }

            if ($endDate->getTimestamp() < $startDate->getTimestamp()) {
                $endDate = $startDate;
            }

            $queryOptions = $queryOptions
                ->setStartDate($startDate)
                ->setEndDate($endDate); // in case no future segment load only past
        }

        $pastSegments = null;

        if ($queryOptions->hasStartDate()) {
            // if there are StartDate - MaxSegments mean max past segments, future is unlimited
            $pastSegments = $queryOptions->getMaxSegments();

            if ($pastSegments === null) {
                $pastSegments = self::DEFAULT_PAST_SEGMENTS_AMOUNT;
            }
            $queryOptions = $queryOptions->setMaxSegments(null);
        }

        $chunkItems = SegmentMapUtils::getChunkByOptions($map, $queryOptions);

        if ($pastSegments > 0) {
            // add some items from past
            $qPast = $queryOptions
                ->setEndDate($queryOptions->getStartDate())
                ->setStartDate(null)
                ->setMaxSegments($pastSegments);

            $pastItems = SegmentMapUtils::getChunkByOptions($map, $qPast);

            if (count($pastItems)) {
                $startDate = $queryOptions->getStartDate()->getTimestamp();
                $count = 0;
                $startIndex = count($pastItems) - 1;

                foreach ($pastItems as $n => $item) {
                    if ($item["startDate"]->getTimestamp() < $startDate) {
                        $count++;
                    }

                    if ($count >= $pastSegments) {
                        $startIndex = $n;

                        break;
                    }
                }
                $queryOptions = $queryOptions->setStartDate(clone $pastItems[$startIndex]["startDate"]);
            }
            $chunkItems = array_merge($chunkItems, $pastItems); // chunkItems in reverse order, keep it this way
        }

        if (!$chunkItems) {
            return [];
        }

        $queryOptions = $queryOptions->setItems($chunkItems);

        // needed ?
        if ($pastSegments > 0 && $queryOptions->hasEndDate()) {
            // reversed items here
            $queryOptions = $queryOptions
                ->setEndDate(clone $chunkItems[0]['startDate']);
        }

        if (!$queryOptions->isWithDetails()) {
            return $this->executeQuery($queryOptions);
        }

        $dumpedKeysPostfix = $this->translator->isDumpKeysEnabled() || $this->translator->isEnableDesktopHelper() ? '_dumped_keys_' : '';
        $timelineTranslationKeys = [];
        $request = $this->requestStack->getMasterRequest();
        $requestLocale = $request ? $request->getLocale() : 'null';
        $key = Tags::getTimelineKeyByOptions(
            $pastSegments > 0 ? $queryOptions->setMaxSegments($pastSegments) : $queryOptions, // preserve past segments count for the sake of cache-key uniqueness
            $this->tokenStorage->getBusinessUser(),
            $this->localizeService->getLocale() . '_' . $requestLocale . '_' . $dumpedKeysPostfix
        );

        $tags = Tags::getTimelineTags($queryOptions->getUser(), $queryOptions->getUserAgent());

        if ($queryOptions->hasCacheTags()) {
            $tags = array_merge($tags, $queryOptions->getCacheTags());
        }

        return $this->cacheManager->load(
            (new CacheItemReference(
                $key,
                $tags,
                function () use ($queryOptions, &$timelineTranslationKeys) {
                    if ($this->translator->isDumpKeysEnabled()) {
                        $keysBefore = $this->translator->getDumpedKeys();
                    }

                    $data = $this->executeQuery($queryOptions);

                    if (isset($keysBefore)) {
                        // call array_merge because callback may be invoked several times
                        $timelineTranslationKeys = array_merge(
                            $timelineTranslationKeys,
                            array_diff_key($this->translator->getDumpedKeys(), $keysBefore)
                        );
                    }

                    return $data;
                }
            ))->setSerializer(function ($data) use (&$timelineTranslationKeys) {
                if ($this->translator->isDumpKeysEnabled()) {
                    $data = [
                        'dumpedTranslationKeys' => $timelineTranslationKeys,
                        'data' => $data,
                    ];
                }

                return $data;
            })->setDeserializer(function ($data) {
                if (isset($data['dumpedTranslationKeys'])) {
                    if ($this->translator->isDumpKeysEnabled()) {
                        $this->translator->setDumpedKeys(array_merge(
                            $this->translator->getDumpedKeys(),
                            $data['dumpedTranslationKeys']
                        ));
                    }

                    $data = $data['data'];
                }

                return $data;
            })->setOptions(CacheItemReference::OPTION_GZIP_AUTO)
        );
    }

    /**
     * get user start date, will cache start date for 2 weeks, to allow timeline cache.
     */
    public function getUserStartDate(Usr $user, ?\DateTime $dateTimeForced = null): \DateTime
    {
        return $this->cacheManager->load(
            (new CacheItemReference(
                Tags::getTimelineUserStartDateKey($user),
                Tags::getTimelineTags($user),
                fn () =>
                    $dateTimeForced ??
                    $this->clock->current()->getAsDateTime()->modify('-' . self::START_DATE_OFFSET_DAYS . ' day')
            ))
            ->setExpiration(days(self::START_DATE_CACHE_LIFETIME_DAYS)->getAsSecondsInt())
        );
    }

    /**
     * @param string $format
     * @return $this
     */
    public function addFormatHandler($format, FormatHandlerInterface $formatter)
    {
        $this->formatHandlers[$format] = $formatter;

        return $this;
    }

    /**
     * @param $shareCode string
     * @return array
     */
    public function decodeShareCode($shareCode)
    {
        $shareCode = StringHandler::base64_decode_url($shareCode);
        $parts = explode(".", $shareCode);

        if (count($parts) != 3) {
            return [];
        }

        return $parts;
    }

    /**
     * @param string $shareCode
     */
    public function queryByShareCode($shareCode, QueryOptions $queryOptions)
    {
        $queryOptions = $queryOptions->lock();

        $decoded = $this->decodeShareCode($shareCode);

        if (count($decoded) != 3) {
            throw new BadRequestHttpException("Invalid share code");
        }
        [$kind, $id, $code] = $decoded;

        if ($kind === 'Travelplan') {
            $travelPlan = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->find($id);

            /** @var Plan $travelPlan */
            if (empty($travelPlan) || $travelPlan->getShareCode() != $code) {
                return [];
            }

            $queryOptions = $queryOptions->setUser($travelPlan->getUser())->setSharedPlan($travelPlan);

            if ($travelPlan->getUserAgent()) {
                $queryOptions = $queryOptions->setUserAgent($travelPlan->getUserAgent());
            }

            return $this->query($queryOptions);
        } else {
            if (!isset(Itinerary::$table[$kind])) {
                return [];
            }
            $entity = $this->em->getRepository(Itinerary::getItineraryClass($kind))->find($id);

            /** @var Itinerary $entity */
            if (empty($entity) || $entity->getSharecode() != $code) {
                return [];
            }

            $queryOptions = $queryOptions->setUser($entity->getUser())->setShareId($kind . '.' . $id);

            if (null !== $entity->getUserAgent()) {
                $queryOptions = $queryOptions->setUserAgent($entity->getUserAgent());
            }

            return $this->query($queryOptions);
        }
    }

    /**
     * @param string[] $itineraryIds
     * @throws \Exception
     */
    public function queryByItineraries(array $itineraryIds, QueryOptions &$queryOptions)
    {
        $user = $this->tokenStorage->getBusinessUser();
        $queryOptions->setEntityManager($this->em);
        $queryOptions->setUser($user);
        $queryOptions = $queryOptions->lock();

        $segments = [];
        /** @var EntityRepository[] $repoMap */
        $repoMap = [];

        foreach (Itinerary::$table as $kind => $name) {
            $repoMap[$kind] = $this->em->getRepository(Itinerary::getItineraryClass($name));
        }

        $repoMap['TS'] = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
        $table = Itinerary::$table;
        $table['TS'] = 'Tripsegment';

        $ids = [];

        foreach ($itineraryIds as $itId) {
            [$kind, $id] = explode(".", $itId);

            if (array_key_exists($kind, $table)) {
                $ids[$id] = $kind;
            }
        }

        foreach ($ids as $id => $kind) {
            /** @var SegmentSourceInterface|Itinerary $it */
            $it = $repoMap[$kind]->find($id);

            if (
                !empty($it)
                && (
                    ($it instanceof Itinerary && $this->authorizationChecker->isGranted('VIEW', $it))
                    || ($it instanceof Tripsegment && $this->authorizationChecker->isGranted('VIEW', $it->getTripid()))
                )
            ) {
                $segments = array_merge($segments, $it->getTimelineItems($user, $queryOptions));
            }
        }
        $this->loadDetails($segments, $user, $user);
        $result = $this->builder->build($segments, $queryOptions);

        return $this->format($result, $queryOptions);
    }

    /**
     * @param string $itCode like 'PU.1245'
     * @return object|null
     * @throws \InvalidArgumentException
     */
    public function getEntityByItCode($itCode)
    {
        $parts = explode(".", $itCode);

        if (count($parts) != 2) {
            throw new \InvalidArgumentException("Itinerary code wrong format. Expected like CI.12345, obtained: " . $itCode);
        }
        [$kind, $id] = $parts;

        foreach (Itinerary::$table as $table) {
            $kinds = call_user_func(["\\AwardWallet\\MainBundle\\Entity\\$table", "getSegmentMap"]);

            if (in_array($kind, $kinds)) {
                if ($table == "Trip") {
                    $table = "Tripsegment";
                }

                return $this->em->getRepository(Itinerary::getItineraryClass($table))->find($id);
            }
        }

        throw new \InvalidArgumentException("Invalid itinerary code");
    }

    /**
     * @param string $segmentId like 'PU.1245'
     * @return bool - true - was deleted, false - was not found
     */
    public function deleteSegment($segmentId, $undelete = false)
    {
        $entity = $this->getEntityByItCode($segmentId);

        if (empty($entity)) {
            return false;
        }

        if ($entity instanceof Tripsegment) {
            $checkAccessOn = $entity->getTripid();
        } else {
            $checkAccessOn = $entity;
        }

        // @TODO: Itinerary voter for EDIT
        /** @var Itinerary $checkAccessOn */
        //        $user = $this->authorizationChecker->getToken()->getUser();
        //        if (!($user instanceof Usr) || $user->getUserid() != $checkAccessOn->getUser()->getUserid())
        //            return false;

        if (!$this->authorizationChecker->isGranted('EDIT', $checkAccessOn)) {
            return false;
        }

        $this->logger->warning("deleting segment by user $segmentId", ["undelete" => $undelete]);

        if ($entity instanceof Tripsegment) {
            if ($undelete) {
                $entity->unhide();
            } else {
                $entity->hideByUser();
            }
            $entity->setUndeleted($undelete);
        } else {
            $entity->setHidden(!$undelete);
            $entity->setUndeleted($undelete);
        }

        if ($entity instanceof Tripsegment) {
            $trip = $entity->getTripid();
            $trip->setHidden(array_reduce($trip->getSegments()->toArray(), function ($carry, Tripsegment $ts) {
                return $carry && $ts->getHidden();
            }, true));
        }

        $this->em->persist($entity);
        $this->em->flush();

        return true;
    }

    /**
     * @param string $segmentId like 'PU.1245'
     * @return bool - true - changes were confirmed, false - segment or changes were not found
     */
    public function confirmSegmentChanges($segmentId)
    {
        $entity = $this->getEntityByItCode($segmentId);

        if (empty($entity)) {
            return false;
        }

        if ($entity instanceof Tripsegment) {
            $checkAccessOn = $entity->getTripid();
        } else {
            $checkAccessOn = $entity;
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $checkAccessOn)) {
            return false;
        }

        $source = $entity->getKind();

        if (!$source) {
            return false;
        }

        [$kind, $id] = explode('.', $segmentId);

        $stmt = $this->em->getConnection()->executeQuery(
            "DELETE FROM DiffChange WHERE SourceID = :sourceId",
            ['sourceId' => "$source.$id"],
            [\PDO::PARAM_STR]
        );

        $entity->setChangeDate(null);
        $this->em->flush($entity);

        $this->logger->warning("confirmed changes on segment {$segmentId}, {$stmt->rowCount()} old properties deleted ");

        return true;
    }

    /**
     * @param string $itCode
     * @param bool $copy
     * @return Itinerary
     * @throws \Exception
     */
    public function moveItinerary($itCode, ?Useragent $agent = null, $copy = false)
    {
        $entity = $this->getEntityByItCode($itCode);
        $user = $this->tokenStorage->getBusinessUser();

        if (empty($entity)) {
            throw new \Exception("Itinerary not found");
        }

        if ($entity instanceof Tripsegment) {
            $entity = $entity->getTripid();
        }

        if ($agent && !$this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent)) {
            throw new AccessDeniedException();
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $ownerBefore = $entity->getOwnerId();

        if ($copy) {
            $targetEntity = clone $entity;
            $targetEntity->setFiles([]);
            $targetEntity->setCopied(true);
            $targetEntity->setCreateDate($this->clock->current()->getAsDateTime());
        } else {
            $targetEntity = $entity;
            $targetEntity->setMoved(true);
        }

        if (!$agent || $agent->isFamilyMember()) {
            $targetEntity->setUser(isset($agent) ? $agent->getAgentid() : $user);
            $targetEntity->setUserAgent($agent ?? null);
        } else {
            $targetEntity->setUser($agent->getClientid());
            $targetEntity->setUserAgent(null);
        }

        $ownerAfter = $targetEntity->getOwnerId();
        $this->logger->warning("moving itinerary {$itCode} {$entity->getId()} from {$ownerBefore} to {$ownerAfter}, copy: " . var_export($copy, true), ["UserID" => $user->getId()]);

        if (($dub = $this->itineraryChecker->isUnique($targetEntity)) !== true) {
            $this->em->remove($dub->first());
            $this->em->flush($dub->first());
        }

        $this->em->persist($targetEntity);
        $this->em->flush();

        return $targetEntity;
    }

    public function changeItinerariesOwner(Owner $oldOwner, Owner $newOwner)
    {
        foreach ([Trip::class, Reservation::class, Rental::class, Restaurant::class, Parking::class] as $itineraryClass) {
            $repository = $this->em->getRepository($itineraryClass);
            /** @var Itinerary[] $itineraries */
            $itineraries = $repository->findBy(['user' => $oldOwner->getUser(), 'userAgent' => $oldOwner->getFamilyMember()]);

            foreach ($itineraries as $itinerary) {
                $this->logger->warning("changing itinerary owner from {$oldOwner->getOwnerId()} to {$newOwner->getOwnerId()}", ["Kind" => $itinerary->getKind(), "ID" => $itinerary->getId()]);
                $itinerary->setOwner($newOwner);
            }
        }
    }

    public function getSegmentCount(Usr $user, ?Useragent $ua = null)
    {
        $queryOptions = $this->getCountOptions($user);

        if (!empty($ua)) {
            $queryOptions->lock();
            $queryOptions = $this->correctUser($queryOptions->setUserAgent($ua));
        }

        return $this->getCount($queryOptions);
    }

    public function hideAIWarning(string $itCode): bool
    {
        $entity = $this->getEntityByItCode($itCode);

        if (empty($entity)) {
            return false;
        }

        if ($entity instanceof Tripsegment) {
            $checkAccessOn = $entity->getTripid();
        } else {
            $checkAccessOn = $entity;
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $checkAccessOn)) {
            return false;
        }

        $shown = $entity->isShowAIWarningForEmailSource();

        if (!$shown) {
            return true;
        }

        $entity->setShowAIWarning(false);
        $this->em->flush();

        return true;
    }

    /**
     * @return array - ['my' => ['count' => 5, 'name' => 'Alexi Vereschaga', 'accessLevel' => ACCESS_WRITE], ...
     */
    public function getTotals(Usr $user)
    {
        // TODO: move out this code to DesktopTimelineHelper-like class
        $result = [];

        foreach ($user->getFamilyMembers() as $agent) {
            /** @var Useragent $agent */
            if (!$agent->isItinerariesSharedWith($user)) {
                continue;
            }

            $result[$agent->getId()] = [
                'count' => $this->getSegmentCount($user, $agent),
                'name' => $agent->getFullName(),
                'accessLevel' => $agent->getAccesslevel(),
                'clientId' => $agent->getClientid(),
            ];
        }

        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        /** @var TimelineShare $sharedTimeline */
        foreach ($user->getSharedTimelines() as $sharedTimeline) {
            $is = (!$sharedTimeline->getFamilyMember() && $sharedTimeline->getUserAgent()->isApproved());

            if (!$is && $sharedTimeline->getFamilyMember()) {
                $is = true;
                $uaTarget = $userAgentRep->findOneBy([
                    'agentid' => $sharedTimeline->getUserAgent()->getClientid(),
                    'clientid' => $user,
                ]);

                if (null !== $uaTarget && !$uaTarget->isApproved()) {
                    $is = false;
                }
            }

            if ($is) {
                $result[$sharedTimeline->getFamilyMember() ? $sharedTimeline->getFamilyMember()->getId() : $sharedTimeline->getUserAgent()->getId()] = [
                    'count' => $this->getSegmentCount($sharedTimeline->getTimelineOwner(), $sharedTimeline->getFamilyMember()),
                    'name' => $sharedTimeline->getTimelineOwner()->getFullName(),
                    'accessLevel' => $sharedTimeline->getUserAgent()->getAccesslevel(),
                    'clientId' => $sharedTimeline->getUserAgent()->getClientid(),
                    'timeline_access_level' => $sharedTimeline->getUserAgent()->getTripAccessLevel(),
                    'sharedFamilyMember' => $sharedTimeline->getFamilyMember() ? $sharedTimeline->getFamilyMember()->getFullName() : null,
                    'timelineShareId' => $sharedTimeline->getTimelineShareId(),
                ];
            }
        }

        uasort($result, function ($a, $b) {
            return
                (isset($a['sharedFamilyMember']) && !empty($a['sharedFamilyMember']) ? $a['sharedFamilyMember'] : $a['name']) >
                (isset($b['sharedFamilyMember']) && !empty($b['sharedFamilyMember']) ? $b['sharedFamilyMember'] : $b['name']);
        });

        $result = [
            '' => [
                'count' => $this->getSegmentCount($user),
                'name' => $user->getFullName(),
                'accessLevel' => ACCESS_WRITE,
                'clientId' => null,
            ],
        ] + $result;

        return $result;
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function hasMoreBefore(\DateTime $date, Usr $user, ?Useragent $useragent = null, bool $withPlans = true, bool $showDeleted = true)
    {
        return $this->cacheManager->load(new CacheItemReference(
            Tags::getTimelineHasBeforeKey($date, $user, $useragent, $withPlans, $showDeleted),
            Tags::getTimelineCounterTags($user, $useragent),
            function () use ($date, $user, $useragent, $showDeleted) {
                $queryOptions =
                    (new QueryOptions())
                    ->setMaxSegments(1)
                    ->setEndDate($date)
                    ->setWithDetails(false)
                    ->setShowDeleted($showDeleted)
                    ->setUser($user)
                    ->setUserAgent($useragent)
                    ->lock();

                $timelineMap = it($this->getTimelineMap($queryOptions));

                if (!$showDeleted) {
                    $timelineMap = $timelineMap->filterByColumn('deleted', false);
                }

                $firstItem = $timelineMap->first();

                if (!$firstItem) {
                    return false;
                }

                $safeDate =
                    $this->clock->current()->getAsDateTime()
                    ->setTimestamp($date->getTimestamp())
                    ->modify('-2 day');

                $hasBefore = $firstItem['startDate']->getTimestamp() < $safeDate->getTimestamp();

                return $hasBefore ?: (bool) $this->query($queryOptions);
            }
        ));
    }

    protected function loadSegments(QueryOptions $queryOptions): array
    {
        return \iter\toArray($this->loadSegmentsIter($queryOptions));
    }

    /**
     * Load segments.
     */
    protected function loadSegmentsIter(QueryOptions $queryOptions): iterable
    {
        $queryOptions = $queryOptions->setOperatedByResolver($this->operatedByResolver);
        $queryOptions->setEntityManager($this->em);
        $user = $queryOptions->getUser();
        $segments = [];

        // we will grab a couple of days more, to cut by day boundary. later we will discard extra segments
        // in Builder->filterByDates
        if ($queryOptions->hasItems()) {
            $queryOptions = $queryOptions
                ->setStartDate(null)
                ->setEndDate(null);
        } else {
            if (!empty($queryOptions->getStartDate())) {
                $queryOptions = $queryOptions->setStartDate($queryOptions->getStartDate()->sub(new \DateInterval('P2D')));
            }

            if (!empty($queryOptions->getEndDate())) {
                $queryOptions = $queryOptions->setEndDate($queryOptions->getEndDate()->add(new \DateInterval('P2D')));
            }
        }

        foreach (['Tripsegment', 'Rental', 'Reservation', 'Restaurant', 'Parking'] as $table) {
            /** @var SegmentSourceInterface $repo */
            $repo = $this->em->getRepository(Itinerary::getItineraryClass($table));

            yield from $repo->getTimelineItems($user, $queryOptions);
        }
    }

    private function executeQuery(QueryOptions $queryOptions)
    {
        $segments = $this->loadSegments($queryOptions);

        $result = $this->builder->build($segments, $queryOptions);

        if ($queryOptions->isWithDetails()) {
            $this->loadDetails($segments, $queryOptions->getUser(), $this->tokenStorage->getBusinessUser());
        }

        if ($queryOptions->isWithDetails() && $queryOptions->hasFormat()) {
            $result = $this->format($result, $queryOptions);
        }

        return $result;
    }

    /**
     * @param ItemInterface[] $segments
     * @throws \Exception
     */
    private function loadDetails(array $segments, Usr $timelineOwnerUser, ?Usr $user = null)
    {
        $segments = array_filter($segments, function (ItemInterface $segment) {
            return $segment instanceof AbstractItinerary;
        });
        /** @var AbstractItinerary[] $segments */
        $this->tripHelper->fillBookingLinks($segments, $timelineOwnerUser);
        $this->loadChanges($segments);
        $mileValuesData = $this->mileValueAlternativeFlights->fetchMileValuesData($segments);
        $userConnectionMapCache = [];
        $phoneBook = $this->phoneBookFactory->create(
            it($segments)
                ->map(fn (AbstractItinerary $segment) => $segment->getSource())
                ->filterNotNull()
                ->toArray(),
            $user
        );

        foreach ($segments as $item) {
            $itinerary = $item->getItinerary();

            if ($item instanceof AbstractTrip) {
                if (array_key_exists($itinerary->getId(), $mileValuesData)) {
                    $item->setMileValue($mileValuesData[$itinerary->getId()]);
                }

                if (ItineraryUtil::isOverseasTravel($itinerary->getGeoTags())) {
                    $item->setIsOverseasTrip(true);
                }
            }

            $item->setPhones(!is_null($item->getSource()) ? $phoneBook->getPhones($item->getSource()) : []);

            if ($item->isChanged() && !$item->getChanges()) {
                $item->setChanges($this->diffQuery->query($item->getDiffSourceId()));
            }

            if (
                !empty($user)
                && !empty($itinerary)
                && !empty($itinerary->getUser())
                && (
                    $itinerary->getUser()->isBusiness()
                    || ($itinerary->getUser()->getUserid() == $user->getUserid())
                )
            ) {
                if (
                    !empty($itinerary->getUserAgent())
                    && $itinerary->getUserAgent()->isFamilyMember()
                ) {
                    $item->setAgent($itinerary->getUserAgent());
                } else {
                    $connectedUserId = $itinerary->getUser()->getId();

                    if (!array_key_exists($connectedUserId, $userConnectionMapCache)) {
                        $userConnectionMapCache[$connectedUserId] = $user->getConnectionWith($itinerary->getUser());
                    }

                    $item->setAgent($userConnectionMapCache[$connectedUserId]);
                }
            }
        }

        $this->loadItineraryFiles($segments);
    }

    private function loadItineraryFiles(array $segments): void
    {
        $assignFiles = $map = [];

        foreach ($segments as &$item) {
            $itinerary = $item->getItinerary();
            $kind = Itinerary::ITINERARY_KIND_TABLE[$itinerary->getKind()];
            $segmentIdentifier = $itinerary->getKind() . '_' . $itinerary->getId();
            $map[$segmentIdentifier] = $item;

            if (!array_key_exists($kind, $assignFiles)) {
                $assignFiles[$kind] = [];
            }
            $assignFiles[$kind][] = $itinerary->getId();
        }

        if (empty($assignFiles)) {
            return;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from(ItineraryFile::class, 'f');

        foreach ($assignFiles as $kind => $filesId) {
            $qb->orWhere('(f.itineraryTable = ' . $kind . ' AND f.itineraryId IN (' . implode(',', $filesId) . '))');
        }

        $result = $qb->getQuery()->getResult();

        /** @var ItineraryFile $file */
        foreach ($result as $file) {
            $segmentIdentifier = $file->getItinerary()->getKind() . '_' . $file->getItinerary()->getId();
            $map[$segmentIdentifier]->addFile($file);
        }
    }

    /**
     * @return array
     */
    private function format(array $items, QueryOptions $options)
    {
        if (!isset($this->formatHandlers[$format = $options->getFormat()])) {
            throw new \InvalidArgumentException("Unknown format handler alias '{$format}'");
        }

        return $this->formatHandlers[$format]->handle($items, $options);
    }

    private function getCountOptions(Usr $user)
    {
        $queryOptions = new QueryOptions();
        $queryOptions
            ->setStartDate($this->clock->current()->getAsDateTime()->modify('-2 hour'))
            ->setShowPlans(true)
            ->setUser($user);

        return $queryOptions;
    }

    private function getCount(QueryOptions $queryOptions)
    {
        $queryOptions = $queryOptions->lock();

        return $this->cacheManager->load((new CacheItemReference(
            Tags::TAG_TIMELINE_COUNTER . Tags::getTimelineCounterKey($queryOptions->getUser(), $queryOptions->getUserAgent(), $queryOptions->getShowPlans()),
            Tags::getTimelineCounterTags($queryOptions->getUser(), $queryOptions->getUserAgent()),
            function () use ($queryOptions) {
                $timelineList = $this->getTimelineMap($queryOptions);

                if (!$timelineList) {
                    return 0;
                }

                $startDate = $queryOptions->getStartDate()->setTime(0, 0, 0);
                $pastLength =
                    ArrayUtils::binarySearchLeftmostRangeWithComparator(
                        $timelineList,
                        fn (array $item) => $item['startDate'] <=> $startDate,
                    )
                    ->getPrefixLength();

                if (!\array_key_exists($pastLength, $timelineList)) {
                    return 0;
                }

                return $timelineList[$pastLength]['futureNotDeletedUniqCount'];
            },
            null,
            null,
            CacheItemReference::OPTION_NO_OPTIONS
        ))->setExpiration(300));
    }

    /**
     * Loads timeline map.
     * Timeline map is an ordered(with no respect to timezone info) set of segment metadata.
     *
     * @return array
     */
    private function getTimelineMap(QueryOptions $queryOptions)
    {
        $user = $queryOptions->getUser();
        $userAgent = $queryOptions->getUserAgent();
        $withPlans = $queryOptions->getShowPlans();

        $key = Tags::getTimelineMapKey($user, $userAgent, $withPlans);
        $tags = Tags::getTimelineCounterTags($user, $userAgent);

        $timelineMap = $this->cacheManager->load(
            (new CacheItemReference(
                $key,
                $tags,
                function () use ($user, $userAgent, $withPlans) {
                    /** @var SegmentMapItem[] $segments */
                    $segments = [];

                    foreach (Itinerary::$table as $table) {
                        /** @var SegmentMapSourceInterface $repo */
                        $repo = $this->em->getRepository(Itinerary::getItineraryClass($table));

                        $segments = array_merge($segments, $repo->getTimelineMapItems($user, $userAgent));
                    }

                    if ($withPlans) {
                        $segments = array_merge($segments, (new PlanMapQuery($this->em->getConnection()))->getTimelineMapItems($user, $userAgent));
                    }

                    usort(
                        $segments,
                        function (
                            /** @var SegmentMapItem $a */
                            $a,
                            /** @var SegmentMapItem $b */
                            $b
                        ) {
                            return $a['startDate']->getTimestamp() - $b['startDate']->getTimestamp();
                        }
                    );

                    if (\count($segments) >= 40_000) {
                        $segments = \array_slice($segments, -15_000);
                    }

                    $this->preCalculateSegmentsCount($segments);

                    return $segments;
                }
            ))->setOptions(CacheItemReference::OPTION_GZIP_AUTO)
        );

        return $timelineMap;
    }

    private function preCalculateSegmentsCount(array &$segments): void
    {
        $skipFromCount = [
            /*
            Parking::SEGMENT_MAP_END => null,
            Reservation::SEGMENT_MAP_END => null,
            Rental::SEGMENT_MAP_END => null,
            */
        ];

        $futureCount = 0;
        $futureDeletedCount = 0;
        $uniq = [];

        for ($i = \count($segments) - 1; $i >= 0; --$i) {
            $segment = &$segments[$i];
            $isDeleted = true === (bool) $segment['deleted'];
            $isSkipped = \array_key_exists($segment['type'], $skipFromCount)
                || (array_key_exists('isPlanType', $segment) && true === $segment['isPlanType']);
            $segment['futureCount'] = $futureCount + (int) (!$isSkipped);
            $segment['futureDeletedCount'] = $futureDeletedCount + ((int) (!$isSkipped)) * ((int) $isDeleted);
            $segment['futureNotDeletedUniqCount'] = \count($uniq);

            if ($isSkipped) {
                continue;
            }

            ++$futureCount;

            if ($isDeleted) {
                ++$futureDeletedCount;
            } else {
                $uniq[$segment['shareId']] = 1 + ($uniq[$segment['shareId']] ?? 0);
                $segment['futureNotDeletedUniqCount'] = \count($uniq);
            }
        }
        unset($segment);

        $pastCount = 0;
        $pastDeletedCount = 0;

        foreach ($segments as $key => &$segment) {
            $segment['pastCount'] = $pastCount;
            $segment['pastDeletedCount'] = $pastDeletedCount;

            if (\array_key_exists($segment['type'], $skipFromCount)
                || (array_key_exists('isPlanType', $segment) && true === $segment['isPlanType'])
            ) {
                continue;
            }

            ++$pastCount;

            if ($segment['deleted']) {
                ++$pastDeletedCount;
            }
        }
        unset($segment);
    }

    private function correctUser(QueryOptions $queryOptions)
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();
        $userAgent = $queryOptions->getUserAgent();

        if (!empty($userAgent) && ($userAgent->getAgentid() != $user || !$userAgent->isFamilyMember())) {
            if (!$userAgent->isFamilyMember()) {
                $timelineShare = !empty($user) ? $user->getTimelineShareWith($userAgent->getClientid()) : null;
                $queryOptions = $queryOptions
                    ->setUser($userAgent->getClientid())
                    ->setUserAgent(null);
            } else {
                $timelineShare = !empty($user) ? $user->getTimelineShareWith($userAgent->getAgentid(), $userAgent) : null;
                $queryOptions = $queryOptions
                    ->setUser($userAgent->getAgentid());
            }
        }

        if (!empty($timelineShare)) {
            $tags = $queryOptions->getCacheTags() ?: [];
            $queryOptions = $queryOptions->setCacheTags(array_merge($tags, Tags::addTagPrefix(Tags::getTimelineShareTags($timelineShare->getTimelineShareId()))));
        }

        if (!empty($userAgent)) {
            $tags = $queryOptions->getCacheTags() ?: [];
            $queryOptions = $queryOptions->setCacheTags(array_merge($tags, Tags::addTagPrefix(Tags::getUserAgentTags($userAgent->getUseragentid()))));
        }

        return $queryOptions;
    }

    /**
     * @param AbstractItinerary[] $segments
     */
    private function loadChanges(array $segments)
    {
        /** @var AbstractItinerary[][] $segmentsToLoadMap */
        $segmentsToLoadMap =
            it($segments)
            ->reindex(function (AbstractItinerary $segment) {
                return $segment->getDiffSourceId();
            })
            ->collapseByKey()
            ->toArrayWithKeys();

        foreach ($this->diffQuery->queryAll(\array_keys($segmentsToLoadMap)) as $sourceId => $changes) {
            foreach ($segmentsToLoadMap[$sourceId] as $segment) {
                $segment->setChanges($changes);
            }
        }
    }
}
