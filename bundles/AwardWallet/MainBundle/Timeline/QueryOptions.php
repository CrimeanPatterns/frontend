<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\ImmutableFluent;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\FilterCallback\FilterCallbackInterface;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Class QueryOptions.
 *
 * @method QueryOptions setWithDetails(bool $withDetails)
 * @method bool isWithDetails()
 * @method QueryOptions setShowDeleted(bool $show)
 * @method bool isShowDeleted()
 * @method QueryOptions setUser(Usr $user)
 * @method Usr getUser()
 * @method bool hasUser()
 * @method QueryOptions setUserAgent(Useragent $useragent)
 * @method Useragent getUserAgent()
 * @method bool hasUserAgent()
 * @method QueryOptions setFormat(string $format)
 * @method string getFormat()
 * @method bool hasFormat()
 * @method QueryOptions setFormatOptions(FeaturesBitSet $options)
 * @method FeaturesBitSet getFormatOptions()
 * @method bool hasFormatOptions()
 * @method FilterCallbackInterface getFilterCallback()
 * @method bool hasFilterCallback()
 * @method QueryOptions setMaxSegments(integer $maxSegments)
 * @method int getMaxSegments()
 * @method bool hasMaxSegments()
 * @method QueryOptions setMaxFutureSegments(integer $maxFutureSegments)
 * @method int getMaxFutureSegments()
 * @method bool hasMaxFutureSegments()
 * @method bool hasStartDate()
 * @method bool hasEndDate()
 * @method bool hasFuture()
 * @method bool getFuture()
 * @method QueryOptions setFuture(bool $future)
 * @method EntityManagerInterface getEntityManager()
 * @method EntityRepository getGeotags()
 * @method QueryOptions setItems(SegmentMapItem[] $items)
 * @method bool hasItems()
 * @method SegmentMapItem[] getItems()
 * @method QueryOptions setShareId(string $shareId)
 * @method bool hasShareId()
 * @method string getShareId()
 * @method QueryOptions setSharedPlan(Plan $sharedPlan)
 * @method Plan getSharedPlan()
 * @method bool hasSharedPlan()
 * @method QueryOptions setCacheTags(string[] $tags)
 * @method bool hasCacheTags()
 * @method string[] getCacheTags()
 * @method QueryOptions setShowPlans(bool $plans)
 * @method bool hasShowPlans()
 * @method bool isShowPlans()
 * @method bool getShowPlans()
 * @method QueryOptions setBareSegments(bool $bareSegments)
 * @method bool hasBareSegments()
 * @method bool isBareSegments()
 * @method bool getBareSegments()
 * @method QueryOptions setOperatedByResolver(OperatedByResolver $operatedByResolver)
 * @method OperatedByResolver getOperatedByResolver()
 */
class QueryOptions
{
    use ImmutableFluent;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var bool
     */
    private $withDetails;

    /**
     * return no more than this number of segments. may be a bit more because we will return full days.
     *
     * @var int
     */
    private $maxSegments;

    /**
     * @var string
     */
    private $format;

    /**
     * @var FilterCallbackInterface
     */
    private $filterCallback;

    /**
     * @var Useragent
     */
    private $userAgent;

    /**
     * @var Usr
     */
    private $user;

    /**
     * @var bool
     */
    private $future;

    /**
     * @var bool
     */
    private $showDeleted;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $geotags;

    /**
     * @var SegmentMapItem[]
     */
    private $items;

    /**
     * @var ?FeaturesBitSet
     */
    private $formatOptions;

    /**
     * @var bool
     */
    private $dateIntervalUTCSafe;

    /**
     * @var string
     */
    private $shareId;

    /**
     * @var Plan
     */
    private $sharedPlan;

    /**
     * Additional cache tags.
     *
     * @var string[]
     */
    private $cacheTags;

    /**
     * @var bool
     */
    private $showPlans;

    /**
     * @var OperatedByResolver
     */
    private $operatedByResolver;

    /**
     * @var bool
     */
    private $bareSegments;

    /**
     * @var int
     */
    private $maxFutureSegments;

    public function __construct()
    {
        $this->withDetails = false;
        $this->showDeleted = false;
        $this->showPlans = true;
        $this->bareSegments = false;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return null === $this->startDate ? null : clone $this->startDate;
    }

    /**
     * @return QueryOptions
     */
    public function setStartDate(?\DateTime $startDate = null)
    {
        if (null !== $startDate) {
            $startDate = clone $startDate;
        }

        if ($this->isLocked()) {
            $cloned = clone $this;
            $cloned->startDate = $startDate;

            return $cloned;
        } else {
            $this->startDate = $startDate;

            return $this;
        }
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return null === $this->endDate ? null : clone $this->endDate;
    }

    /**
     * @return QueryOptions
     */
    public function setEndDate(?\DateTime $endDate = null)
    {
        if (null !== $endDate) {
            $endDate = clone $endDate;
        }

        if ($this->isLocked()) {
            $cloned = clone $this;
            $cloned->endDate = $endDate;

            return $cloned;
        } else {
            $this->endDate = $endDate;

            return $this;
        }
    }

    /**
     * @return QueryOptions
     */
    public static function createDesktop()
    {
        return (new self())
            ->setFormat(ItemFormatterInterface::DESKTOP)
            ->setCacheTags(Tags::addTagPrefix([Tags::TAG_TIMELINE_DESKTOP]));
    }

    /**
     * @return QueryOptions
     */
    public static function createMobile()
    {
        return (new self())
            ->setFormat(ItemFormatterInterface::MOBILE)
            ->setCacheTags(Tags::addTagPrefix([Tags::TAG_TIMELINE_MOBILE]))
            ->setFormatOptions(new FeaturesBitSet(0));
    }

    /**
     * @param string $dateString time to shift by
     * @return QueryOptions
     */
    public function expandDateInterval($dateString = '3 day')
    {
        if ($this->isLocked()) {
            $queryOptions = clone $this;

            if (null !== $this->startDate) {
                $queryOptions->startDate = clone $this->startDate;
            }

            if (null !== $this->endDate) {
                $queryOptions->endDate = clone $this->endDate;
            }
        } else {
            $queryOptions = $this;
        }

        if (null !== $this->startDate) {
            $queryOptions->startDate->modify('-' . $dateString);
        }

        if (null !== $this->endDate) {
            $queryOptions->endDate->modify('+' . $dateString);
        }

        return $queryOptions;
    }

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        $this->geotags = $em->getRepository(Geotag::class);
    }

    public function noPersonalData()
    {
        return $this->hasShareId() || $this->hasSharedPlan();
    }

    public function addFilterCallback(FilterCallbackInterface $filterCallback): self
    {
        $newCallback = $this->filterCallback ?
            $this->filterCallback->and($filterCallback) :
            $filterCallback;

        if ($this->isLocked()) {
            $cloned = clone $this;
            $cloned->filterCallback = $newCallback;

            return $cloned;
        } else {
            $this->filterCallback = $newCallback;

            return $this;
        }
    }

    /**
     * @deprecated use {@link addFilterCallback} instead
     */
    public function setFilterCallback(FilterCallbackInterface $filterCallback): void
    {
        throw new \LogicException('Use addFilterCallback instead!');
    }
}
