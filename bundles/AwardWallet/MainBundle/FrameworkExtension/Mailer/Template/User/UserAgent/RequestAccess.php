<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class RequestAccess extends AbstractTemplate
{
    /**
     * @var Usr
     */
    public $agent;

    /**
     * @var Account[]|Providercoupon[]
     */
    public $accounts = [];

    /**
     * @var string
     */
    public $code;

    /**
     * @var bool
     */
    public $full;

    public static function getDescription(): string
    {
        return 'Request access to accounts';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('withAccounts', CheckboxType::class, [
            'required' => false,
            /** @Ignore */
            'label' => 'With accounts',
        ]);
        $builder->add('full', CheckboxType::class, [
            'required' => false,
            /** @Ignore */
            'label' => 'Full Control',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());
        $template->agent = Tools::createUser(ACCOUNT_LEVEL_BUSINESS);

        if (isset($options['withAccounts']) && $options['withAccounts']) {
            // Account #1
            $account = Tools::createAccount($user, Tools::createProvider(), 1234567.6);
            $template->accounts[] = $account;

            // Account #2
            $account = Tools::createAccount($user, Tools::createProvider(
                'Jessica Corporation', 'Sweet & Beautiful Dreams'
            ), 300);
            $template->accounts[] = $account;

            // Account #3
            $account = Tools::createAccount($user, $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)
                ->findOneByCode('delta'), lcg_value() * abs(100000));
            $template->accounts[] = $account;
        }

        $template->code = 'xxxyyyzzz';
        $template->full = isset($options['full']) && $options['full'];

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
