<?php

namespace AwardWallet\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PressReleaseController extends AbstractController
{
    public const FORUM_NUMBER = 9; // move to constants!

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route(
     *     "/pr/",
     *     name="aw_pressrelease_index",
     *     defaults={"_canonical" = "aw_pressrelease_index_locale", "_alternate" = "aw_pressrelease_index_locale"}
     * )
     * @Route("/{_locale}/pr/",
     *      name="aw_pressrelease_index_locale",
     *      defaults={"_locale"="en", "_canonical" = "aw_pressrelease_index_locale", "_alternate" = "aw_pressrelease_index_locale"},
     *      requirements={"_locale" = "%route_locales%"}
     * )
     */
    public function indexAction()
    {
        $forumRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Forum::class);
        $pressReleases = $forumRep->findBy(['forumnumber' => self::FORUM_NUMBER], [
            'posttime' => 'DESC',
            'rank' => 'ASC',
        ]);
        $releasesData = [];
        $isStaff = (true === $this->authorizationChecker->isGranted('ROLE_STAFF'));

        foreach ($pressReleases as $pressRelease) {
            if ($pressRelease->getVisible() || $isStaff) {
                $releasesData[] = [
                    'id' => $pressRelease->getForumid(),
                    'title' => $pressRelease->getTitle(),
                    'date' => $pressRelease->getPosttime(),
                    'description' => $pressRelease->getEmail(),
                    'visible' => $pressRelease->getVisible(),
                ];
            }
        }

        if (empty($releasesData)) {
            throw $this->createNotFoundException();
        }

        return $this->render('@AwardWalletMain/PressRelease/indexND.html.twig', [
            'pressReleases' => $releasesData,
        ]);
    }

    /**
     * @Route(
     *     "/pr/{pressReleaseId}",
     *     name="aw_pressrelease_view",
     *     requirements={"pressReleaseId" = "\d+"},
     *     defaults={"_canonical" = "aw_pressrelease_view_locale", "_alternate" = "aw_pressrelease_view_locale"},
     * )
     * @Route("/{_locale}/pr/{pressReleaseId}",
     *      name="aw_pressrelease_view_locale",
     *      defaults={"_locale"="en"},
     *      requirements={"pressReleaseId" = "\d+", "_locale" = "%route_locales%"}
     * )
     */
    public function viewAction($pressReleaseId)
    {
        $forumRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Forum::class);
        $pressRelease = $forumRep->findOneBy([
            'forumnumber' => self::FORUM_NUMBER,
            'forumid' => $pressReleaseId,
        ]);
        $isStaff = (true === $this->authorizationChecker->isGranted('ROLE_STAFF'));

        if (!isset($pressRelease)
        || (!$isStaff && !$pressRelease->getVisible())) {
            throw $this->createNotFoundException();
        }

        return $this->render('@AwardWalletMain/PressRelease/viewND.html.twig', [
            'pressRelease' => [
                'id' => $pressRelease->getForumid(),
                'title' => $pressRelease->getTitle(),
                'body' => $pressRelease->getBodytext(),
                'date' => $pressRelease->getPosttime(),
                'visible' => $pressRelease->getVisible(),
            ],
        ]);
    }
}
