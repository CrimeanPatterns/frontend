<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\LooseConditionsException;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\ItineraryMatcherInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ConstructException;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ItinerarySchema2EntityConverterInterface;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class ItineraryProcessor implements ItineraryProcessorInterface
{
    protected string $supportedClass;

    protected ItineraryRepositoryInterface $repository;

    protected EntityManagerInterface $entityManager;

    protected LoggerInterface $logger;

    protected ItinerarySchema2EntityConverterInterface $converter;

    private ItineraryMatcherInterface $matcher;

    private ItineraryTracker $tracker;

    private NamesMatcher $namesMatcher;

    private EventDispatcherInterface $eventDispatcher;
    private DoctrineRetryHelper $doctrineRetryHelper;

    /**
     * ItineraryProcessor constructor.
     */
    public function __construct(
        string $supportedClass,
        ItineraryRepositoryInterface $repository,
        ItinerarySchema2EntityConverterInterface $converter,
        ItineraryMatcherInterface $matcher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ItineraryTracker $tracker,
        NamesMatcher $namesMatcher,
        EventDispatcherInterface $eventDispatcher,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        $this->supportedClass = $supportedClass;
        $this->repository = $repository;
        $this->converter = $converter;
        $this->matcher = $matcher;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->tracker = $tracker;
        $this->namesMatcher = $namesMatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineRetryHelper = $doctrineRetryHelper;
    }

    /**
     * @param \AwardWallet\Schema\Itineraries\Itinerary $schemaItinerary
     */
    public function process($schemaItinerary, SavingOptions $options): ProcessingReport
    {
        if (!$schemaItinerary instanceof $this->supportedClass) {
            return new ProcessingReport();
        }

        try {
            $candidates = $this->repository->findMatchingCandidates(
                $options->getOwner()->getUser(),
                $schemaItinerary
            );

            // find timelines with full access
            /** @var TimelineShare[] $timelines */
            $timelines = $this->entityManager->createQuery("
                SELECT share
                FROM AwardWallet\MainBundle\Entity\TimelineShare share
                JOIN share.userAgent connection
                JOIN share.familyMember familyMember
                JOIN share.timelineOwner timelineOwner
                WHERE 
                    share.recipientUser = :user
                    AND connection.isapproved = true
                    AND connection.tripAccessLevel = :access
            ")->execute([
                'user' => $options->getOwner()->getUser(),
                'access' => Useragent::TRIP_ACCESS_FULL_CONTROL,
            ]);

            // get owners of timelines with full access
            /** @var Owner[] $timelineFullAccessOwners */
            $timelineFullAccessOwners = array_map(function (TimelineShare $timeline) {
                return OwnerRepository::getByTimelineShare($timeline);
            }, $timelines);

            // filter owners by name
            $timelineFullAccessOwners = array_filter($timelineFullAccessOwners, function (Owner $owner) use ($schemaItinerary) {
                return $this->namesMatcher->match($schemaItinerary->getPersons(), [$owner->getFullName()]);
            });

            // log owners of timelines with full access
            if (count($timelineFullAccessOwners) > 0) {
                $this->logger->info(sprintf(
                    'found %d timelines with full access, owners: %s',
                    count($timelineFullAccessOwners),
                    implode(", ", array_map(function (Owner $owner) {
                        if ($owner->isFamilyMember()) {
                            return sprintf('%s (#%d, %s)', $owner->getFullName(), $owner->getFamilyMember()->getId(), 'family member');
                        } else {
                            return sprintf('%s (#%d)', $owner->getFullName(), $owner->getUser()->getId());
                        }
                    }, $timelineFullAccessOwners))
                ));
            }
        } catch (LooseConditionsException $e) {
            $this->logger->warning($e->getMessage(), ['itinerary' => $schemaItinerary, 'options' => $options]);

            return new ProcessingReport();
        }
        $matches = $this->findBestMatches($schemaItinerary, $candidates, $options->isInitializedByUser());
        $this->logger->info(sprintf(
            'saving itinerary, candidates: %d, matches: %d, candidates ids: %s, matches ids: %s',
            count($candidates),
            count($matches),
            $this->itineraryIds($candidates),
            $this->itineraryIds($matches)
        ),
            [
                'schemaItinerary' => $schemaItinerary,
                'isInitializedByUser' => $options->isInitializedByUser(),
                'isPartialUpdate' => $options->isPartialUpdate(),
                'isSilent' => $options->isSilent(),
            ]
        );
        $oldProperties = [];
        $result = new ProcessingReport();

        // update master (owner) itinerary and copies
        $persons = $schemaItinerary->getPersons();
        $ownerItinerary = null;
        $moved = false;

        if (!empty($matches)) {
            $matches = $this->removeDuplicates($matches);

            foreach ($matches as $processingItinerary) {
                if ($options->getOwner()->isSame($processingItinerary->getOwner())) {
                    // owner itinerary, will update
                    $ownerItinerary = $processingItinerary;
                } else {
                    // copy, or gathered from another source. update only if traveler name matched
                    if (!$this->namesMatcher->match($persons, $processingItinerary->getTravelerNames())) {
                        $this->logger->info("skipping " . $processingItinerary->getId() . ", names do not match to " . implode(", ", $processingItinerary->getTravelerNames()));

                        continue;
                    }
                }

                // When updating by account - using account tracker in AccountProcessor
                if (!$options->getAccount()) {
                    $oldProperties = $this->tracker->getProperties($processingItinerary->getIdString());
                }

                // update manually modified itineraries - https://redmine.awardwallet.com/issues/17466#note-5
                //            if ($processingItinerary->getModified() && !$processingItinerary->getHidden()) {
                //                $this->logger->warning("itinerary {$processingItinerary->getKind()} {$processingItinerary->getId()} is modified, will not update");
                //                return new ProcessingReport();
                //            }
                try {
                    $this->converter->convert($schemaItinerary, $processingItinerary, $options);

                    if ($schemaItinerary->cancelled) {
                        $report = new ProcessingReport([], [], [$processingItinerary]);
                    } else {
                        $report = new ProcessingReport([], [$processingItinerary], []);
                    }
                    $result = $result->merge($report);
                    $this->finishItineraryUpdate($processingItinerary, $options, $oldProperties);

                    if ($processingItinerary->getMoved()) {
                        $moved = true;
                    }
                } catch (ConstructException $e) {
                    $this->logger->warning($e->getMessage(), ['itinerary' => $schemaItinerary, 'options' => $options]);
                }
            }
        }

        if ($ownerItinerary === null && !$options->isUpdateOnly() && !$moved && empty($schemaItinerary->cancelled)) {
            try {
                $processingItinerary = $this->converter->convert($schemaItinerary, null, $options);
                $matches[] = $processingItinerary;
                $this->entityManager->persist($processingItinerary);
                $result = $result->merge(new ProcessingReport([$processingItinerary], [], []));
                $this->finishItineraryUpdate($processingItinerary, $options, $oldProperties);
            } catch (ConstructException $e) {
                $this->logger->warning($e->getMessage(), [['itinerary' => $schemaItinerary, 'options' => $options]]);

                return new ProcessingReport();
            }
        }

        //        if (empty($schemaItinerary->cancelled) && count($matches) > 0) {
        //            $this->unhideAtleastOne($matches);
        //        }

        return $result;
    }

    private function itineraryIds(array $itineraries): string
    {
        return implode(", ", array_map(function (Itinerary $itinerary) {
            return $itinerary->getId();
        }, $itineraries));
    }

    private function finishItineraryUpdate(Itinerary $processingItinerary, SavingOptions $options, array $oldProperties)
    {
        $processingItinerary->setLastParseDate(new \DateTime());
        $processingItinerary->setParsed(true);

        if (null !== $options->getConfirmationFields()) {
            $processingItinerary->setConfFields($options->getConfirmationFields());
        }
        // Without this flush tracker won't notice be able to pull diff from database
        $this->doctrineRetryHelper->execute(function () {
            $this->entityManager->flush();
        });
        $this->eventDispatcher->dispatch(new ItinerarySavedEvent($processingItinerary), ItinerarySavedEvent::NAME);
        $this->eventDispatcher->dispatch(new ItineraryUpdateEvent($processingItinerary));

        if (!$options->getAccount()) {
            $this->tracker->recordChanges(
                $oldProperties,
                $processingItinerary->getIdString(),
                $options->getOwner()->getUser()->getUserid(),
                false,
                $options->isSilent()
            );
        }
    }

    /**
     * @return EntityItinerary[]
     */
    private function findBestMatches($schemaItinerary, array $candidates, bool $initializedByUser): array
    {
        $result = [];

        $currentConfidence = 0;
        $bestMatch = null;

        foreach ($candidates as $candidate) {
            /** @var Itinerary $candidate */
            $confidence = $this->matcher->match($candidate, $schemaItinerary);
            $this->logger->warning("candidate {$candidate->getIdString()} confidence: {$confidence}");

            if ($confidence >= 0.99) {
                $result[] = $candidate;

                continue;
            }

            if ($confidence > $currentConfidence) {
                $bestMatch = $candidate;
                $currentConfidence = $confidence;
            }
        }

        if (empty($result) && $bestMatch !== null) {
            $result[] = $bestMatch;
        }

        return $result;
    }

    /**
     * @param Itinerary[] $matches
     */
    private function removeDuplicates(array $matches)
    {
        $timelines = [];

        foreach ($matches as $itinerary) {
            $key = $itinerary->getOwnerId();

            if (!isset($timelines[$key])) {
                $timelines[$key] = [];
            }
            $timelines[$key][] = $itinerary;
        }

        $toRemove = [];

        foreach ($timelines as $timeline) {
            if (count($timeline) === 1) {
                continue;
            }

            usort($timeline, function (Itinerary $a, Itinerary $b) {
                if ($a->getHidden() !== $b->getHidden()) {
                    if ($a->getHidden()) {
                        return -1;
                    }

                    return 1;
                }

                return $a->getCreateDate()->getTimestamp() <=> $b->getCreateDate()->getTimestamp();
            });
            /** @var Itinerary $winner */
            $winner = array_pop($timeline);

            foreach ($timeline as $itinerary) {
                /** @var Itinerary $itinerary */
                if ($itinerary === $winner) {
                    continue;
                }
                $this->logger->warning("removing " . $itinerary->getIdString() . " as duplicate of " . $winner->getIdString());
                $toRemove[] = $itinerary;
            }
        }

        if (count($toRemove) > 0) {
            foreach ($toRemove as $itinerary) {
                $this->entityManager->remove($itinerary);
            }
            $this->entityManager->flush();
        }

        return array_filter($matches, function (Itinerary $itinerary) use ($toRemove) {
            return !in_array($itinerary, $toRemove, true);
        });
    }

    /**
     * @param Itinerary[] $matches
     */
    private function unhideAtleastOne(array $matches)
    {
        $allHidden = array_reduce($matches, function ($carry, Itinerary $itinerary) {
            return $carry && $itinerary->getHidden();
        }, true);

        if ($allHidden) {
            usort($matches, function (Itinerary $a, Itinerary $b) {
                return (int) $a->getCopied() <=> (int) $b->getCopied();
            });
            $itinerary = array_shift($matches);
            $this->logger->warning("all matches were hidden (" . implode(", ", array_map(function (Itinerary $itinerary) {
                return $itinerary->getIdString();
            }, $matches)) . "), unhiding one: " . $itinerary->getIdString());
            $itinerary->setHidden(false);
            $this->entityManager->flush();
        }
    }
}
