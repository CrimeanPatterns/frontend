<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\FilterCallback\FilterCallback;
use AwardWallet\MainBundle\Timeline\FilterCallback\FilterCallbackInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\PlanItem;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Helper
{
    private const TIMELINE_ITEMS_MEMORY_INCREASE_THRESHOLD = 400;
    private const TIMELINE_ITEMS_MEMORY_INCREASE_STEP = 100;
    private const MEMORY_INCREASE_STEP_BYTES = 50 * 1024 * 1024; // 50 MB per additional 200 timeline items
    private const MEMORY_MAX_INCREASE_STEPS = 4; // max 200 MB additional memory
    private OperatedByResolver $operatedByResolver;
    private Manager $manager;
    private ApiVersioningService $apiVersioning;
    private ManagerRegistry $doctrine;
    private AuthorizationCheckerInterface $authorizationChecker;
    private OwnerRepository $ownerRepository;
    private LoggerInterface $logger;

    public function __construct(
        Manager $manager,
        ApiVersioningService $apiVersioning,
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        OwnerRepository $ownerRepository,
        LoggerInterface $logger,
        OperatedByResolver $operatedByResolver
    ) {
        $this->manager = $manager;
        $this->apiVersioning = $apiVersioning;
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->ownerRepository = $ownerRepository;
        $this->logger = $logger;
        $this->operatedByResolver = $operatedByResolver;
    }

    /**
     * @return TimelineView[]
     */
    public function getUserTimelines(Usr $user)
    {
        $withPlans = $this->apiVersioning->supports(MobileVersions::TIMELINE_PLANS_ITEMS);
        $withParkings = $this->apiVersioning->supports(MobileVersions::TIMELINE_PARKINGS_ITEMS);
        $withCruiseLayouts = $this->apiVersioning->supports(MobileVersions::TIMELINE_CRUISE_LIST_ITEMS);

        $userQueryOptions = QueryOptions::createMobile()
            ->setWithDetails(true)
            ->setFuture(true)
            ->setMaxFutureSegments(Manager::MAX_FUTURE_SEGMENTS)
            ->setMaxSegments(50)
            ->addFilterCallback($this->getParkingsFilter($withParkings))
            ->addFilterCallback($this->getCruiseLayoversFilter($withCruiseLayouts))
            ->setFormatOptions($baseOptions = new FeaturesBitSet($this->getLoggedInFormatOptions()))
            ->setUser($user)
            ->setShowPlans($withPlans)
            ->lock();

        // User timeline

        $userTimeline = (new TimelineView())
            ->setName($user->getFullName())
            ->setItems($items = $this->manager->query(
                $userQueryOptions
                    ->setFormatOptions($baseOptions->enable(
                        $this->featureToOption(MobileVersions::GEO_NOTIFICATIONS, Mobile\FormatHandler::GEO_NOTIFICATIONS)
                    ))
            ))
            ->setUserAgentId('my')
            ->setItineraryForwardEmail($user->getItineraryForwardingEmail())
            ->setNeedMore(
                (($startDateTs = $this->getStartDateTs($items)) !== null)
                && $this->manager->hasMoreBefore(new \DateTime('@' . $startDateTs), $user, null, $withPlans, false)
            )
            ->setCanChange(true)
            ->setCanConnectMailbox(true);

        $this->tryAllocateMemory($user);

        $sharedTimelines = [];

        // Familiy members timeline

        foreach ($user->getFamilyMembers() as $familyMember) {
            if (!$familyMember->isItinerariesSharedWith($user)) {
                continue;
            }

            $sharedTimelines[] = (new TimelineView())
                ->setItems($items = $this->manager->query($userQueryOptions->setUserAgent($familyMember)))
                ->setName($familyMember->getFullName())
                ->setUserAgentId($familyMember->getUseragentid())
                ->setNeedMore(
                    (($startDateTs = $this->getStartDateTs($items)) !== null)
                    && $this->manager->hasMoreBefore(new \DateTime('@' . $startDateTs), $user, $familyMember, $withPlans, false)
                )
                ->setItineraryForwardEmail($familyMember->getItineraryForwardingEmail())
                ->setCanChange(true)
                ->setCanConnectMailbox(true);
        }

        // Shared timeline
        $userAgentRep = $this->doctrine->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        foreach ($user->getSharedTimelines() as $sharedTimeline) {
            // incoming to $user
            $incomingConnectionLink = $userAgentRep->findOneBy([
                'agentid' => $sharedTimeline->getUserAgent()->getClientid(),
                'clientid' => $user,
            ]);

            if (
                !$user->getTimelineShareWith($sharedTimeline->getTimelineOwner(), $sharedTimeline->getFamilyMember())
                || (null !== $incomingConnectionLink && !$incomingConnectionLink->isApproved())
            ) {
                continue;
            }

            $agent = $sharedTimeline->getFamilyMember() ?: $sharedTimeline->getUserAgent();
            $ownerName = $sharedTimeline->getTimelineOwner()->getFullName();
            $familyName = ($family = $sharedTimeline->getFamilyMember()) ? $family->getFullName() : null;

            $sharedTimelines[] = (new TimelineView())
                ->setItems($items = $this->manager->query($userQueryOptions->setUserAgent($agent)))
                ->setName(
                    $this->apiVersioning->supports(MobileVersions::TIMELINE_OWNER_NAME) ?
                        $ownerName :
                        (isset($familyName) ? sprintf('%s (%s)', $familyName, $ownerName) : $ownerName)
                )
                ->setFamilyName($family ? $family->getFullName() : null)
                ->setUserAgentId($agent->getUseragentid())
                ->setItineraryForwardEmail($agent->getItineraryForwardingEmail())
                ->setNeedMore(
                    (($startDateTs = $this->getStartDateTs($items)) !== null)
                    && $this->manager->hasMoreBefore(new \DateTime('@' . $startDateTs), $user, $agent, $withPlans, false)
                )
                ->setCanChange($this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent))
                ->setCanConnectMailbox(false);
        }

        usort($sharedTimelines, function (TimelineView $a, TimelineView $b) {
            return $a->getName() > $b->getName();
        });

        return array_merge(
            [$userTimeline],
            $sharedTimelines
        );
    }

    public function getChunkedTimeline(?\DateTime $startDate, ?\DateTime $endDate, Usr $viewer, ?Useragent $useragent = null, bool $showDeleted = false): ?TimelineView
    {
        if (!isset($startDate) && !isset($endDate)) {
            return null;
        }

        if ($useragent) {
            if ($useragent->isFamilyMember()) {
                $isTimelineShared =
                    $useragent->isItinerariesSharedWith($viewer)
                    || (bool) $viewer->getTimelineShareWith($useragent->getAgentid(), $useragent);
            } else {
                $isTimelineShared = (bool) $viewer->getTimelineShareWith($useragent->getClientid());
            }

            if (!$isTimelineShared) {
                return null;
            }
        }

        $withPlans = $this->apiVersioning->supports(MobileVersions::TIMELINE_PLANS_ITEMS);
        $withParkings = $this->apiVersioning->supports(MobileVersions::TIMELINE_PARKINGS_ITEMS);
        $withCruiseLayouts = $this->apiVersioning->supports(MobileVersions::TIMELINE_CRUISE_LIST_ITEMS);

        $queryOptions = QueryOptions::createMobile();

        if (isset($startDate, $endDate)) {
            $queryOptions = $queryOptions
                ->setStartDate($startDate)
                ->setEndDate($endDate);
        } elseif (isset($startDate)) {
            $queryOptions = $queryOptions
                ->setStartDate($startDate)
                ->setMaxSegments(0);
        } elseif (isset($endDate)) {
            $queryOptions = $queryOptions
                ->setEndDate($endDate)
                ->setMaxSegments(50);
        }

        $queryOptions = $queryOptions
            ->setWithDetails(true)
            ->setUserAgent($useragent)
            ->setUser($viewer)
            ->setShowPlans($withPlans)
            ->addFilterCallback($this->getParkingsFilter($withParkings))
            ->addFilterCallback($this->getCruiseLayoversFilter($withCruiseLayouts))
            ->setShowDeleted($showDeleted)
            ->setFormatOptions(new FeaturesBitSet($this->getLoggedInFormatOptions()));

        return (new TimelineView())
            ->setItems($items = $this->manager->query($queryOptions))
            ->setNeedMore(
                (($startDateTs = $this->getStartDateTs($items)) !== null)
                && $this->manager->hasMoreBefore(new \DateTime('@' . $startDateTs), $viewer, $useragent, $withPlans, $showDeleted)
            );
    }

    /**
     * @param string[] $ids
     */
    public function getByItineraryIds(array $ids)
    {
        $queryOptions = QueryOptions::createMobile();
        $queryOptions = $queryOptions
            ->setWithDetails(true)
            ->setBareSegments(true)
            ->setShowPlans(false)
            ->setShowDeleted(true)
            ->setOperatedByResolver($this->operatedByResolver)
            ->setFormatOptions(new FeaturesBitSet(
                $this->getCommonFormatOptions() |
                Mobile\FormatHandler::CONFIRM_CHANGES
            ));

        return $this->manager->queryByItineraries($ids, $queryOptions);
    }

    public function getSharedTimelineItems($shareCode)
    {
        $showParkings = $this->apiVersioning->supports(MobileVersions::TIMELINE_PARKINGS_ITEMS);
        $withCruiseLayouts = $this->apiVersioning->supports(MobileVersions::TIMELINE_CRUISE_LIST_ITEMS);

        return $this->manager->queryByShareCode(
            $shareCode,
            QueryOptions::createMobile()
                ->setWithDetails(true)
                ->addFilterCallback($this->getParkingsFilter($showParkings))
                ->addFilterCallback($this->getCruiseLayoversFilter($withCruiseLayouts))
                ->setFormatOptions(new FeaturesBitSet(
                    $this->getCommonFormatOptions() |
                    $this->inversedFeatureToOption(MobileVersions::TIMELINE_PLANS_ITEMS, FormatHandler::FILTER_PLANS_ITEMS)
                ))
        );
    }

    public function getParkingsFilter(bool $showParkings): FilterCallbackInterface
    {
        if (!$showParkings) {
            return FilterCallback::make(
                fn (Item\ItemInterface $segment) => !($segment instanceof Item\ParkingStart) && !($segment instanceof Item\ParkingEnd),
                'no_parkings_mobile'
            );
        }

        return FilterCallback::pass();
    }

    public function getCruiseLayoversFilter(bool $showLayovers): FilterCallbackInterface
    {
        if (!$showLayovers) {
            return FilterCallback::make(
                fn (Item\ItemInterface $segment) => !($segment instanceof Item\CruiseLayover),
                'no_cruise_layovers_mobile'
            );
        }

        return FilterCallback::pass();
    }

    /**
     * @return list<array>
     */
    public function getTimelineStub(Usr $user): array
    {
        return [
            (new TimelineView())
            ->setName($user->getFullName())
            ->setItems([])
            ->setUserAgentId('my')
            ->setItineraryForwardEmail($user->getItineraryForwardingEmail())
            ->setNeedMore(false)
            ->setCanChange(true)
            ->setCanConnectMailbox(true),
        ];
    }

    /**
     * @param int $featureFlag
     * @param int $formatOption
     * @return int
     */
    protected function featureToOption($featureFlag, $formatOption)
    {
        return $this->apiVersioning->supports($featureFlag) ? $formatOption : 0;
    }

    /**
     * @param int $featureFlag
     * @param int $formatOption
     * @return int
     */
    protected function inversedFeatureToOption($featureFlag, $formatOption)
    {
        return (!$this->apiVersioning->supports($featureFlag)) ? $formatOption : 0;
    }

    protected function getStartDateTs(array $items)
    {
        if (
            // plan start should not be in previous chunk
            (
                isset($items[0], $items[0]->startDate->ts)
                && ($items[0] instanceof PlanItem)
                && (($startDateTs = $items[0]->startDate->ts) > 0)
            )
            || (
                isset($items[1], $items[1]->startDate->ts)
                && (($startDateTs = $items[0]->startDate->ts) > 0)
            )
        ) {
            return $startDateTs;
        }

        return null;
    }

    private function getLoggedInFormatOptions(): int
    {
        return
            $this->getCommonFormatOptions() |
            Mobile\FormatHandler::CONFIRM_CHANGES |
            $this->featureToOption(MobileVersions::TIMELINE_SAVINGS, FormatHandler::SAVINGS) |
            $this->featureToOption(MobileVersions::TIMELINE_SOURCES, FormatHandler::SOURCES) |
            $this->featureToOption(MobileVersions::LOUNGES, FormatHandler::LOUNGES) |
            $this->featureToOption(MobileVersions::NO_FOREIGN_FEES_CARDS, FormatHandler::NOT_FEES_CARDS) |
            $this->featureToOption(MobileVersions::ITINERARY_NOTE_FILES, FormatHandler::ITINERARY_FILES) |
            $this->featureToOption(MobileVersions::TIMELINE_SOURCES_TRIPIT, FormatHandler::SOURCES_TRIPIT) |
            $this->featureToOption(MobileVersions::MOVE_TRAVEL_SEGMENTS, FormatHandler::MOVE_TRAVEL_SEGMENTS) |
            $this->featureToOption(MobileVersions::LOUNGES_OFFLINE, FormatHandler::LOUNGES_OFFLINE);
    }

    private function tryAllocateMemory(Usr $user): void
    {
        $itemsCount = $this->calculateTimelineItemsCount($user);

        if ($itemsCount <= self::TIMELINE_ITEMS_MEMORY_INCREASE_THRESHOLD) {
            return;
        }

        $this->logger->info('Increasing memory limit for timeline items: ' . $itemsCount, [
            'items_count_int' => $itemsCount,
        ]);

        $memoryLimitStr = (\function_exists('ini_get') && \function_exists('ini_set')) ?
            \ini_get('memory_limit') :
            false;

        if (false === $memoryLimitStr || StringUtils::isEmpty($memoryLimitStr)) {
            $this->logger->info('Unable to increase memory limit for timeline items: memory limit is not set or ini_* functions are not available');

            return;
        }

        $memoryLimit = $this->convertMemoryLimitToBytes($memoryLimitStr);
        $increaseStepsCount = (int) \ceil(($itemsCount - self::TIMELINE_ITEMS_MEMORY_INCREASE_THRESHOLD) / self::TIMELINE_ITEMS_MEMORY_INCREASE_STEP);
        $additionalMemory = \min(self::MEMORY_MAX_INCREASE_STEPS, $increaseStepsCount) * self::MEMORY_INCREASE_STEP_BYTES;
        $newLimit = $memoryLimit + $additionalMemory;
        $this->logger->info("Increasing memory limit for timeline items: {$memoryLimitStr} + {$additionalMemory}B = {$newLimit}B");
        \ini_set('memory_limit', $newLimit);
    }

    private function calculateTimelineItemsCount(Usr $user): int
    {
        return
            it($this->ownerRepository->findAvailableOwners(
                OwnerRepository::FOR_ITINERARY_VIEW,
                $user,
                '',
                0
            ))
            ->map(fn (Owner $owner) => $this->manager->getSegmentCount(
                $owner->getUser(),
                $owner->getFamilyMember()
            ))
            ->sum();
    }

    private function getCommonFormatOptions(): int
    {
        return
            Mobile\FormatHandler::ALTERNATIVE_FLIGTHS |
            Mobile\FormatHandler::FLIGHT_STATUS |
            Mobile\FormatHandler::PARKINGS_ADS |
            $this->inversedFeatureToOption(MobileVersions::NATIVE_APP, FormatHandler::OUT_URL) |
            $this->featureToOption(MobileVersions::TIMELINE_OFFERS, Mobile\FormatHandler::OFFER) |
            $this->featureToOption(MobileVersions::TIMELINE_FLIGHT_PROGRESS, Mobile\FormatHandler::FLIGHT_PROGRESS) |
            $this->featureToOption(MobileVersions::TIMELINE_BLOCKS_V2, FormatHandler::DETAILS_BLOCKS_V2) |
            $this->featureToOption(MobileVersions::REGIONAL_SETTINGS, FormatHandler::REGIONAL_SETTINGS) |
            $this->featureToOption(MobileVersions::TIMELINE_TAXI_RIDE, FormatHandler::TAXI_RIDE) |
            $this->featureToOption(MobileVersions::DESANITIZED_STRINGS, FormatHandler::DESANITIZED_STRINGS) |
            $this->featureToOption(MobileVersions::TIMELINE_NO_SHOW_MORE, FormatHandler::NO_SHOW_MORE) |
            $this->featureToOption(MobileVersions::TIMELINE_DETAILED_AIRLINE_INFO, FormatHandler::DETAILED_ITINERARIES_V2_INFO) |
            $this->featureToOption(MobileVersions::TIMELINE_PARKINGS_ITEMS_ICON, FormatHandler::PARKINGS_ICON) |
            $this->featureToOption(MobileVersions::TIMELINE_CRUISE_LIST_ITEMS, FormatHandler::CRUISE_LIST_ITEMS) |
            $this->featureToOption(MobileVersions::TRAVEL_PLAN_DURATION, FormatHandler::TRAVEL_PLAN_DURATION) |
            $this->featureToOption(MobileVersions::TIMELINE_AI_WARNING, FormatHandler::AI_WARNING) |
            $this->featureToOption(MobileVersions::ITINERARY_NOTE_AND_FILES, FormatHandler::ITINERARY_NOTE_AND_FILES) |
            $this->featureToOption(MobileVersions::TIMELINE_TRIP_TITLE_POINT, FormatHandler::TRIP_TITLE_POINT) |
            $this->featureToOption(MobileVersions::TIMELINE_READABLE_TRIP_AND_RESTAURANT_DATES, FormatHandler::READABLE_TRIP_AND_RESTAURANT_DATES) |
            0;
    }

    private function convertMemoryLimitToBytes(string $memoryLimitStr): int
    {
        $memoryLimitStr = \trim($memoryLimitStr);
        $last = \strtolower($memoryLimitStr[\strlen($memoryLimitStr) - 1]);

        if (\is_numeric($last)) {
            return (int) $memoryLimitStr;
        }

        $memoryLimit = (int) \substr($memoryLimitStr, 0, -1);

        switch ($last) {
            case 'g':
                $memoryLimit *= 1024;

                // no break
            case 'm':
                $memoryLimit *= 1024;

                // no break
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit;
    }
}
