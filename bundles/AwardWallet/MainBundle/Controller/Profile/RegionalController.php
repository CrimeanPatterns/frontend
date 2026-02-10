<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\ProfileRegionalType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/user")
 */
class RegionalController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/regional", name="aw_profile_regional", options={"expose"=true})
     * @Template("@AwardWalletMain/Profile/Regional/index.html.twig")
     */
    public function indexAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        AwTokenStorageInterface $tokenStorage,
        Handler $formProfileRegionalHandlerDesktop,
        TranslatorInterface $translator
    ) {
        $userProfileWidget->setActiveItem('regional');
        $user = $tokenStorage->getUser();

        if (!$user instanceof Usr) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ProfileRegionalType::class, $user);

        if ($formProfileRegionalHandlerDesktop->handleRequest($form, $request)) {
            $request->getSession()->getFlashBag()->add(
                'notice',
                $translator->trans(
                    /** @Desc("You have successfully changed your settings") */ 'notice.regional-success-changed'
                )
            );

            return $this->redirectToRoute('aw_profile_overview');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
