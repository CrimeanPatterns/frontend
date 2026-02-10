<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Type;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccessGrantedHelper;
use AwardWallet\MainBundle\Timeline\Manager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MembersController extends AbstractController
{
    private AccessGrantedHelper $accessGrantHelper;
    private $user;
    private $em;
    private \Memcached $memcached;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        AccessGrantedHelper $accessGrantHelper,
        ContainerInterface $container,
        \Memcached $memcached
    ) {
        $this->user = $tokenStorage->getBusinessUser();
        $this->container = $container;
        $this->em = $em;
        $this->accessGrantHelper = $accessGrantHelper;
        $this->memcached = $memcached;

        LocalizeService::defineDateTimeFormat();
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/members", name="aw_business_members", options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Members/membersList.html.twig")
     */
    public function membersListAction(Request $request)
    {
        $business = $this->user;
        /** @var EntityManager $em */
        $em = $this->em;
        $rep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $members = $rep->getBusinessMembersData($business);

        foreach ($members as $key => $member) {
            if (isset($member['Name'])) {
                $members[$key]['Name'] = htmlspecialchars_decode($member['Name']);
            }

            if ($member['type'] == 0) {
                $member['Name'] = '';
                unset($member['Programs']);
                unset($member['abRequests']);
                unset($member['PlusExpirationDate']);
                unset($member['AccountLevel']);
                $members[$key] = $member;
            } else {
                $cacheKey = 'business_member_trips_' . $member['UserAgentID'];
                $data = $this->memcached->get($cacheKey);
                $tripsCount = $data === false ? false : @json_decode($data, true)['trips'];
                $members[$key]['Trips'] = $tripsCount;
            }
        }

        $adminAccess = [
            'ACCESS_ADMIN' => ACCESS_ADMIN,
            'ACCESS_BOOKING_MANAGER' => ACCESS_BOOKING_MANAGER,
            'ACCESS_BOOKING_REFERRAL' => ACCESS_BOOKING_VIEW_ONLY,
        ];

        return [
            'adminAccess' => $adminAccess,
            'membersData' => $members,
        ];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/members/trip-totals", name="aw_trip_totals", methods={"POST"}, options={"expose"=true})
     */
    public function getTripTotalsAction(Request $request, Manager $manager)
    {
        $em = $this->em;
        /** @var UseragentRepository $uaRep */
        $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $business = $this->user;

        $data = json_decode($request->getContent());
        $members = $data->user_ids;

        $result = [];

        foreach ($members as $member) {
            /** @var Useragent $userAgent */
            $userAgent = $uaRep->find($member->LinkUserAgentID);

            if ($userAgent && $userAgent->isItinerariesSharedWith($business)) {
                $cacheKey = 'business_member_trips_' . $member->LinkUserAgentID;
                $data = $this->memcached->get($cacheKey);

                if ($data === false) {
                    $tripsCount = $userAgent->getClientid() ?
                        $manager->getSegmentCount($userAgent->getClientid()) :
                        $manager->getSegmentCount($userAgent->getAgentid(), $userAgent);

                    $this->memcached->set($cacheKey, json_encode(['trips' => $tripsCount]), 300);
                } else {
                    $tripsCount = @json_decode($data, true)['trips'];
                }
            } else {
                $tripsCount = '';
            }

            $result[] = [
                'LinkUserAgentID' => $member->LinkUserAgentID,
                'trips' => $tripsCount,
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/members/connection/{userAgentId}", name="aw_business_member_edit", options={"expose"=true})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @Template("@AwardWalletMain/Business/Members/editBusinessMember.html.twig")
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editBusinessMemberAction(
        Request $request,
        Useragent $userAgent,
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        if (!$this->isGranted('EDIT', $userAgent)) {
            throw $this->createAccessDeniedException();
        }

        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $backLink = $uaRep->findOneBy(['agentid' => $userAgent->getClientid(), 'clientid' => $userAgent->getAgentid()]);

        if (empty($backLink) || $backLink->getIsapproved() == false) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(Type\BusinessMemberType::class, $userAgent);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var EntityManager $em */
                $em = $this->em;
                $em->flush($form->getData());

                $session->getFlashBag()->add(
                    'notice',
                    $translator->trans(
                        /** @Desc("You have successfully changed member settings.") */
                        'notice.business-member-success-changed'
                    )
                );
            }
        }

        $ret = [
            'business' => $this->user,
            'form' => $form->createView(),
            'userAgent' => $userAgent,
        ];

        $user = $userAgent->getAgentid();

        $ret = array_merge($ret, $this->accessGrantHelper->calculateAccess($this->user, $user));

        $allTimelinesShared = $ret['sharedTimelinesCount'] === ($ret['familyMembersCount'] + 1);

        $ret['grantedFull'] = $allTimelinesShared && $ret['tripDefaults'] && $ret['tripLevel'] && $ret['accountDefaults'] && $ret['accountFull'] && $ret['accessLevelFull'];
        $ret['grantedRead'] = $allTimelinesShared && $ret['tripDefaults'] && $ret['accountDefaults'] && $ret['accountFull'] && ($ret['accessLevel'] >= 0);

        return $ret;
    }
}
