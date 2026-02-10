<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Sitead;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\RequestAttributes;
use AwardWallet\MainBundle\FrameworkExtension\Twig\SiteExtension;
use AwardWallet\MainBundle\Manager\SiteAdManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class ReferalListener implements EventSubscriberInterface
{
    public const REFERER_SESSION_KEY = 'referer';

    public const SESSION_REF_KEY = 'ref';
    public const REPLACEMENT_REF_SITEAD_ID = [233, 232, 231, 230];
    public const SESSION_ADVERTISER_ID_KEY = 'advertiser_id';

    protected TokenStorageInterface $tokenStorage;
    protected EntityManagerInterface $em;
    protected RouterInterface $router;
    private AuthorizationCheckerInterface $authorizationChecker;
    private Environment $twig;
    private SiteAdManager $siteAdManager;
    private LoggerInterface $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        Environment $twig,
        SiteAdManager $siteAdManager,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->twig = $twig;
        $this->siteAdManager = $siteAdManager;
        $this->logger = $logger;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        if (RequestAttributes::isSessionLessRequest($request)) {
            return;
        }

        $session = $request->getSession();

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if (!$user instanceof Usr) {
                $user = false;
            }
        } else {
            $user = false;
        }

        if ($user === false
            && $session !== null
            && !empty($referer = $request->headers->get('Referer'))
            && (
                !$session->has(self::REFERER_SESSION_KEY)
                || (
                    $request->query->has(self::SESSION_REF_KEY)
                    && in_array($request->query->getInt(self::SESSION_REF_KEY), self::REPLACEMENT_REF_SITEAD_ID, true)
                )
            )
        ) {
            $this->logger->info('setReferer ReferalListener', [
                'referer' => $referer,
                'has' => $session->has(self::REFERER_SESSION_KEY),
                'get' => $request->getSession()->get(self::REFERER_SESSION_KEY, 'undefined'),
                'ip' => $request->getClientIp(),
            ]);

            if ($request->query->has('var2') && is_string($request->query->get('var2')) && false === strpos($referer, 'var2=')) {
                $referer .= false !== strpos($referer, '?') ? '&' : '?';
                $referer .= 'var2=' . $request->query->get('var2');
            }

            $session->set(self::REFERER_SESSION_KEY, $referer);
        }

        $ref = (int) $request->get(self::SESSION_REF_KEY);

        if ($ref < 0) {
            $ref = 0;
        }

        if ($ref > 0) {
            $this->siteAdManager->updateClicksForRef($ref);

            if ($ref == 145 && $request->get('ftouch') && !$user) {
                $session->set('ReferralID', preg_replace('#[^\w\-]#ims', '', $request->get('ftouch')));
                /*
                 * @see /web/account/list.php
                // BEGIN: Tracking pixels for e-miles
                if (!empty($objList->Totals) && $_SESSION['UserFields']['CameFrom'] == 145 && !empty($_SESSION['UserFields']['ReferralID'])){
                    echo "<img style='width: 1px; height: 1px; border: none;' src='https://www.e-miles.com/autocredit.do?pc=6EWP5DK4KYUAY7Z&ftouch=".urlencode($_SESSION['ReferralID'])."&cs=1&id=awardwallet'>";
                    $Connection->Execute("update Usr set ReferralID = null where UserID = ".$_SESSION['UserID']);
                }
                // END: Tracking pixels for $_GET["ref"]
                */
            }

            if (170 == $ref && !empty($tid = $request->query->get('tid'))) { // e-Miles Aug 2017
                $session->set('tid', $tid);
            }
            $session->set(self::SESSION_REF_KEY, $ref);
        }

        // work only on home page
        $refCode = $request->get('refCode');
        $invId = $request->get('invId');

        if (($refCode || $invId)
            && !$user
            && $this->twig->getExtension(SiteExtension::class)->isHomePage()
        ) {
            // referral from link
            // /?refCode=...
            if ($refCode) {
                $event->stopPropagation();
                $response = new RedirectResponse($this->router->generate('aw_home'));
                $response->setContent($this->twig->render("@AwardWalletMain/redirectPage.html.twig", ['url' => $this->router->generate('aw_home')]));

                $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
                $inviter = $userRep->findBy(['refcode' => $refCode]);

                if (count($inviter)) {
                    /** @var Usr $inviter */
                    $inviter = $inviter[0];
                    $session->set('inviterId', $inviter->getId());
                    $session->set(self::SESSION_REF_KEY, Sitead::REF_INVITE_OPTION);
                    $this->siteAdManager->updateClicksForRef(Sitead::REF_INVITE_OPTION);
                    $response = $this->createRedirectToRegistration();
                }

                $event->setResponse($response);

                return;
            }

            // invite from mail
            // /?invId=...&code=...
            if ($invId) {
                $event->stopPropagation();
                $response = new RedirectResponse($this->router->generate('aw_home'));

                if ($request->get('target') != 'main' && $code = $request->get('code')) {
                    $invitesRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Invites::class);
                    $invite = $invitesRep->findBy(['invitesid' => $invId]);

                    if (count($invite)) {
                        /** @var Invites $invite */
                        $invite = $invite[0];

                        if ($invite->getCode() == $code && is_null($invite->getInviteeid())) {
                            $session->set('inviterId', $invite->getInviterid()->getId());
                            $session->set(self::SESSION_REF_KEY, Sitead::REF_INVITE_OPTION);
                            $this->siteAdManager->updateClicksForRef(Sitead::REF_INVITE_OPTION);
                            $response = $this->createRedirectToRegistration();
                        }
                    }
                }

                $event->setResponse($response);

                return;
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 21]],
        ];
    }

    protected function createRedirectToRegistration()
    {
        return $this->authorizationChecker->isGranted('SITE_MOBILE_VERSION_SUITABLE') ?
            new RedirectResponse($this->router->generate('aw_home') . 'm/registration') :
            new RedirectResponse($this->router->generate('aw_register'));
    }
}
