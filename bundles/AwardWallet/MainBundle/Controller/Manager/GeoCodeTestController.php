<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\Common\Geo\GoogleGeo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class GeoCodeTestController
{
    private ServiceLocator $geoCoders;

    public function __construct(ServiceLocator $geoCoders)
    {
        $this->geoCoders = $geoCoders;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_TEST_GEOCODE')")
     * @Route("/manager/test-geocode")
     */
    public function testGeoCodeAction(Environment $twig, FormBuilderInterface $builder, Request $request)
    {
        $builder->add('address', TextType::class, [
            'label' => "Address",
            'attr' => ['style' => 'width: 500px;'],
        ]);

        $coders = array_keys($this->geoCoders->getProvidedServices());
        $builder->add('source', ChoiceType::class, [
            'choices' => array_combine($coders, $coders),
            'data' => 'aw.geo.coder.default',
        ]);
        $builder->add('Check', SubmitType::class);

        $form = $builder->getForm();
        $result = null;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var GoogleGeo $source */
            $source = $this->geoCoders->get($data['source']);
            $result = $source->FindGeoTag($data['address'], null, 0, true);
        }

        return new Response($twig->render("@AwardWalletMain/Manager/testGeoCode.html.twig", ["form" => $form->createView(), "result" => $result]));
    }
}
