<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Manager\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ImpersonateController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/manager/impersonate", name="aw_manager_impersonate")
     */
    public function impersonateAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        UserManager $userManager
    ) {
        if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
            return $this->redirect($this->generateUrl("aw_users_logout", ["BackTo" => $this->generateUrl("aw_manager_impersonate")]));
        }

        if (
            !$authorizationChecker->isGranted('ROLE_MANAGE_IMPERSONATE')
            || (
                $authorizationChecker->isGranted('SITE_MANAGER_2FA_REQUIRED')
                && !$authorizationChecker->isGranted('USER_2FA_ENABLED')
            )
        ) {
            throw new AccessDeniedHttpException();
        }

        $builder = $this->createFormBuilder();

        $builder->add('UserID', TextType::class, [
            'label' => "User ID / Login / Email",
            'allow_urls' => true,
        ]);
        $builder->add('Mobile', CheckboxType::class, ["required" => false]);
        $builder->add('AwPlus', CheckboxType::class, ["required" => false]);
        $builder->add('Goto', TextType::class, ["required" => false, 'allow_urls' => true]);

        $data = [
            'UserID' => $request->query->get("UserID"),
            'AwPlus' => !empty($request->query->get("AwPlus")),
            'Mobile' => !empty($request->query->get("Mobile")),
            'Goto' => $request->query->get("Goto", '/'),
        ];

        if ($authorizationChecker->isGranted('FULL_IMPERSONATE')) {
            $builder->add('Full', CheckboxType::class, ['label' => 'Full impersonate', "required" => false]);
            $data['Full'] = !empty($request->query->get("Full"));
        }

        $builder->add('Impersonate', SubmitType::class, ['label' => 'Impersonate']);
        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if (($form->isSubmitted() && $form->isValid()) || $request->query->has('AutoSubmit')) {
            $data = $form->getData();

            try {
                $user = $userManager->findUser($data["UserID"], true);
                $goto = $data['Mobile'] ? '/m' : $data['Goto'];

                return $userManager->impersonate($user, !empty($data['Full']), $data['AwPlus'], $goto);
            } catch (AuthenticationException $e) {
                $form->addError(new FormError("We could not find user '{$data['UserID']}'"));
            }
        }

        return $this->render("@AwardWalletMain/Manager/Impersonate/impersonate.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_IMPERSONATE')")
     * @Route("/manager/impersonate-account/{account}", name="aw_manager_impersonate_account", requirements={"account"="\d+"})
     */
    public function impersonateAccountAction(
        Account $account
    ) {
        return $this->redirectToRoute("aw_manager_impersonate", [
            "UserID" => $account->getUser()->getId(),
            "Goto" => $this->generateUrl("aw_account_list", ["account" => $account->getAccountID()]),
        ]);
    }
}
