<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Security\SiegeModeDetector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class SiegeModeController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_SIEGE_MODE')")
     * @Route("/manager/security/siege-mode", name="aw_manager_siege_mode")
     */
    public function siegeModeAction(Request $request, ParameterRepository $parameterRepository, Environment $twig): Response
    {
        $choices = [
            'Auto' => '',
            'Enabled' => '1',
            'Disabled' => '0',
        ];

        $builder = $this->createFormBuilder();
        $builder->add('siegeMode', ChoiceType::class, [
            'label' => "Siege Mode",
            'required' => false,
            'choices' => $choices,
        ]);
        $builder->add('update', SubmitType::class, ['label' => 'Update']);

        $data = [
            'siegeMode' => $parameterRepository->getParam(SiegeModeDetector::SIEGE_MODE_PARAM_NAME),
        ];
        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $parameterRepository->setParam(SiegeModeDetector::SIEGE_MODE_PARAM_NAME, $data['siegeMode']);
            $this->addFlash('notice', 'Siege mode set to ' . array_flip($choices)[$data['siegeMode']]);
        }

        return new Response($twig->render(
            '@AwardWalletMain/Manager/SimpleForm/view.html.twig',
            [
                "title" => "Siege Mode",
                "description" => "Display catcha on login",
                "form" => $form->createView(),
            ]
        ));
    }
}
