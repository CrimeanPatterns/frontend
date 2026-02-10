<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\RelaySelector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class MailRelayController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_MAIL_RELAY')")
     * @Route("/manager/mail-relay", name="aw_manager_mail_relay")
     */
    public function mailRelayAction(Request $request, ParameterRepository $parameterRepository, Environment $twig): Response
    {
        $choices = [
            'Sparkpost' => '',
            'Amazon' => 'amazon',
        ];

        $builder = $this->createFormBuilder();
        $builder->add('relay', ChoiceType::class, [
            'label' => "Mail Relay",
            'required' => false,
            'choices' => $choices,
        ]);
        $builder->add('update', SubmitType::class, ['label' => 'Update']);

        $data = [
            'relay' => $parameterRepository->getParam(RelaySelector::RELAY_PARAM_NAME),
        ];
        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $parameterRepository->setParam(RelaySelector::RELAY_PARAM_NAME, $data['relay']);
            $this->addFlash('notice', 'Relay changed to ' . array_flip($choices)[$data['relay']]);
        }

        return new Response($twig->render(
            '@AwardWalletMain/Manager/SimpleForm/view.html.twig',
            [
                "title" => "Mail Relay",
                "description" => "Set default smtp relay",
                "form" => $form->createView(),
            ]
        ));
    }
}
