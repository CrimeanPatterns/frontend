<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\AbBookerInfo;
use AwardWallet\MainBundle\Entity\Repositories\AbBookerInfoRepository;
use AwardWallet\MainBundle\Entity\Repositories\SocialadRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserEmailVerificationChangedEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker\OpenTracker;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class EmailLogoController extends AbstractController
{
    private RequestStack $requestStack;

    private AdManager $adManager;

    private EntityManagerInterface $em;

    private UsrRepository $userRepository;

    private SocialadRepository $socialadRepository;

    private UserManager $userManager;

    private KernelInterface $kernel;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        RequestStack $requestStack,
        AdManager $adManager,
        EntityManagerInterface $em,
        UsrRepository $usrRepository,
        SocialadRepository $socialadRepository,
        UserManager $userManager,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->adManager = $adManager;
        $this->em = $em;
        $this->userRepository = $usrRepository;
        $this->socialadRepository = $socialadRepository;
        $this->userManager = $userManager;
        $this->kernel = $kernel;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route(
     *     "/logo/{userId}/{hash}/{trackingId}.png",
     *     name="aw_email_logo",
     *     requirements={"userId" = "\d+", "hash" = "[a-z\d]{1,40}", "trackingId" = "[a-z\d]{4,32}"}
     * )
     * @Route("/logo.png", name="aw_email_logo_2")
     */
    public function awLogoAction(
        OpenTracker $openTracker,
        LoggerInterface $logger,
        Request $request,
        int $userId = 0,
        string $trackingId = 'logo',
        string $hash = ''
    ) {
        if ($newUserId = $this->getUserIdFromRequest($request)) {
            $userId = $newUserId;
        } elseif (empty($userId) && !empty($newUserId = $request->get('userId')) && is_numeric($newUserId)) {
            $userId = $newUserId;
        }

        if (empty($hash) && !empty($newHash = $request->get('hash')) && is_string($newHash)) {
            $hash = $newHash;
        }

        $image = $this->getImage($this->kernel->getProjectDir() . "/web/images/email/newdesign/logo.png");

        if (!empty($hash)) {
            $this->verifyEmail($userId, $hash);
        }
        $this->recordAdStat();

        if ($trackingId !== "logo") {
            $binaryTrackingId = @hex2bin($trackingId);

            if ($binaryTrackingId !== false) {
                $openTracker->logOpen($binaryTrackingId, $request);
            } else {
                $logger->warning("failed to decode trackingId: $trackingId");
            }
        }

        return new Response($image, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * @Route(
     *     "/emtrpx/{trackingId}.png",
     *     name="aw_email_tracking_pixel",
     *     requirements={"trackingId" = "[a-z\d]{4,32}"}
     * )
     */
    public function trackingPixelAction(
        string $trackingId,
        OpenTracker $openTracker,
        LoggerInterface $logger,
        Request $request
    ) {
        $imageContent = $this->getImage($this->kernel->getProjectDir() . "/web/images/email/newdesign/emtrpx.png");
        $binaryTrackingId = @hex2bin($trackingId);

        if ($binaryTrackingId !== false) {
            $openTracker->logOpen($binaryTrackingId, $request);
        } else {
            $logger->warning("failed to decode trackingId: $trackingId");
        }

        return new Response($imageContent, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * @Route(
     *     "/logo/{userId}/{hash}/{booker}/logo.png",
     *     name="aw_email_booker_logo",
     *     requirements={"userId" = "\d+","hash" = "[a-z\d]{40}", "booker" = "\d+"}
     * )
     */
    public function bookingLogoAction(
        $userId,
        $hash,
        $booker,
        AbBookerInfoRepository $abBookerInfoRepository
    ) {
        /** @var AbBookerInfo $bookerInfo */
        $bookerInfo = $abBookerInfoRepository->findOneBy(['UserID' => $booker]);

        if (!$bookerInfo) {
            throw $this->createNotFoundException();
        }

        $image = $this->getImage(
            $this->kernel->getProjectDir() . "/web/" . $bookerInfo->getEmailLogo()
        );

        if (!empty($hash)) {
            $this->verifyEmail($userId, $hash);
        }
        $this->recordAdStat();

        return new Response($image, 200, ['Content-Type' => 'image/png']);
    }

    private function getUserIdFromRequest(Request $request): ?int
    {
        $userName = $request->get('userName');

        if (is_string($userName) && !empty($userName)) {
            $user = $this->userRepository->findOneBy(['login' => $userName]);

            if ($user) {
                return $user->getId();
            }
        }

        return null;
    }

    private function getImage($file)
    {
        if (!file_exists($file)) {
            throw $this->createNotFoundException();
        }
        ob_start();
        @readfile($file);

        return ob_get_clean();
    }

    private function verifyEmail($userId, $hash)
    {
        /** @var Usr $currentUser */
        $currentUser = $this->getUser();
        $isAuth = $this->isGranted('ROLE_USER');

        if (($isAuth && $currentUser->getId() === $userId) || !$isAuth) {
            $user = null;

            if ($isAuth) {
                $user = $currentUser;
            } elseif ($userId > 0) {
                $user = $this->userRepository->find($userId);
            }

            if ($user && $user->getEmailVerificationHash() === $hash) {
                $prevEmailVerified = $user->getEmailverified();
                $user->setEmailverified(EMAIL_VERIFIED);
                $user->setLastemailreaddate(new \DateTime());
                $this->em->flush();

                if ($isAuth) {
                    $this->userManager->refreshToken();
                }

                if ($prevEmailVerified !== EMAIL_VERIFIED) {
                    $this->eventDispatcher->dispatch(new UserEmailVerificationChangedEvent($user));
                }
            }
        }
    }

    private function recordAdStat()
    {
        $request = $this->requestStack->getMasterRequest();
        $adId = $request->get("ad");

        if (isset($adId) && is_numeric($adId)) {
            $ad = $this->socialadRepository->find(intval($adId));

            if ($ad) {
                $this->adManager->recordStat($ad->getSocialadid());
            }
        }
    }
}
