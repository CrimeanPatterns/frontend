<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary\CheckIn;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Service\ItineraryMail\Formatter;
use AwardWallet\MainBundle\Service\ItineraryMail\Itinerary;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @deprecated use FlightEmailAlertConsumer instead
 */
class SendEmailExecutor implements ExecutorInterface
{
    private EntityManagerInterface $em;

    private TripsegmentRepository $tsRep;

    private UsrRepository $userRep;

    private UseragentRepository $userAgentRep;

    private ProviderRepository $providerRep;

    private Mailer $mailer;

    private AdManager $adManager;

    private Formatter $formatter;

    public function __construct(EntityManagerInterface $em, Mailer $mailer, AdManager $adManager, Formatter $formatter)
    {
        $this->em = $em;
        $this->tsRep = $this->em->getRepository(Tripsegment::class);
        $this->userRep = $this->em->getRepository(Usr::class);
        $this->userAgentRep = $this->em->getRepository(Useragent::class);
        $this->providerRep = $this->em->getRepository(Provider::class);
        $this->mailer = $mailer;
        $this->adManager = $adManager;
        $this->formatter = $formatter;
    }

    /**
     * @param SendEmailTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $response = new Response();
        $userAgent = null;
        $provider = null;
        $ts = $this->tsRep->find($task->getTripSegmentId());
        $user = $this->userRep->find($task->getUserId());

        if (is_null($user) || is_null($ts)) {
            return $response;
        }

        if (!empty($task->getUserAgentId())) {
            $userAgent = $this->userAgentRep->find($task->getUserAgentId());
        }

        if (!empty($task->getProviderId())) {
            $provider = $this->providerRep->find($task->getProviderId());
        }

        $template = new CheckIn();

        if ($userAgent) {
            if ($task->isCopy()) {
                $template->toUser($user, false);
                $template->originalRecipient = $userAgent;
            } else {
                $template->toFamilyMember($userAgent);
            }
        } else {
            $template->toUser($user, false);
        }

        $template->advt = $this->getAd($user, $provider);

        // Itinerary
        $it = new Itinerary();
        $it->setEntity($ts->getTripid());
        $it->setSegments($this->formatter->format($ts, null, $user->getLocale(), $user->getLanguage()));
        $template->itineraries = [$it];

        $message = $this->mailer->getMessageByTemplate($template);
        $opts = [];

        if (!is_null($field = NotificationDate::getField($task->getKind()))) {
            $opts[Mailer::OPTION_ON_SUCCESSFUL_SEND] = function () use ($ts, $field) {
                $this->em->getConnection()->update(
                    'TripSegment',
                    [$field => (new \DateTime())->format('Y-m-d H:i:s')],
                    ['TripSegmentID' => $ts->getTripsegmentid()]
                );
            };
        }

        $this->mailer->send($message, $opts);

        return $response;
    }

    private function getAd(Usr $user, ?Provider $provider = null)
    {
        $opt = new Options(ADKIND_EMAIL, $user, CheckIn::getEmailKind());

        if ($provider) {
            $opt->providers = [$provider];
        }

        return $this->adManager->getAdvt($opt);
    }
}
