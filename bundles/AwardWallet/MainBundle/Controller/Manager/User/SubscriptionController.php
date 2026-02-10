<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_CANCEL_SUBSCRIPTION')")
     * @Route("/manager/billing/cancel-subscription/{userid}", name="aw_manager_cancel_subscription", requirements={"userid": "\d+"})
     * @ParamConverter("user", class="AwardWalletMainBundle:Usr")
     */
    public function cancelSubscriptionAction(Request $request, Usr $user, RecurringManager $recurringManager)
    {
        if ($user->isBusiness()) {
            throw $this->createNotFoundException('Business users cannot cancel subscriptions');
        }

        $error = null;

        if (!$user->hasAnyActiveSubscription()) {
            $error = 'User does not have any active subscription';
        } elseif (!$user->canCancelActiveSubscription()) {
            if ($user->hasActiveIosSubscription()) {
                $error = 'User has active iOS subscription which cannot be cancelled automatically.';
            } else {
                $error = 'User has active subscription which cannot be cancelled automatically.';
            }
        }

        if ($error) {
            return $this->render('@AwardWalletMain/Manager/User/cancelSubscription.html.twig', [
                'error' => $error,
                'user' => $user,
            ]);
        }

        $form = $this->createFormBuilder()
            ->add('referer', HiddenType::class)
            ->add('save', SubmitType::class, ['label' => 'Cancel Subscription'])
            ->setData([
                'referer' => $request->headers->get('referer') ? urlPathAndQuery($request->headers->get('referer')) : null,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $recurringManager->cancelRecurringPayment($user);

            if ($data['referer']) {
                return $this->redirect(urlPathAndQuery($data['referer']));
            }
        }

        return $this->render('@AwardWalletMain/Manager/User/cancelSubscription.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
