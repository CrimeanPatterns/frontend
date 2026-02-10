<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Service\ItineraryMail\Formatter;
use AwardWallet\MainBundle\Service\ItineraryMail\Itinerary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

class CheckIn extends AbstractItineraryTemplate
{
    public static function getDescription(): string
    {
        return "Online check-in reminder";
    }

    public static function getStatus(): int
    {
        return AbstractTemplate::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        $builder->add('withAccount', CheckboxType::class, [
            'required' => false,
            'label' => /** @Ignore */ 'With LP account',
        ]);
        $builder->add('AdTripID', TextType::class, [
            'label' => /** @Ignore */ 'Advert for TripID',
            'label_attr' => [
                'title' => /** @Ignore */ 'Эмулируется ситуация вылета по трипу. Для выборки. Он не будет показан в письме. 
                В рассылку попадает реклама только по тому провайдеру, который связан с перелетом.',
            ],
        ]);
        $builder->remove('AdItID');

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        // Airlines
        $trip = Tools::createTrip(
            "GSQ123",
            TRIP_CATEGORY_AIR,
            $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find(161)
        );
        // # Segment 1
        $tripSeg1 = Tools::createTripSegment();
        $tripSeg1->setMarketingAirlineConfirmationNumber("GSQ123");
        $tripSeg1->setDepname("Seattle");
        $tripSeg1->setDepcode("SEA");
        $tripSeg1->setDepartureDate(new \DateTime("+24 hour"));
        $tripSeg1->setArrname("New York");
        $tripSeg1->setArrcode("JFK");
        $tripSeg1->setArrivalDate(new \DateTime("+28 hour"));
        $tripSeg1->setAirlineName("Singapore Airlines");
        $tripSeg1->setFlightNumber("F323");
        $tripSeg1->setCabinClass("Economy / Coach");
        $tripSeg1->setSeats(["A12", "B13"]);
        $tripSeg1->setDepartureTerminal("2");
        $tripSeg1->setDepartureGate("14");
        $tripSeg1->setArrivalTerminal("B");
        $tripSeg1->setArrivalGate("2");
        $tripSeg1->setAircraftName("Boeing Douglas MD-90");
        $tripSeg1->setDuration("5h 20m");
        $tripSeg1->setMeal("Standard");
        $tripSeg1->setTraveledMiles("1542");
        $tripSeg1->setTripid($trip);
        $trip->addSegment($tripSeg1);
        $it = new Itinerary();
        $it->setEntity($trip);
        $segments = $container->get(Formatter::class)->format($tripSeg1);
        $it->setSegments($segments);
        $template->itineraries = [$it];

        if (isset($options['withAccount']) && $options['withAccount']) {
            $user = null;
            $to = $template->getUser();

            if ($to instanceof Usr) {
                $user = $to;
            } elseif ($to instanceof Useragent) {
                $user = $to->getAgentid();
            }
            $account = Tools::createAccount($user, $provider = Tools::createProvider(), 12345);
            $trip->setAccount($account);
            $trip->setRealProvider($provider);
        }

        if (isset($options['AdTripID'])) {
            $options['AdTripID'] = preg_replace("/[^\d]/ims", "", $options['AdTripID']);
            $template->advt = Tools::getAdvtByItineraryId($container, "T." . $options['AdTripID'], static::getEmailKind());
        }

        return $template;
    }
}
