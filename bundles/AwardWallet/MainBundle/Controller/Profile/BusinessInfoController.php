<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\BusinessProfileType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BusinessInfoController extends AbstractController
{
    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/user/business-info", name="aw_profile_business_edit", options={"expose"=true})
     * @Template("@AwardWalletMain/Profile/BusinessInfo/index.html.twig")
     * @return array|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        AwTokenStorageInterface $tokenStorage,
        Handler $profileBusinessHandlerDesktop,
        SessionInterface $session,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $userProfileWidget->setActiveItem('business-info');
        $form = $this->createForm(BusinessProfileType::class, $tokenStorage->getBusinessUser());

        if ($profileBusinessHandlerDesktop->handleRequest($form, $request)) {
            $session->getFlashBag()->add(
                'notice',
                $translator->trans(
                    /** @Desc("You have successfully changed your business info") */
                    'notice.business-success-changed'
                )
            );

            return $this->redirect($router->generate('aw_profile_overview_business'));
        }

        return [
            'form' => $form->createView(),
            'user' => $tokenStorage->getToken()->getUser(),
            'business' => $tokenStorage->getBusinessUser(),
        ];
    }
}
