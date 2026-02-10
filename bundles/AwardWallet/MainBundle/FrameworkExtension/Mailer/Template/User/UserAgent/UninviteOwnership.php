<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class UninviteOwnership extends AbstractTemplate
{
    /**
     * @var Useragent
     */
    public $invitee;

    /**
     * @var Account[]|Providercoupon[]
     */
    public $accounts = [];

    public static function getDescription(): string
    {
        return 'Invitation to claim ownership of accounts (for inviter)';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('withAccounts', CheckboxType::class, [
            'required' => false,
            /** @Ignore */
            'label' => 'With accounts',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $user = Tools::createUser();
        $familyMember = Tools::createFamilyMember($user);
        $template = new static($user);
        $template->invitee = $familyMember;

        if (isset($options['withAccounts']) && $options['withAccounts']) {
            // Account #1
            $account = Tools::createAccount($user, Tools::createProvider(), 1234567.6);
            $account->setUseragentid($familyMember);
            $template->accounts[] = $account;

            // Account #2
            $account = Tools::createAccount($user, Tools::createProvider(
                'Jessica Corporation', 'Sweet & Beautiful Dreams'
            ), 300);
            $account->setUseragentid($familyMember);
            $template->accounts[] = $account;

            // Account #3
            $account = Tools::createAccount($user, $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)
                ->findOneByCode('delta'), lcg_value() * abs(100000));
            $account->setUseragentid($familyMember);
            $template->accounts[] = $account;
        }

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
