<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Utils;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class OfferManager
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private ManagerRegistry $doctrine;

    /**
     * @var Connection
     */
    private $connection;
    private $request;
    private $offerId;
    private $offerUserId;
    private $code;
    private $description;
    private $descTitle;
    private $picturePath;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(ManagerRegistry $doctrine, AuthorizationChecker $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->doctrine = $doctrine;
        $this->em = $doctrine->getManager();
        $this->connection = $this->em->getConnection();
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public function checkUserOffers(Usr $user, Request $request, $src = 0)
    {
        // searches for an offer available to an authorized user
        $currentUserId = $user->getUserid();

        if (
            $this->authorizationChecker->isGranted('USER_IMPERSONATED')
            && ($offerImpersonate = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Offerimpersonate::class)->findOneBy(['login' => Utils::getImpersonator($this->tokenStorage->getToken())]))
            && $offerImpersonate->getDisabled()
        ) {
            return false;
        }

        if ($user->isAwPlus() && $user->isSplashAdsDisabled()) {
            return false;
        }

        // issue #7403
        // SiteGroup #50 - Do Not Communicate
        $row = $this->connection->executeQuery(
            "SELECT EXISTS(SELECT 1 FROM GroupUserLink WHERE UserID = ? and SiteGroupID = ?) as x",
            [$currentUserId, 50],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['x'] == 1) {
            return false;
        }

        require_once __DIR__ . '/../../../../web/schema/Offer.php';
        $cl = 'TOfferSchema';
        $offerPeriod = $cl::getShowPeriod();

        // check if some offer has been shown to this user recently
        $row = $this->connection->executeQuery("
			SELECT COUNT(*) as `Count` FROM Usr
			WHERE UserID = ? AND  (OfferShowDate IS NULL or now() > DATE_ADD(OfferShowDate, INTERVAL ? MINUTE))",
            [$currentUserId, $offerPeriod],
            [\PDO::PARAM_INT, \PDO::PARAM_INT])->fetch(\PDO::FETCH_ASSOC);

        if (($row['Count'] == 1) || $src) {
            // if several offers are suitable for current user, the oldest one will be shown
            $result = $this->connection->executeQuery("
				SELECT * FROM OfferUser JOIN Offer ON OfferUser.OfferID = Offer.OfferID
				WHERE UserID = ? AND Enabled = 1 AND Agreed IS NULL
				AND (ShowDate IS NULL OR DATE_ADD(ShowDate, INTERVAL RemindMeDays DAY) < now())
				AND IF(MaxShows IS NULL, 1, OfferUser.ShowsCount < MaxShows)
				AND NOT Offer.Kind in (select OfferKind from OfferKindRefused where UserID = ?)
				AND (now() < Offer.ShowUntilDate or Offer.ShowUntilDate is null)
				ORDER BY Priority DESC, ShowDate",
                [$currentUserId, $currentUserId],
                [\PDO::PARAM_INT, \PDO::PARAM_INT]);
            $rows = [];

            while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
                $rows[] = $row;
            }

            if (count($rows) > 0) {
                $offerUserId = $rows[0]['OfferUserID'];

                require_once __DIR__ . '/../../../../web/manager/offer/plugins/' . ucfirst($rows[0]['Code']) . 'OfferPlugin.php';
                $cl = ucfirst($rows[0]['Code']) . 'OfferPlugin';
                $offer = new $cl($rows[0]['OfferID'], $this->doctrine);
                $offer->logging = false;

                if ($offer->checkUser($currentUserId, $offerUserId)) {
                    return $offerUserId;
                }
            }
        }

        return false;
    }

    public function getOfferData($offerUserId, Request $request, Usr $user, $src = 0)
    {
        $offerUserId = (int) $offerUserId;

        $sql = $this->connection->prepare("
			SELECT Code, OfferUser.OfferID, ApplyURL, FirstName, DisplayType, Agreed
			FROM OfferUser INNER JOIN Offer ON OfferUser.OfferID = Offer.OfferID JOIN Usr ON OfferUser.UserID = Usr.UserID
			WHERE OfferUser.OfferUserID = $offerUserId");
        $sql->execute();
        $result = $sql->fetchAll();

        if (count($result) == 1) {
            $offerId = $result[0]['OfferID'];
            $code = $result[0]['Code'];
            $applyUrl = $result[0]['ApplyURL'];
            $FirstName = $result[0]['FirstName'];

            require_once __DIR__ . '/../../../../web/manager/offer/plugins/' . ucfirst($code) . 'OfferPlugin.php';
            $cl = ucfirst($code) . 'OfferPlugin';
            /** @var \OfferPlugin $offer */
            $offer = new $cl($offerId, $this->doctrine);

            if (!$offer->checkUser($user->getUserid(), $offerUserId)) {
                return null;
            }

            $params = $offer->getParams($offerUserId, false, $request->request->get('Params'));

            $returnLocation = '/account/list';
            //            $referer = $request->server->get("HTTP_REFERER");
            //            if (!empty($referer) && parse_url($referer, PHP_URL_HOST) == $request->getHost()) {
            //                $returnLocation = urlPathAndQuery($referer);
            //            }

            if (!$this->authorizationChecker->isGranted('ROLE_IMPERSONATED')) {
                $sql = $this->connection->prepare("
					UPDATE OfferUser SET ShowDate = now()
					WHERE OfferUserID = $offerUserId");
                $sql->execute();
                $this->connection->executeQuery("
					UPDATE Usr SET OfferShowDate = now()
					WHERE UserID = ?",
                    [$user->getUserid()],
                    [\PDO::PARAM_INT]);
                $this->connection->executeQuery("
					UPDATE OfferUser SET ShowsCount = ShowsCount + 1
					WHERE OfferUserID = ?",
                    [$offerUserId],
                    [\PDO::PARAM_INT]);
                $sql = $this->connection->prepare("
					UPDATE Offer SET ShowsCount = ShowsCount + 1
					WHERE OfferID = $offerId
					");
                $sql->execute();
                $this->connection->executeQuery("
                    INSERT INTO OfferLog (OfferID, UserID, Action, ActionDate)
                    VALUES (?, ?, Null, now())",
                    [$offerId, $user->getUserid()],
                    [\PDO::PARAM_INT, \PDO::PARAM_INT]
                );
                $offer->afterShow($user->getUserid(), $offerUserId, $params);
            }

            if ($this->authorizationChecker->isGranted('ROLE_IMPERSONATED')) {
                //                  Setting a $_SESSION var is probably a bad behaviour but there's no other way yet
                $_SESSION['ImpersonatedOfferShown'] = true;
            }

            return array_merge([
                "returnLocation" => $returnLocation,
                "src" => $src,
                "OfferUserID" => $offerUserId,
                "ApplyURL" => $applyUrl,
                "FirstName" => $FirstName,
                "DescTitle" => $this->descTitle,
                "Description" => $this->description,
                "PicturePath" => $this->picturePath,
                "Code" => $code,
            ], $params);
        }

        return null;
    }
}
