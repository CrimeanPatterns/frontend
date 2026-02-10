<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\ReservationChanged;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\ReservationNew;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Timeline\NoForeignFeesCardsQuery;
use AwardWallet\MainBundle\Timeline\Util\ItineraryUtil;
use Doctrine\ORM\EntityManager;

class Task
{
    protected $template;

    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var Useragent
     */
    protected $familyMember;

    /**
     * @var bool
     */
    protected $isCopy = false;

    /**
     * @var Itinerary[]
     */
    protected $itineraries = [];

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var AdManager
     */
    protected $adManager;

    /**
     * @var EmailScannerApi
     */
    protected $scannerApi;

    protected NoForeignFeesCardsQuery $noForeignFeesCardsQuery;

    protected $noForeignFeesCards;

    public function __construct($template, Usr $user, ?Useragent $ua = null, $isCopy = false, NoForeignFeesCardsQuery $noForeignFeesCardsQuery)
    {
        $this->template = $template;
        $this->user = $user;
        $this->familyMember = $ua;
        $this->isCopy = $isCopy;
        $this->noForeignFeesCardsQuery = $noForeignFeesCardsQuery;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function setAdManager(AdManager $adManager)
    {
        $this->adManager = $adManager;
    }

    public function setScannerApi(EmailScannerApi $scannerApi)
    {
        $this->scannerApi = $scannerApi;
    }

    /**
     * @return Itinerary[]
     */
    public function getItineraries()
    {
        return $this->itineraries;
    }

    /**
     * @param Itinerary[] $itineraries
     */
    public function setItineraries($itineraries)
    {
        $this->itineraries = $itineraries;
    }

    public function addItinerary(Itinerary $it)
    {
        $this->itineraries[] = $it;
    }

    /**
     * @return ReservationChanged|ReservationNew
     */
    public function getEmailTemplate()
    {
        /** @var AbstractTemplate $template */
        if ($this->template == Sender::NEW_RESERVATION) {
            $template = new ReservationNew();
        } else {
            $template = new ReservationChanged();
        }

        if (isset($this->familyMember)) {
            if ($this->isCopy) {
                $template->toUser($this->user, false);
                $template->originalRecipient = $this->familyMember;
                // Mailbox offer
                $template->hasMailbox = $this->hasMailboxes($this->user);
            } else {
                $template->toFamilyMember($this->familyMember);
            }
        } else {
            $template->toUser($this->user, false);
            // Mailbox offer
            $template->hasMailbox = $this->hasMailboxes($this->user);
        }

        // Its
        $this->formatItineraries();
        $template->itineraries = $this->itineraries;
        // Ad
        $template->advt = $this->getAd($template->getEmailKind());

        $template->noForeignFeesCards = $this->noForeignFeesCards;

        return $template;
    }

    protected function hasMailboxes(Usr $user)
    {
        try {
            return count($this->scannerApi->listMailboxes(["user_" . $user->getUserid()])) > 0;
        } catch (\Exception $e) {
            // do not show offer
            return true;
        }
    }

    protected function getAd($kind)
    {
        $providers = [];

        foreach ($this->itineraries as $it) {
            $p = $it->getEntity()->getProvider();

            if ($p && !isset($providers[$p->getProviderid()])) {
                $providers[$p->getProviderid()] = $p;
            }
        }
        $opt = new Options(ADKIND_EMAIL, $this->user, $kind);
        $opt->providers = $providers;

        return $this->adManager->getAdvt($opt);
    }

    protected function formatItineraries()
    {
        $minStartDateTime = new \DateTime("-" . TRIPS_PAST_DAYS . " DAY");

        foreach ($this->itineraries as $k => $itinerary) {
            try {
                $rep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Itinerary::getItineraryClass($itinerary->getData()['Kind']));
                /** @var EntityItinerary $entity */
                $entity = $rep->find($itinerary->getData()['ID']);
                $itinerary->setEntity($entity);
                $changeDate = null;

                if ($this->template == Sender::UPDATE_RESERVATION) {
                    /** @var \DateTime $updateDate */
                    $changeDate = $itinerary->getChangeDate();

                    if (!$changeDate) {
                        throw new \LogicException("Change date is empty");
                    }
                    $changeDate = $changeDate->modify("-180 seconds");
                }

                if ($entity instanceof Trip) {
                    $segments = [];
                    $isOverseasTravel = false;

                    foreach ($entity->getSegmentsSorted() as $tripSegment) {
                        if ($tripSegment->getDepartureDate() && $tripSegment->getDepartureDate() > $minStartDateTime) {
                            $segments = array_merge($segments, $this->formatter->format($tripSegment, $changeDate, $this->user->getLocale(), $this->user->getLanguage()));
                        }

                        if (!$isOverseasTravel) {
                            $isOverseasTravel = ItineraryUtil::isOverseasTravel($tripSegment->getGeoTags());
                        }
                    }

                    if (count($segments) > 0) {
                        $itinerary->setSegments($segments);
                    } else {
                        unset($this->itineraries[$k]);
                    }

                    if (null === $this->noForeignFeesCards && $isOverseasTravel) {
                        $this->noForeignFeesCards = $this->noForeignFeesCardsQuery->getCards($this->user->getId());
                    }
                } else {
                    $itinerary->setSegments($this->formatter->format($entity, $changeDate, $this->user->getLocale(), $this->user->getLanguage()));
                }
            } catch (\LogicException $e) {
                unset($this->itineraries[$k]);
            }
        }
    }
}
