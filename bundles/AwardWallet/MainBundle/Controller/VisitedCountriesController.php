<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\VisitedCountries\Exporter;
use AwardWallet\MainBundle\Service\VisitedCountries\Reporter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class VisitedCountriesController
{
    private Exporter $exporter;

    private Reporter $reporter;

    private TokenStorageInterface $tokenStorage;

    private UseragentRepository $uaRep;

    private PageVisitLogger $pageVisitLogger;

    public function __construct(
        Exporter $exporter,
        Reporter $reporter,
        TokenStorageInterface $tokenStorage,
        UseragentRepository $uaRep,
        PageVisitLogger $pageVisitLogger
    ) {
        $this->exporter = $exporter;
        $this->reporter = $reporter;
        $this->tokenStorage = $tokenStorage;
        $this->uaRep = $uaRep;
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * @Route("/report/visited_countries.xls", name="timeline_export_countries", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function exportAction(Request $request)
    {
        /** @var Usr $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $agentId = $request->get('agentId');

        if (!is_numeric($agentId)) {
            $agent = null;
        } else {
            $agent = $this->uaRep->find($agentId);

            if (empty($agent)) {
                throw new AccessDeniedException('Unknown user agent');
            }

            if ($agent->getAgentid()->getId() !== $user->getId() || !$agent->isFamilyMember()) {
                if ($agent->isFamilyMember()) {
                    $timelineShare = $user->getTimelineShareWith($agent->getAgentid(), $agent);
                } else {
                    $timelineShare = $user->getTimelineShareWith($agent->getClientid());
                }

                if (empty($timelineShare)) {
                    throw new AccessDeniedException('Unknown user agent');
                }
            }
        }

        $after = $request->get('after');

        if (!is_numeric($after)) {
            $after = null;
        } else {
            $after = new \DateTime('@' . intval($after));
        }

        $before = $request->get('before');

        if (!is_numeric($before)) {
            $before = null;
        } else {
            $before = new \DateTime('@' . intval($before));
        }

        $timeline = $this->reporter->getCountries($user, $agent, $after, $before);
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_LIST_OF_VISITED_COUNTRIES);

        return $this->exporter->export(
            $agent ? $agent->getFullName() : $user->getFullName(),
            $timeline
        );
    }
}
