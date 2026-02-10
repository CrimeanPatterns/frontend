<?php

namespace AwardWallet\MainBundle\Service\MileValue\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\MileValue\Async\FlightSearchTask;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\CombinedPriceSource;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\PriceSourceInterface;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\SearchRoute;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use JMS\TranslationBundle\Annotation\Ignore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FlightPricesController
{
    /**
     * @var iterable
     */
    private $priceSources;

    public function __construct(iterable $mileValuePriceSources)
    {
        $this->priceSources = $mileValuePriceSources;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_MILEVALUE')")
     * @Route("/manager/flight-prices")
     */
    public function testAction(FormFactoryInterface $formFactory, Request $request, Environment $twig, Process $asyncProcess, AwTokenStorage $tokenStorage, Client $messaging)
    {
        ini_set('memory_limit', '2048M');
        $builder = $formFactory->createBuilder();
        $sourceClasses = it($this->priceSources)->map(function (PriceSourceInterface $priceSource) { return basename(get_class($priceSource)); })->toArray();
        $sourceNames = it($sourceClasses)->map(function (string $class) { return substr($class, strrpos($class, '\\') + 1); })->toArray();

        $builder->add('Sources', ChoiceType::class, [
            'constraints' => [
                new NotBlank(),
            ],
            'choices' => /** @Ignore */ array_combine($sourceNames, $sourceClasses),
            'multiple' => true,
        ]);
        $builder->add('From', TextType::class, ['constraints' => [
            new NotBlank(),
            new Regex('/^\w\w\w$/ims'),
        ]]);
        $builder->add('To', TextType::class, ['constraints' => [
            new NotBlank(),
            new Regex('/^\w\w\w$/ims'),
        ]]);
        $builder->add('DepartureDate', DateType::class);
        $builder->add('ReturnDate', DateType::class);
        $builder->add('ClassOfService', ChoiceType::class, ['choices' => /** @Ignore */ [array_combine(Constants::CLASSES_OF_SERVICE, Constants::CLASSES_OF_SERVICE)]]);
        $builder->add('RouteType', ChoiceType::class, ['choices' => [/** @Ignore */ 'One way' => Constants::ROUTE_TYPE_ONE_WAY, /** @Ignore */ 'Round trip' => Constants::ROUTE_TYPE_ROUND_TRIP]]);
        $builder->add('Duration', IntegerType::class, ['label' => /** @Ignore */ 'Trip duration in hours']);
        $builder->add('Passengers', IntegerType::class);

        $builder->add('Search', SubmitType::class, ['label' => /** @Ignore */ 'Search']);

        $data = [
            'Sources' => [CombinedPriceSource::class],
            'From' => 'JFK',
            'To' => 'LAX',
            'DepartureDate' => new \DateTime("+1 week"),
            'ReturnDate' => new \DateTime("+2 week"),
            'RouteType' => Constants::ROUTE_TYPE_ONE_WAY,
            'Duration' => 1000,
            'ClassOfService' => Constants::CLASS_ECONOMY,
            'Passengers' => 1,
        ];

        $builder->setData($data);

        $form = $builder->getForm();
        $form->handleRequest($request);

        $channel = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $channel = UserMessaging::getChannelName('flightprices' . bin2hex(random_bytes(3)), $tokenStorage->getUser()->getUserid());
            $routes = [new SearchRoute($data['From'], $data['To'], $data['DepartureDate']->getTimestamp())];

            if ($data['RouteType'] === Constants::ROUTE_TYPE_ROUND_TRIP) {
                $routes[] = new SearchRoute($data['To'], $data['From'], $data['ReturnDate']->getTimestamp());
            }
            $task = new FlightSearchTask($data['Sources'], $routes, $data['ClassOfService'], $data['Passengers'], $data['Duration'], $channel);
            $asyncProcess->execute($task);
        }

        return new Response($twig->render("@AwardWalletMain/Manager/flightSearchResults.html.twig", [
            "title" => "Search Flight Prices",
            "form" => $form->createView(),
            "channel" => $channel,
            'centrifuge_config' => $messaging->getClientData(),
        ]));
    }
}
