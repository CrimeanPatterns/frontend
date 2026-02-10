<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Files\PlanFile;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\Date;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\PlanEnd;
use AwardWallet\MainBundle\Timeline\Item\PlanStart;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlanManager
{
    public const MAX_OFFSET = 28800; // 3600 * 8
    public const MAX_LENGTH_NOTES = 4000;

    /**
     * @var Manager
     */
    private $tlManager;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var PlanNameCreator
     */
    private $planNameCreator;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    private PlanFileManager $planFileManager;
    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        Manager $tlManager,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        PlanNameCreator $planNameCreator,
        PlanFileManager $planFileManager,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->tlManager = $tlManager;
        $this->em = $em;
        $this->validator = $validator;
        $this->planNameCreator = $planNameCreator;
        $this->tokenStorage = $tokenStorage;
        $this->planFileManager = $planFileManager;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @param int $startTime
     * @return Plan|null
     */
    public function create(?Useragent $agent = null, $startTime)
    {
        $segments = $this->getSegmentsForPlans($agent, $startTime);
        $user = $this->tokenStorage->getBusinessUser();
        $plans = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->findBy(['user' => $user, 'userAgent' => $agent]);

        if ($this->findMatchingPlan($plans, $startTime)) {
            return null;
        }

        $this->extractDay($segments, $startTime, $startIndex, $endIndex);
        $endIndex = $this->extendDayByConfNumbers($segments, $startIndex, $endIndex);
        $planStartTime = $this->getStartTime($segments, $startTime, $startIndex);
        $planEndTime = $this->getEndTime($segments, $startTime, $endIndex);

        if ($planStartTime > $startTime || $planEndTime <= $planStartTime) {
            return null;
        }

        $plan = new Plan();
        $plan
            ->setStartDate(new \DateTime('@' . $planStartTime))
            ->setEndDate(new \DateTime('@' . $planEndTime))
            ->setUser($user)
            ->setUserAgent($agent)
            ->setShareCode(RandomStr(ord('a'), ord('z'), 32))
            ->setName($this->planNameCreator->generateName(array_slice($segments, $startIndex, $endIndex - $startIndex + 1)));
        $this->em->persist($plan);
        $this->em->flush();

        return $plan;
    }

    /**
     * @param int $startTime
     * @return Plan|null
     */
    public function createShared(TimelineShare $timelineShare, $startTime)
    {
        $user = $this->tokenStorage->getBusinessUser();
        $segments = $this->getSegmentsForPlans($timelineShare->getUserAgent(), $startTime, $timelineShare);
        $plans = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Plan::class)->findBy(['user' => $timelineShare->getTimelineOwner(), 'userAgent' => $timelineShare->getFamilyMember()]);

        if ($this->findMatchingPlan($plans, $startTime)) {
            return null;
        }

        $this->extractDay($segments, $startTime, $startIndex, $endIndex);
        $endIndex = $this->extendDayByConfNumbers($segments, $startIndex, $endIndex);
        $planStartTime = $this->getStartTime($segments, $startTime, $startIndex);
        $planEndTime = $this->getEndTime($segments, $startTime, $endIndex);

        if ($planStartTime > $startTime || $planEndTime <= $planStartTime) {
            return null;
        }

        $plan = new Plan();
        $plan
            ->setStartDate(new \DateTime('@' . $planStartTime))
            ->setEndDate(new \DateTime('@' . $planEndTime))
            ->setUser($timelineShare->getTimelineOwner())
            ->setUserAgent($timelineShare->getFamilyMember())
            ->setShareCode(RandomStr(ord('a'), ord('z'), 32))
            ->setName($this->planNameCreator->generateName(array_slice($segments, $startIndex, $endIndex - $startIndex + 1)));
        $this->em->persist($plan);
        $this->em->flush();

        return $plan;
    }

    public function delete(Plan $plan)
    {
        foreach ($plan->getFiles() as $planFile) {
            $this->planFileManager->removeFile($planFile);
        }

        $this->logger->info(sprintf('plan #%d will be deleted, userId: %d', $plan->getId(), $plan->getUser()->getId()));
        $this->em->remove($plan);
        $this->em->flush();
    }

    public function rename(Plan $plan, $name)
    {
        $plan->setName($name);
        $errors = $this->validator->validate($plan);

        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->em->persist($plan);
        $this->em->flush();
    }

    public function move(Plan $plan, $timestamp, $type, $segmentId)
    {
        /** @var ItemInterface[] $segments */
        $segments = $this->getSegmentsRaw($plan->getUser(), $plan->getUserAgent(), $timestamp);
        $startDateBefore = $plan->getStartDate()->getTimestamp();
        $endDateBefore = $plan->getEndDate()->getTimestamp();

        if ($type == 'planStart') {
            $plan->setStartDate(new \DateTime('@' . $this->getStartTime($segments, $timestamp, $this->findIndexBySegmentId($segments, $segmentId))));
        } else {
            $plan->setEndDate(new \DateTime('@' . $this->getEndTime($segments, $timestamp, $this->findIndexBySegmentId($segments, $segmentId))));
        }

        $this->em->persist($plan);
        $this->em->flush();
        $this->logger->info(sprintf(
            'plan #%d was moved, userId: %d, start: "%s" -> "%s", end: "%s" -> "%s"',
            $plan->getId(),
            $plan->getUser()->getId(),
            date('Y-m-d H:i:s', $startDateBefore),
            $plan->getStartDate()->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', $endDateBefore),
            $plan->getEndDate()->format('Y-m-d H:i:s')
        ));
    }

    public function attachFile(Plan $plan, UploadedFile $file, Usr $user): PlanFile
    {
        $this->planFileManager->baseValidate($file);

        $planFile = $this->planFileManager->saveUploadedFile($file, $user, PlanFile::class);
        $planFile->setPlan($plan);
        $this->em->persist($planFile);

        $plan->addFile($planFile);
        $this->updateEntity($plan);

        return $planFile;
    }

    public function updateNoteText(Plan $plan, string $text): bool
    {
        $text = StringHandler::cleanHtmlText($text);

        if (mb_strlen($text) > self::MAX_LENGTH_NOTES) {
            throw new \LengthException($this->translator->trans('text-is-too-big'));
        }

        $plan->setNotes(self::cleanBlankLineInEndText($text));
        $this->updateEntity($plan);

        return true;
    }

    public static function cleanBlankLineInEndText(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        $combination = ['<p><\/p>', '<p><br><\/p>', '<p><br\/><\/p>', '<p>&nbsp;<\/p>', '<br>', '<br\/>', '&nbsp;'];
        $pattern = implode('|', $combination);
        $text = preg_replace("/({$pattern})+$/m", '', trim($text));

        return trim($text);
    }

    public function updateEntity(Plan $plan): void
    {
        $this->em->persist($plan);
        $this->em->flush();

        $this->em->refresh($plan);
    }

    /**
     * @param ItemInterface[] $segments
     */
    private function findIndexBySegmentId(array $segments, $segmentId)
    {
        $result = null;

        foreach ($segments as $index => $segment) {
            if ($segment->getId() == $segmentId) {
                $result = $index;

                break;
            }
        }

        return $result;
    }

    /**
     * @param ItemInterface[] $segments
     */
    private function extractDay(array $segments, $startTime, &$startIndex, &$endIndex)
    {
        for ($n = 0; $n < count($segments); $n++) {
            $item = $segments[$n];
            $itemTime = $item->getStartDate()->getTimestamp();

            if ($itemTime >= $startTime) {
                if (!isset($startIndex)) {
                    $startIndex = $n;
                }

                if ($itemTime > $startTime && ($item instanceof Date || $item instanceof PlanStart || $item instanceof PlanEnd)) {
                    break;
                }
            }
        }

        if (!isset($startIndex)) {
            $startIndex = 0;
        }
        $endIndex = $n - 1;
    }

    /**
     * @param ItemInterface[] $segments
     * @return int
     */
    private function extendDayByConfNumbers($segments, $startIndex, $endIndex)
    {
        $itineraries = [];

        for ($n = $startIndex; $n <= $endIndex; $n++) {
            $segment = $segments[$n];

            if ($segment instanceof AbstractItinerary && !empty($itinerary = $segment->getItinerary())) {
                $itineraries[] = $itinerary->getKind() . $itinerary->getId();

                if (!empty($itinerary->getConfirmationNumber())) {
                    $itineraries[] = $itinerary->getConfirmationNumber();
                }
            }
        }

        for ($n = $endIndex + 1; $n < count($segments); $n++) {
            $segment = $segments[$n];

            if ($segment instanceof PlanStart || $segment instanceof PlanEnd) {
                break;
            }

            if ($segment instanceof AbstractItinerary && !empty($itinerary = $segment->getItinerary())
            && (in_array($itinerary->getKind() . $itinerary->getId(), $itineraries) || in_array($itinerary->getConfirmationNumber(), $itineraries))) {
                $endIndex = $n;
            }
        }

        return $endIndex;
    }

    /**
     * @param ItemInterface[] $segments
     */
    private function getStartTime(array $segments, $startTime, $startIndex)
    {
        // middle between first segment and previous segment
        if ($startIndex > 0 && $segments[$startIndex]->getStartDate()->getTimestamp() == $startTime) {
            $prevStartTime = $segments[$startIndex - 1]->getStartDate()->getTimestamp();
            $planStartTime = round(($startTime + $prevStartTime) / 2);
        }

        if (empty($planStartTime) || ($startTime - $planStartTime) > self::MAX_OFFSET) {
            $planStartTime = $startTime - self::MAX_OFFSET;
        }

        return $planStartTime;
    }

    /**
     * @param ItemInterface[] $segments
     * @return float|int
     */
    private function getEndTime(array $segments, $startTime, $endIndex)
    {
        if (isset($segments[$endIndex])) {
            $lastItemTime = $segments[$endIndex]->getStartDate()->getTimestamp();
        } else {
            $lastItemTime = $startTime;
        }

        if ($endIndex < (count($segments) - 1) && count($segments)) {
            $nextItemTime = $segments[$endIndex + 1]->getStartDate()->getTimestamp();
            $planEndTime = round(($lastItemTime + $nextItemTime) / 2);
        }

        if (empty($planEndTime) || $planEndTime > ($lastItemTime + self::MAX_OFFSET)) {
            $planEndTime = $lastItemTime + self::MAX_OFFSET;
        }

        return $planEndTime;
    }

    /**
     * @param Plan[] $plans
     * @param int $time
     * @return Plan|null
     */
    private function findMatchingPlan(array $plans, $time)
    {
        foreach ($plans as $plan) {
            if ($time >= $plan->getStartDate()->getTimestamp() && $time < $plan->getEndDate()->getTimestamp()) {
                return $plan;
            }
        }

        return null;
    }

    private function getSegmentsForPlans(?Useragent $agent = null, $startTime, $timelineShare = null): array
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();

        if (!empty($timelineShare) && $timelineShare instanceof TimelineShare) {
            $agent = $timelineShare->getFamilyMember() ?: $timelineShare->getUserAgent();
        }

        return $this->getSegmentsRaw($user, $agent, $startTime);
    }

    /**
     * @return ItemInterface[]
     */
    private function getSegmentsRaw(Usr $user, ?Useragent $useragent, $startTime): array
    {
        $options = new QueryOptions();
        $options->setUser($user);
        $options->setUserAgent($useragent);
        // we will try to load future segments to use cache
        $futureStart = $this->tlManager->getUserStartDate($user);

        if ($futureStart->getTimestamp() <= $startTime) {
            $options->setFuture(true);
        } else {
            $options->setStartDate(new \DateTime('@' . $startTime));
            $options->setMaxSegments(50);
        }

        return $this->tlManager->query($options);
    }
}
