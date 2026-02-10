<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Redirect;
use AwardWallet\MainBundle\Entity\Redirecthit;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\SecureLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OutController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/out", name="aw_out")
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function outAction(Request $request, SecureLink $secureLink, string $host)
    {
        $url = $request->query->get("url");
        $hash = $request->query->get("hash");
        $redirectId = $request->query->get("redirectId");
        $referer = $request->server->get("HTTP_REFERER");

        $valid = !empty($url) && is_string($url) && preg_match('#^http(s)?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL);

        if ($valid && !empty($hash)) {
            $valid = $secureLink->checkUrlHash($url, $hash);
        } elseif ($valid && !empty($referer)) {
            // do not redirect from ?BackTo pages
            $valid = preg_match('#^http(s)?://([a-zA-Z0-9\-]+\.)*' . preg_quote($host . "/") . "#i", $referer)
                && stripos($referer, 'BackTo') === false;
        } elseif (
            !empty($redirectId)
            && $redirect = $this->getDoctrine()->getRepository(Redirect::class)->find($redirectId)
        ) {
            $url = htmlspecialchars_decode($redirect->getUrl());
            $this->processRedirect($redirect);
            $valid = true;
        } else {
            $valid = false;
        }

        if (!$valid) {
            return new RedirectResponse("/");
        }

        return $this->render("@AwardWalletMain/out.html.twig", ["url" => $url]);
    }

    protected function processRedirect(Redirect $redirect)
    {
        $user = $this->tokenStorage->getUser();
        $userId = $user ? $user->getUserid() : null;

        $redirectHit = new Redirecthit();
        $redirectHit->setRedirectid($redirect);
        $redirectHit->setHitdate(new \DateTime());
        $redirectHit->setUserid($userId);
        $this->entityManager->persist($redirectHit);
        $this->entityManager->flush();
    }
}
