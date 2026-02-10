<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OfferController extends AbstractController
{
    private $offerId;
    private $offerUserId;
    private $code;
    private $description;
    private $descTitle;
    private $picturePath;

    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private KernelInterface $kernel;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        KernelInterface $kernel
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->kernel = $kernel;
    }

    /**
     * @Route("/offer/var/{offerUserId}", name="aw_offer_var", requirements={"offerUserId" = "\d+"})
     * @param int $offerUserId
     * @return Response
     */
    public function varAction(Request $request, $offerUserId = 0, SessionInterface $session)
    {
        // $this->offerUserId = intval($offerUserId);
        // $this->checkUser();
        $session->set("OfferVar", $request->query->get('var'));

        return new Response($session->get("OfferVar"));
    }

    /**
     * @Route("/offer/agree/{offerUserId}/{agreed}", name="aw_offer_agree", methods={"POST"}, requirements={"offerUserId" = "\d+", "agreed" = "\d+"})
     * @Security("is_granted('CSRF')")
     * @param int $offerUserId
     * @return Response
     */
    public function agreeAction(Request $request, $offerUserId = 0, $agreed = -1)
    {
        // 0 - Refused
        // 1 - Agreed
        // 2 - Refused to see offers of the kind
        $this->offerUserId = intval($offerUserId);

        $result = $this->getDoctrine()->getConnection()->executeQuery("
            SELECT OfferID FROM OfferUser WHERE OfferUserID = ?",
            [$offerUserId],
            [\PDO::PARAM_INT]);
        $this->offerId = intval($result->fetch(\PDO::FETCH_ASSOC)['OfferID']);

        if (!$this->authorizationChecker->isGranted('ROLE_IMPERSONATED') && $this->checkUser()) {
            if (($agreed == '0') || ($agreed == '1')) {
                $sql = $this->getDoctrine()->getConnection()->prepare("
	   		UPDATE OfferUser SET Agreed = $agreed
	   		WHERE OfferUserID = $this->offerUserId");
                $sql->execute();
                $this->getDoctrine()->getConnection()->executeQuery("
                   INSERT INTO OfferLog (OfferID, UserID, Action, ActionDate)
                    VALUES (?, ?, ?, now())",
                    [$this->offerId, $this->tokenStorage->getBusinessUser()->getUserid(), $agreed],
                    [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
                );
            } elseif ($agreed == 2) {
                if (!$this->authorizationChecker->isGranted('ROLE_IMPERSONATED') && $this->checkUser()) {
                    $sql = $this->getDoctrine()->getConnection()->prepare("
	   		UPDATE OfferUser SET Agreed = 0
	   		WHERE OfferUserID = $this->offerUserId");
                    $sql->execute();
                    $result = $this->getDoctrine()->getConnection()->executeQuery("
        	    SELECT Kind FROM OfferUser JOIN Offer ON OfferUser.OfferID = Offer.OfferID
			    WHERE OfferUserID = ?",
                        [$offerUserId],
                        [\PDO::PARAM_INT]);
                    $offerKind = intval($result->fetch(\PDO::FETCH_ASSOC)['Kind']);

                    if ($offerKind != 0) {
                        $sql = $this->getDoctrine()->getConnection()->prepare("
	   	        INSERT INTO OfferKindRefused (UserID, OfferKind)
        		VALUES ({$this->tokenStorage->getBusinessUser()->getUserid()}, $offerKind)
        		on duplicate key update OfferKindRefusedID = OfferKindRefusedID
        		");
                        $sql->execute();
                        $this->getDoctrine()->getConnection()->executeQuery("
                   INSERT INTO OfferLog (OfferID, UserID, Action, ActionDate)
                    VALUES (?, ?, 2, now())",
                            [$this->offerId, $this->tokenStorage->getBusinessUser()->getUserid()],
                            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
                        );
                        $this->getDoctrine()->getConnection()->executeQuery("
                   INSERT INTO OfferLog (OfferID, UserID, Action, ActionDate)
                    VALUES (?, ?, 0, now())",
                            [$this->offerId, $this->tokenStorage->getBusinessUser()->getUserid()],
                            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
                        );
                    }
                } else {
                    $agreed = -1;
                }
            } else {
                $agreed = -1;
            }
        }

        return new Response($agreed);
    }

    /**
     * @Route("/offer/find/{src}", name="aw_offer_find", defaults={"src" = 0}, requirements={"src" = "\d+"})
     * @return Response
     */
    public function findAction($src)
    {
        // searches for an offer available to an authorized user
        $response = 'none';
        $currentUserId = $this->tokenStorage->getBusinessUser()->getUserid();

        // issue #7403
        // SiteGroup #50 - Do Not Communicate
        $row = $this->getDoctrine()->getConnection()->executeQuery(
            "SELECT EXISTS(SELECT 1 FROM GroupUserLink WHERE UserID = ? and SiteGroupID = ?) as x",
            [$currentUserId, 50],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['x'] == 1) {
            return new Response($response);
        }

        require_once ($rootDir = $this->kernel->getProjectDir()) . '/web/schema/Offer.php';
        $cl = 'TOfferSchema';
        $offerPeriod = $cl::getShowPeriod();

        // check if some offer has been shown to this user recently
        $row = $this->getDoctrine()->getConnection()->executeQuery("
			SELECT COUNT(*) as `Count` FROM Usr
			WHERE UserID = ? AND  (OfferShowDate IS NULL or now() > DATE_ADD(OfferShowDate, INTERVAL ? MINUTE))",
            [$currentUserId, $offerPeriod],
            [\PDO::PARAM_INT, \PDO::PARAM_INT])->fetch(\PDO::FETCH_ASSOC);

        if (($row['Count'] == 1) || $src) {
            // if several offers are suitable for current user, the oldest one will be shown
            /** @var \PDOStatement $result */
            $result = $this->getDoctrine()->getConnection()->executeQuery(
                "SELECT *
                    FROM OfferUser ou
                    JOIN Offer o ON ou.OfferID = o.OfferID
                    WHERE
                        ou.UserID = ? AND 
                        o.Enabled = 1 AND 
                        ou.Agreed IS NULL AND 
                        (
                            ou.ShowDate IS NULL OR 
                            DATE_ADD(ou.ShowDate, INTERVAL o.RemindMeDays DAY) < now()
                        ) AND 
                        IF(o.MaxShows IS NULL, 1, ou.ShowsCount < o.MaxShows) AND 
                        NOT o.Kind IN (
                            SELECT OfferKind
                                FROM OfferKindRefused
                            WHERE
                                UserID = ?
                        ) AND 
                        (
                            now() < o.ShowUntilDate OR
                            o.ShowUntilDate IS NULL
                        )
                    ORDER BY 
                        Priority DESC, 
                        ShowDate",
                [$currentUserId, $currentUserId],
                [\PDO::PARAM_INT, \PDO::PARAM_INT]
            );

            while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                $offerUserId = $row['OfferUserID'];
                $popup = $row['DisplayType'];

                $offerClassName = ucfirst($row['Code']) . 'OfferPlugin';
                $s = require_once $rootDir . '/web/manager/offer/plugins/' . $offerClassName . '.php';
                /** @var \OfferPlugin $offer */
                $offer = new $offerClassName($row['OfferID'], $this->getDoctrine());
                $offer->logging = false;

                if ($offer->checkUser($currentUserId, $offerUserId)) {
                    if ($popup) {
                        return $this->showAction($offerUserId, $src);
                    } else {
                        $response = 'redirect ' . $offerUserId;

                        break;
                    }
                }
            }
        }

        return new Response($response);
    }

    /**
     * @Route("/manager/offer/preview/{offerUserId}", name="aw_offer_show", defaults={"src" = 1}, requirements={"offerUserId" = "\d+"})
     * @Route("/offer/show/{offerUserId}", name="aw_offer_show_2", defaults={"src" = 0}, requirements={"src" = "\d+"})
     * @param int $src
     * @return Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function showAction(Request $request, $offerUserId, $src)
    {
        // If $_POST["Params"] and $_GET['Preview'] are set, Controller works in the preview mode
        // This means that it ignores the Params field in the OfferUser table
        //  because it doesn't hold the value that was modified
        //  but not yet saved in the manager

        $response = "none";
        $this->offerUserId = intval($offerUserId);
        $preview = $request->query->has('preview');
        $newDesign = $request->query->has('ND') ? [
            'popup' => true,
            'defaultRedirectLocation' => '',
            'preview' => true, ] : [];
        $params = [];

        if ($preview && (false === $this->authorizationChecker->isGranted('ROLE_STAFF'))) {
            throw new AccessDeniedException();
        } else {
            if (!$preview) {
                $this->checkUser();
            }

            $sql = $this->getDoctrine()->getConnection()->prepare("
			        SELECT 
			            OfferUser.UserID, 
			            Code, 
			            OfferUser.OfferID, 
			            ApplyURL, 
			            FirstName, 
			            DisplayType, 
			            Agreed
			        FROM OfferUser 
			        INNER JOIN Offer ON OfferUser.OfferID = Offer.OfferID 
			        JOIN Usr ON OfferUser.UserID = Usr.UserID
			        WHERE 
			            OfferUser.OfferUserID = $this->offerUserId");
            $sql->execute();
            $result = $sql->fetchAll();

            if (count($result) == 1) {
                if ($result[0]['Agreed'] == '0' && (false === $this->authorizationChecker->isGranted('ROLE_STAFF'))) {
                    throw new AccessDeniedException();
                }
                $this->offerId = $result[0]['OfferID'];
                $this->code = $result[0]['Code'];
                $applyUrl = $result[0]['ApplyURL'];
                $FirstName = $result[0]['FirstName'];

                // TODO: popup offers cannot be shown at /offer/show
                // $popup = $result[0]['DisplayType'];
                // $router = $this->get('_router');
                // $route = $router->match($request->getPathInfo());
                // if ($popup && !preview && !src && ($route['_route'] == 'aw_offer_show_2')){
                //     throw new AccessDeniedException();
                // }

                require_once $this->kernel->getProjectDir() . '/web/manager/offer/plugins/' . ucfirst($this->code) . 'OfferPlugin.php';
                $cl = ucfirst($this->code) . 'OfferPlugin';
                /** @var \OfferPlugin $offer */
                $offer = new $cl($this->offerId, $this->getDoctrine());
                $params = $offer->getParams($offerUserId, $preview, $request->request->get('Params'));

                $returnLocation = '/account/list.php?UserAgentID=All';
                $referer = $request->server->get("HTTP_REFERER");

                if (!empty($referer) && parse_url($referer, PHP_URL_HOST) == $request->getHost()) {
                    $returnLocation = urlPathAndQuery($referer);
                }

                $offerData = array_merge(
                    ["preview" => $preview, 'returnLocation' => $returnLocation],
                    ["src" => $src],
                    ["OfferUserID" => $this->offerUserId],
                    ["ApplyURL" => $applyUrl],
                    ["FirstName" => $FirstName],
                    ["DescTitle" => $this->descTitle],
                    ["Description" => $this->description],
                    ["PicturePath" => $this->picturePath],
                    ["Code" => $this->code],
                    $newDesign,
                    $params
                );
                $offerData['OfferData'] = $offerData;

                if (!$offer->checkUser($result[0]['UserID'], $this->offerUserId)) {
                    if ('aw_offer_show' === $request->attributes->get('_route')) {
                        return new Response($this->renderView("@AwardWalletMain/Offer/previewunavailable.html.twig", $offerData));
                    } else {
                        return new RedirectResponse($returnLocation);
                    }
                }

                $response = $this->renderView("@AwardWalletMain/Offer/$this->code.html.twig", $offerData);

                if (!$preview && !$src && !$this->authorizationChecker->isGranted('ROLE_IMPERSONATED')) {
                    $sql = $this->getDoctrine()->getConnection()->prepare("
					UPDATE OfferUser SET ShowDate = now()
					WHERE OfferUserID = $this->offerUserId");
                    $sql->execute();
                    $this->getDoctrine()->getConnection()->executeQuery("
					UPDATE Usr SET OfferShowDate = now()
					WHERE UserID = ?",
                        [$this->tokenStorage->getBusinessUser()->getUserid()],
                        [\PDO::PARAM_INT]);
                    $this->getDoctrine()->getConnection()->executeQuery("
					UPDATE OfferUser SET ShowsCount = ShowsCount + 1
					WHERE OfferUserID = ?",
                        [$this->offerUserId],
                        [\PDO::PARAM_INT]);
                    $sql = $this->getDoctrine()->getConnection()->prepare("
					UPDATE Offer SET ShowsCount = ShowsCount + 1
					WHERE OfferID = $this->offerId
					");
                    $sql->execute();
                    $this->getDoctrine()->getConnection()->executeQuery("
                    INSERT INTO OfferLog (OfferID, UserID, Action, ActionDate)
                    VALUES (?, ?, Null, now())",
                        [$this->offerId, $this->tokenStorage->getBusinessUser()->getUserid()],
                        [\PDO::PARAM_INT, \PDO::PARAM_INT]
                    );
                    $offer->afterShow($this->tokenStorage->getBusinessUser()->getUserid(), $this->offerUserId, $params);
                }

                if (!$preview && !$src && $this->authorizationChecker->isGranted('ROLE_IMPERSONATED')) {
                    //                  Setting a $_SESSION var is probably a bad behaviour but there's no other way yet
                    $_SESSION['ImpersonatedOfferShown'] = true;
                }
            } else {
                $response = "none";
            }
        }

        return new Response($response);
    }

    public function renderOfferAction($offerData)
    {
        $popup = $this->render("@AwardWalletMain/Offer/{$offerData['Code']}.html.twig", [
            'preview' => false,
            'popup' => true,
            'returnLocation' => $offerData['returnLocation'],
            'defaultRedirectLocation' => $offerData['returnLocation'],
            'src' => $offerData['src'],
            'OfferUserID' => $offerData['OfferUserID'],
            'ApplyURL' => $offerData['ApplyURL'],
            'FirstName' => $offerData['FirstName'],
            'DescTitle' => $offerData['DescTitle'],
            'Description' => $offerData['Description'],
            'PicturePath' => $offerData['PicturePath'],
            'Code' => $offerData['Code'],
            'OfferData' => $offerData,
        ]);

        $isPopup = strpos($popup->getContent(), 'id="offerPopup"');

        return $isPopup ? $popup : new Response();
    }

    private function checkUser()
    {
        $currentUserId = $this->tokenStorage->getBusinessUser()->getUserid();
        $allowedUserId = 0;
        $sql = $this->getDoctrine()->getConnection()->prepare("
		SELECT UserID FROM OfferUser
		WHERE OfferUserID = $this->offerUserId");
        $sql->execute();
        $result = $sql->fetchAll();

        if (count($result) == 1) {
            $allowedUserId = $result[0]['UserID'];
        }

        if ($allowedUserId == $currentUserId) {
            return true;
        }

        throw new AccessDeniedException();
    }
}
