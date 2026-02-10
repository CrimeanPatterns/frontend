<?php

namespace AwardWallet\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbMessageRepository;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\ProviderPhoneResolver;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\f\coalesce;
use function AwardWallet\MainBundle\Globals\Utils\f\column;
use function AwardWallet\MainBundle\Globals\Utils\f\columnEq;
use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;
use function AwardWallet\MainBundle\Globals\Utils\f\compareColumns;

/**
 * @Route("/awardBooking")
 */
class JsonController extends AbstractController
{
    /**
     * For custom program autocomplete.
     *
     * @Route("/getAllProgs", name="aw_booking_json_getallprogs", options={"expose"=true})
     */
    public function getAllProgsAction(Request $request)
    {
        $whiteListCodes = [];
        $notShowCodes = [];

        if ($request->get('param') == 'seats') {
            $whiteListCodes = ['austrian', 'swissair', 'brussels'];
        } else {
            $notShowCodes = ['swissair', 'brussels'];
        }
        $whiteListCodes = array_merge($whiteListCodes, $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getWhiteListProgramCodes());

        if ($query = $request->get('query')) {
            return new JsonResponse(
                $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)
                    ->findBookingProgs(
                        $query,
                        $whiteListCodes,
                        $notShowCodes,
                        $request->get('param') == 'seats' ? [PROVIDER_HIDDEN] : []
                    )
            );
        }

        return new JsonResponse();
    }

    /**
     * get elite levels by provider name.
     *
     * @Route("/getEliteLevels", name="aw_booking_json_getelitelevels", options={"expose"=true})
     * @return JsonResponse
     */
    public function getEliteLevelsAction(Request $request)
    {
        $provider = $request->get('provider');
        $repo = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $provider = $repo->findOneBy(['displayname' => $provider]);

        if (isset($provider)) {
            $response = $repo->getEliteLevels($provider->getProviderid());
        } else {
            $response = [];
        }

        return new JsonResponse($response);
    }

    /**
     * get phones by provider name.
     *
     * @Route("/getPhones/{provider}", name="aw_booking_json_getphones", options={"expose"=true})
     */
    public function getPhonesAction($provider, ProviderPhoneResolver $providerPhoneResolver)
    {
        $providerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $regionRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Region::class);
        $provider = $providerRep->findOneBy(['displayname' => $provider]);

        if (isset($provider)) {
            $providerId = strval($provider->getProviderid());
            $usRegionId = $regionRep->getUsCountryId();
            $data = $providerPhoneResolver->getUsefulPhones(
                [
                    ['provider' => $providerId],
                ],
                [],
                [
                    [compareBy(columnEq('RegionID', $usRegionId)),   Criteria::DESC],
                    [compareColumns('PhoneGroupID'),                 Criteria::ASC],
                    [compareBy(coalesce(column('DefaultPhone'), 0)), Criteria::DESC],
                    [compareColumns('PhoneFor'),                     Criteria::ASC],
                    [compareColumns('Phone'),                        Criteria::ASC],
                ]
            );
            $response = [];

            foreach ($data as $row) {
                $response[] = [$row['Phone'], $row['RegionCaption']];
            }
        } else {
            $response = [];
        }

        return new JsonResponse($response);
    }

    /**
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/search/members", name="aw_booking_json_searchmembers", options={"expose"=true})
     */
    public function searchMembersAction(Request $request, LocalizeService $localizeService)
    {
        /** @var Usr $user */
        $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($this->getUser());
        $query = $request->get('query');
        $step = $request->get('step', 1);

        if (!is_string($query) || empty($query)) {
            return JsonResponse::create([]);
        }
        $excluding = $request->get('excluding');

        if (!is_string($excluding) || empty($excluding)) {
            $excluding = [];
        } else {
            $excluding = explode(",", $excluding);
        }

        $options = [
            'filter' => " AND (u.AccountLevel IS NULL OR u.AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . ")",
            'excluding' => $excluding,
            'limit' => 10,
            'localizer' => $localizeService,
        ];

        switch ($step) {
            case "4":
                $options['select'] = ", u.UserID, u.Phone1 AS Phone, u.Email, CONCAT(u.FirstName, ' ', u.LastName) AS FullName";
                $options['filter'] .= " AND u.UserID IS NOT NULL";

                break;

            case "3":
                break;

            case "1": default:
                $options['select'] = ", NULL AS Birthday, NULL AS Nationality, NULL AS Gender";

                break;
        }

        $rep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $found = $rep->searchMembers($user, $query, $options);

        if ($step == 1) {
            if (sizeof($found)) {
                // get user agents ids
                $ids = [];

                foreach ($found as $row) {
                    $ids[] = $row['UserAgentID'];
                }
                $passengers = $this->getDoctrine()->getConnection()->executeQuery(
                    "
                    SELECT DISTINCT 
                        UserAgentID,
                        Birthday,
                        Nationality,
                        Gender
                    FROM
                        AbPassenger
                    WHERE UserAgentID IN (?)
                    ORDER BY UserAgentID DESC",
                    [$ids],
                    [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
                )->fetchAll(\PDO::FETCH_ASSOC);
                unset($ids);
                $processed = [];

                foreach ($passengers as $passenger) {
                    $processed[$passenger['UserAgentID']] = $passenger;
                }
                unset($passengers);

                if (sizeof($processed)) {
                    foreach ($found as &$row) {
                        if (!isset($processed[$row['UserAgentID']])) {
                            continue;
                        }
                        $p = &$processed[$row['UserAgentID']];
                        $row['Birthday'] = $p['Birthday'];
                        $row['Nationality'] = $p['Nationality'];
                        $row['Gender'] = $p['Gender'];
                    }
                }
            }
        } elseif ($step == 3) {
            if (preg_match("/" . preg_quote($query) . "/ims", $user->getFullName())) {
                array_unshift($found, [
                    'UserAgentID' => 0,
                    'FirstName' => null,
                    'MiddleName' => null,
                    'LastName' => null,
                    'Connected' => 0,
                    'text' => $user->getFullName(),
                    'id' => 0,
                ]);
            }
        } elseif ($step == 4) {
            foreach ($found as &$f) {
                $f['id'] = $f['UserID'];
            }
        }

        return JsonResponse::create($found);
    }

    /**
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/search/accounts", name="aw_booking_json_searchaccounts", options={"expose"=true})
     */
    public function searchAccountsAction(
        Request $request,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        /** @var Usr $user */
        $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($this->getUser());
        $ua = $request->get('ua');

        if (!isset($ua) || !is_numeric($ua)) {
            return JsonResponse::create([]);
        }

        // Account List
        $accounts = $accountListManager
            ->getAccountList(
                $optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_USERAGENT, $ua)
            )
            ->getAccounts();
        $response = [];

        foreach ($accounts as $account) {
            $response[] = [
                'id' => $account['ID'],
                'text' => $account['DisplayName'] . " / " . $account['Login'],
                'status' => isset($account['MainProperties']['Status']) ? $account['MainProperties']['Status']['Status'] : '',
                'balance' => $account['Balance'],
            ];
        }

        return JsonResponse::create($response);
    }

    /**
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/check/member", name="aw_booking_json_checkmember", options={"expose"=true})
     */
    public function checkMemberAction(RequestStack $requestStack)
    {
        /** @var Usr $user */
        $user = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($this->getUser());
        /** @var Request $request */
        $request = $requestStack->getCurrentRequest();
        $fn = $request->get('fn');
        $ln = $request->get('ln');
        $response = 0;

        if (!is_string($fn) || !is_string($ln)) {
            return JsonResponse::create($response);
        }
        $fn = trim($fn);
        $ln = trim($ln);

        if (empty($fn) || empty($ln)) {
            return JsonResponse::create($response);
        }

        $conn = $this->getDoctrine()->getConnection();
        $row = $conn->executeQuery(
            "
            SELECT
                1
            FROM
                UserAgent ua
                LEFT OUTER JOIN Usr u
                    ON u.UserID = ua.ClientID
            WHERE
                ua.AgentID = ?
                AND (
                  (ua.FirstName = ?
                  AND ua.LastName = ?)
                  OR
                  (u.FirstName = ?
                  AND u.LastName = ?)
                )
            LIMIT 1
            ",
            [$user->getUserid(), $fn, $ln, $fn, $ln],
            [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            $response = 1;
        }

        return JsonResponse::create($response);
    }

    /**
     * @Security("is_granted('USER_BOOKING_PARTNER')")
     * @Route("/getQueueUpdates", name="aw_booking_json_getqueueupdates", options={"expose"=true})
     */
    public function getQueueUpdates(Request $request, AwTokenStorageInterface $tokenStorage)
    {
        /** @var Usr $user */
        $user = $this->getUser();
        /** @var AbRequestRepository $requestRep */
        $requestRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        try {
            $lastCheck = new \DateTime($request->get('lastCheck', '-1 min'));
        } catch (\Exception $e) {
            $lastCheck = new \DateTime('-1 min');
        }

        $currentCheck = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
        $ret = [
            'queue' => $requestRep->getUnreadCountForUser($user, true, $tokenStorage->getBusinessUser()),
            'updates' => array_map(function (AbRequest $item) {return $item->getAbRequestId(); }, $requestRep->getUnreadListByUser($user, $lastCheck, true)),
            'currentCheck' => $currentCheck->format(\DateTime::RFC3339),
        ];

        return new JsonResponse($ret);
    }

    /**
     * @Route("/getViewUpdates/{id}", name="aw_booking_json_getviewupdates", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', abRequest)")
     * @ParamConverter("abRequest", class="AwardWalletMainBundle:AbRequest")
     */
    public function getViewUpdates(AbRequest $abRequest, Request $request)
    {
        /** @var Usr $user */
        $user = $this->getUser();
        /** @var AbRequestRepository $requestRep */
        $requestRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        /** @var AbMessageRepository $messageRep */
        $messageRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class);

        $lastMessageID = $request->get('lastMessageID', 0);

        $query = $messageRep->createQueryBuilder('m')
            ->andWhere('m.RequestID = :AbRequestID')
            ->andWhere('m.AbMessageID > :lastMessageID')
            ->andWhere('m.UserID <> :UserID')
            ->andWhere('m.Type not in (:internal)')
            ->setParameter(':lastMessageID', $lastMessageID)
            ->setParameter(':AbRequestID', $abRequest->getAbRequestId())
            ->setParameter(':UserID', $user->getUserid())
            ->setParameter(':internal', [AbMessage::TYPE_INTERNAL, AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL]);

        $messageRender = $this->render('@AwardWalletMain/Booking/Message/message.html.twig', [
            'messages' => $query->getQuery()->getResult(),
            'agentsRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class),
            'reqRep' => $requestRep,
            'usrRep' => $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class),
            'request' => $abRequest,
        ])->getContent();

        $ret = [
            'messages' => trim($messageRender),
        ];

        return new JsonResponse($ret);
    }
}
