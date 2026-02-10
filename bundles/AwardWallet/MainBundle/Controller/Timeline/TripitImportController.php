<?php

namespace AwardWallet\MainBundle\Controller\Timeline;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class TripitImportController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private TripitHelper $tripitHelper;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        TripitHelper $tripitHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->tripitHelper = $tripitHelper;
    }

    /**
     * Получение токена запроса.
     *
     * @Route("/tripit/authorize", name="aw_timeline_tripit_authorize", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function actionAuthorize(RouterInterface $router)
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $tripitUser->removeTokens();
        $result = $this->tripitHelper->getRequestToken($tripitUser);

        if ($result) {
            $tripitUser->setRequestToken($result);
            $url = TripitHelper::SIGN_IN_URL . '?' . http_build_query([
                'oauth_token' => $tripitUser->getRequestToken(),
                'oauth_callback' => $router->generate('aw_timeline_tripit_access_token', [], RouterInterface::ABSOLUTE_URL),
                'is_sign_in' => 1,
            ]);

            return $this->redirect($url);
        }

        return $this->redirectToRoute('aw_timeline');
    }

    /**
     * Получение постоянного токена доступа.
     *
     * @Route("/tripit/access-token", name="aw_timeline_tripit_access_token", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function actionAccessToken()
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $result = $this->tripitHelper->getAccessToken($tripitUser);

        if ($result) {
            $tripitUser->setAccessToken($result);
            $this->tripitHelper->subscribe($tripitUser);

            return $this->redirectToRoute('aw_timeline_tripit_import');
        }

        return $this->redirectToRoute('aw_timeline');
    }

    /**
     * Получение новых резерваций для текущего пользователя.
     *
     * @Route("/tripit/import", name="aw_timeline_tripit_import", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function actionImport(SessionInterface $session)
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $result = $this->tripitHelper->list($tripitUser);

        if (!$result->getSuccess()) {
            return $this->redirectToRoute('aw_timeline_tripit_authorize');
        } elseif (empty($result->getItineraries())) {
            $session->getFlashBag()->add('reservations_not_found', true);

            return $this->redirectToRoute('aw_timeline');
        }

        return $this->redirectToRoute('aw_timeline_html5_itineraries', [
            'itIds' => implode(',', $result->getItineraries()),
        ]);
    }

    /**
     * Отключение учётной записи Tripit текущего пользователя.
     *
     * @Route("/tripit/disconnect", name="aw_timeline_tripit_disconnect", methods={"GET"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function actionDisconnect()
    {
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->entityManager);
        $this->tripitHelper->unsubscribe($tripitUser);
        $tripitUser->removeTokens();

        return $this->redirectToRoute('aw_profile_overview');
    }
}
