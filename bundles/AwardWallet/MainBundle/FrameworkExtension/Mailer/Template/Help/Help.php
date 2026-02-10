<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Help;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Help extends AbstractTemplate
{
    use AdvtTrait;

    public array $accounts = [];

    public ?Useragent $familyMember = null;

    public static function getDescription(): string
    {
        return 'Macro help';
    }

    public static function getStatus(): int
    {
        return self::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static();

        $template->toUser(Tools::createUser());
        $familyMember = Tools::createFamilyMember($template->getUser());
        $template->familyMember = $familyMember;

        // Account #1
        $account = Tools::createAccount($template->getUser(), Tools::createProvider(), 1234567.6);
        $account->setUseragentid($familyMember);
        $template->accounts[] = $account;

        // Account #2
        $account = Tools::createAccount($template->getUser(), Tools::createProvider(
            'Jessica Corporation',
            'Sweet & Beautiful Dreams'
        ), 300);
        $account->setUseragentid($familyMember);
        $template->accounts[] = $account;

        // Account #3
        $account = Tools::createAccount(
            $template->getUser(),
            $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneByCode('delta'),
            lcg_value() * abs(100000)
        );
        $account->setUseragentid($familyMember);
        $template->accounts[] = $account;

        // Provider coupon
        $coupon = Tools::createProviderCoupon($template->getUser(), "My Program", "Description...", "250k");
        $template->accounts[] = $coupon;

        $template->advt = Tools::createSocialAd();

        return $template;
    }
}
