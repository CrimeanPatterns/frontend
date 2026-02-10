<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private TranslatorInterface $translator;
    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/profile/api", name="aw_profile_business_api", methods={"GET"}, options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Profile/api.html.twig")
     */
    public function apiAction(UserProfileWidget $userProfileWidget, $requiresChannel, $host)
    {
        $userProfileWidget->setActiveItem('api');

        $businessInfo = $this->tokenStorage->getBusinessUser()->getBusinessInfo();

        if (empty($businessInfo->getApiKey())) {
            $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\BusinessInfo::class)->generateNewKey($businessInfo);
        }

        $enableDisableForm = $this->createFormBuilder()->add('api_enabled', HiddenType::class, ['data' => $businessInfo->isApiEnabled()])->getForm();
        $keyRegenerateForm = $this->createFormBuilder()->add('api_key', HiddenType::class, ['data' => $businessInfo->getApiKey()])->getForm();

        return [
            'info' => $businessInfo,
            'inviteUrl' => $this->getInviteUrl($requiresChannel, $host),
            'enableDisableForm' => $enableDisableForm->createView(),
            'keyRegenerateForm' => $keyRegenerateForm->createView(),
            'allowIPs' => preg_split('/\s+/', $businessInfo->getApiAllowIp(), -1, PREG_SPLIT_NO_EMPTY),
        ];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/profile/api/switch", name="aw_profile_business_api_switch", methods={"POST"}, options={"expose"=true})
     * @return RedirectResponse|Response
     */
    public function apiEnableDisableAction(Request $request)
    {
        $businessInfo = $this->tokenStorage->getBusinessUser()->getBusinessInfo();

        $form = $this->createFormBuilder()->add('api_enabled', HiddenType::class)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $oldEnabled = $form->get('api_enabled')->getData();

                if ($oldEnabled == $businessInfo->isApiEnabled()) {
                    $businessInfo->setApiEnabled(!$businessInfo->isApiEnabled());
                    $this->entityManager->flush();

                    $this->session->getFlashBag()->add(
                        'notice.business-api',
                        $businessInfo->isApiEnabled() ?
                            $this->translator->trans(/** @Desc("You have successfully enabled API access") */ 'notice.business-api.enabled') :
                            $this->translator->trans(/** @Desc("You have successfully disabled API access") */ 'notice.business-api.disabled')
                    );
                }
            }
        }

        return $this->redirect($this->router->generate('aw_profile_business_api'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/profile/api/regenerate", name="aw_profile_business_api_regenerate", methods={"POST"}, options={"expose"=true})
     * @return RedirectResponse|Response
     */
    public function apiKeyRegenerateAction(Request $request)
    {
        $businessInfo = $this->tokenStorage->getBusinessUser()->getBusinessInfo();

        $form = $this->createFormBuilder()->add('api_key', HiddenType::class)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $oldKey = $form->get('api_key')->getData();

                if ($oldKey == $businessInfo->getApiKey()) {
                    $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\BusinessInfo::class)->generateNewKey($businessInfo);

                    $this->session->getFlashBag()->add(
                        'notice.business-api',
                        $this->translator->trans(/** @Desc("You have successfully regenerated your API Key") */
                            'notice.business-api.regenerated')
                    );
                }
            }
        }

        return $this->redirect($this->router->generate('aw_profile_business_api'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/profile/api/settings", name="aw_profile_business_api_settings", methods={"GET", "POST"}, options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Profile/apiSettings.html.twig")
     * @return array|RedirectResponse|Response
     */
    public function apiSettingsAction(Request $request, UserProfileWidget $userProfileWidget)
    {
        $userProfileWidget->setActiveItem('api');
        $businessInfo = $this->tokenStorage->getBusinessUser()->getBusinessInfo();

        if (empty($businessInfo->getApiKey())) {
            $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\BusinessInfo::class)->generateNewKey($businessInfo);
        }

        $formBuilder = $this->createFormBuilder()
            ->add('api_key', TextType::class, [
/** @Desc("API Key") */ 'label' => 'api.api-key',
                'data' => $businessInfo->getApiKey(),
                'required' => false,
                'attr' => [
                    'style' => 'width: 350px !important',
                    'readonly' => 'readonly',
                ],
            ])
            ->add('allow_ip', TextareaType::class, [
/** @Desc("Allowed IPs") */ 'label' => 'api.allowed-ip',
                'data' => $businessInfo->getApiAllowIp(),
                'required' => false,
                'allow_urls' => true,
                'attr' => [
                    'notice' => $this->translator->trans(
                        /** @Desc("List of IP addresses allowed to call the API. An empty list means &quot;Deny for all&quot;. We are ipv6 compatible, if your client is also IP v6 compatible, this is the address you need to provide. Your current IP address is: <strong>%client_ip%</strong>") */
                        "api.allowed-ip.notice", [
                            '%client_ip%' => $request->getClientIp(),
                        ]
                    ),
                ],
            ]);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $businessInfo->setApiAllowIp($form->get('allow_ip')->getData());
                $this->entityManager->flush();
                $this->session->getFlashBag()->add(
                    'notice.business-api',
                    $this->translator->trans('notice.regional-success-changed')
                );

                return $this->redirect($this->router->generate('aw_profile_business_api'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/profile/api/callback_settings", name="aw_profile_business_api_callback_settings", methods={"GET", "POST"}, options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Profile/apiCallbackSettings.html.twig")
     * @return array|RedirectResponse|Response
     */
    public function apiCallbackSettingsAction(Request $request, UserProfileWidget $userProfileWidget, $requiresChannel, $host)
    {
        $userProfileWidget->setActiveItem('api');
        $businessInfo = $this->tokenStorage->getBusinessUser()->getBusinessInfo();

        if (!$businessInfo->isApiInviteEnabled()) {
            throw $this->createNotFoundException();
        }

        $formBuilder = $this->createFormBuilder()
            ->add('invite_url', TextType::class, [
                'label' => /** @Desc("End user invite URL") */
                'api.invite-url',
                'data' => $this->getInviteUrl($requiresChannel, $host),
                'required' => false,
                'allow_urls' => true,
                'attr' => [
                    'readonly' => 'readonly',
                ],
            ])
            ->add('callback_url', TextType::class, [
/** @Desc("Callback URL") */ 'label' => 'api.callback-url',
                'data' => $businessInfo->getApiCallbackUrl(),
                'required' => true,
                'allow_urls' => true,
                'attr' => [
                    'notice' => $this->translator->trans(/** @Desc("Where do you want to redirect the user after they authorize your access?") */ "api.callback-url.notice"),
                ],
                'constraints' => [
                    new Url(),
                ],
            ]);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $businessInfo->setApiCallbackUrl($form->get('callback_url')->getData());
                $this->entityManager->flush();
                $this->session->getFlashBag()->add(
                    'notice.business-api',
                    $this->translator->trans('notice.regional-success-changed')
                );

                return $this->redirect($this->router->generate('aw_profile_business_api'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @return string
     */
    private function getInviteUrl($requiresChannel, $host)
    {
        // https://awardwallet.com/m/connections/approve/jfrzrtrefn/2
        return $requiresChannel . "://"
            . $host
            . '/m/connections/approve/'
            . implode('/', [$this->tokenStorage->getBusinessUser()->getRefcode(), ACCESS_READ_ALL]);
    }
}
