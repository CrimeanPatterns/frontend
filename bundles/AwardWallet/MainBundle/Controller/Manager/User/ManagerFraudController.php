<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class ManagerFraudController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_LIMITED_EDIT_USER')")
     * @Route("/manager/fraud/{userid}", name="aw_manager_fraud", requirements={"userid": "\d+"})
     * @ParamConverter("user", class="AwardWalletMainBundle:Usr")
     */
    public function fraudAction(
        Request $request,
        Usr $user,
        LoggerInterface $logger,
        EntityManagerInterface $em
    ) {
        $builder = $this->createFormBuilder();

        $builder->add('fraud', CheckboxType::class, [
            'label' => "Fraud",
            'required' => false,
        ]);
        $builder->add('referer', HiddenType::class);
        $data = [
            'fraud' => $user->isFraud(),
            'referer' => $request->headers->get('referer') ? urlPathAndQuery($request->headers->get('referer')) : null,
        ];

        $builder->add('Save', SubmitType::class);
        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $logger->info("User {$user->getId()} fraud status changed to " . ($data['fraud'] ? 'true' : 'false'));
            $user->setFraud((bool) $data['fraud']);
            $em->flush();

            if ($data['referer']) {
                return $this->redirect(urlPathAndQuery($data['referer']));
            }
        }

        return $this->render("@AwardWalletMain/Manager/User/simple_form.html.twig", [
            'title' => "Fraud for User " . $user->getId(),
            "form" => $form->createView(),
            "userId" => $user->getId(),
        ]);
    }
}
