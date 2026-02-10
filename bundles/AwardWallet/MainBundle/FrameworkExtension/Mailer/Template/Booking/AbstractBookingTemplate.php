<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

abstract class AbstractBookingTemplate extends AbstractTemplate
{
    /**
     * @var AbRequest
     */
    public $request;

    /**
     * @var bool confirm AbRequest or not
     */
    public $confirm = false;

    /**
     * @var bool to user or booker
     */
    public $toBooker = false;

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        Tools::addMerchantForm($builder, $container);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = [])
    {
        $bookerInfoRep = $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class);
        $bookerInfo = !isset($options['Merchant']) || empty($options['Merchant']) ? Tools::getDefaultMerchant($container) : $bookerInfoRep->find($options['Merchant']);

        $request = Tools::createAbRequest("John Doggett");
        $passenger = Tools::createAbPassenger("Billy", "Villy", "M", "US", "-25 year");
        $request->addPassenger($passenger);
        $passenger = Tools::createAbPassenger("Jessica", "Villy", "F", "Russian", "-20 year");
        $request->addPassenger($passenger);
        $segment = Tools::createAbSegment("MDW", "GSO", "+1 day", "+3 day", "+2 day", "+10 day", "+12 day", "+11 day");
        $segment->setRoundTrip(1);
        $segment->setDepCheckOtherAirports(true);
        $segment->setArrCheckOtherAirports(true);
        $request->addSegment($segment);
        $request->addCustomProgram(Tools::createAbCustomProgram('Custom Program #1', 5000, 'Billy Villy'));
        $request->addCustomProgram(Tools::createAbCustomProgram('Custom Program #2', 200.2, 'Billy Villy'));

        $account = Tools::createAccount($user = Tools::createUser(), Tools::createProvider(), 123456.8);
        $accountProgram = Tools::createAbAccountProgram($account);
        $request->addAccount($accountProgram);
        $request->setUser($user);

        $request->setBooker($bookerInfo->getUserID());

        $template = new static();
        $template->request = $request;
        $template->confirm = true;

        return $template;
    }
}
