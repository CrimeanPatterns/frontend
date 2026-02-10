<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Sitead;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TrackController.
 *
 * @Route("/track")
 */
class TrackController extends AbstractController
{
    private AntiBruteforceLockerService $securityAntibruteforceApiExport;

    public function __construct(AntiBruteforceLockerService $securityAntibruteforceApiExport)
    {
        $this->securityAntibruteforceApiExport = $securityAntibruteforceApiExport;
    }

    /**
     * @Route("/{siteAdId}", name="aw_track_index", methods={"GET"}, requirements={"siteAdId"="\d+"})
     * @ParamConverter("siteAd", class="AwardWalletMainBundle:SiteAd", options={"id"="siteAdId"})
     * @return JsonResponse
     * @throws
     */
    public function indexAction(Request $request, Sitead $siteAd)
    {
        if (170 === $siteAd->getSiteadid()) {
            return $this->eMiles($request, $siteAd);
        }

        return new JsonResponse('Bad Request', Response::HTTP_BAD_REQUEST);
    }

    private function eMiles(Request $request, Sitead $siteAd)
    {
        $refCode = $request->query->get('tid');
        $ctid = $request->query->get('ctid');
        !empty($ctid) ?: $ctid = null;
        $pixel = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');

        if (empty($refCode)) {
            return new Response($pixel, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'image/gif']);
        }

        /** @var Connection $conn */
        $conn = $this->getDoctrine()->getConnection();
        $ip = $request->getClientIp();

        if ($error = $this->securityAntibruteforceApiExport->checkForLockout($ip, true)) {
            return new Response($pixel, Response::HTTP_NOT_FOUND, ['Content-Type' => 'image/gif']);
        }

        $user = $conn->fetchAssoc('SELECT UserID, Promo500k FROM Usr WHERE RefCode = ?', [$refCode], [\PDO::PARAM_STR]);

        if (empty($user)) {
            $this->securityAntibruteforceApiExport->checkForLockout($ip);

            return new Response($pixel, Response::HTTP_NOT_FOUND, ['Content-Type' => 'image/gif']);
        }

        $track = $conn->fetchAssoc('SELECT TrackID FROM Track WHERE UserID = ? AND SiteAdID = ? AND CtID ' . ($ctid ? '=' : 'IS') . ' ?',
            [$user['UserID'], $siteAd->getSiteadid(), $ctid],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, $ctid ? \PDO::PARAM_STR : \PDO::PARAM_NULL]
        );

        if (empty($track)) {
            $conn->insert('Track', [
                'SiteAdID' => $siteAd->getSiteadid(),
                'UserID' => $user['UserID'],
                'CtID' => $ctid,
            ]);
            //            if (empty($user['Promo500k']))
            //                $conn->update('Usr', ['Promo500k' => 1], ['UserID' => $user['UserID']]);
        }

        return new Response($pixel, Response::HTTP_OK, ['Content-Type' => 'image/gif']);
    }
}
