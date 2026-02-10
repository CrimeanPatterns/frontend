<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class EmptyAccounts extends AbstractTemplate
{
    /**
     * @var array
     */
    public $invitees = [];

    /**
     * @var string
     */
    public $coupons;

    public static function getDescription(): string
    {
        return "Empty accounts purged from AwardWallet database";
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('deleteCoupons', CheckboxType::class, [
            'label' => /** @Ignore */ 'Delete coupons',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());

        $template->invitees = [
            [
                'FirstName' => StringHandler::getRandomName()['FirstName'],
                'LastName' => StringHandler::getRandomName()['LastName'],
                'Email' => 'test@gmail.com',
                'EmailVerified' => EMAIL_VERIFIED,
                'cAccounts' => 7,
                'RegistrationIP' => '74.193.78.90',
                'CreationDateTime' => (new \DateTime("-1 year")),
            ],
            [
                'FirstName' => StringHandler::getRandomName()['FirstName'],
                'LastName' => StringHandler::getRandomName()['LastName'],
                'Email' => 'test@hotmail.com',
                'EmailVerified' => EMAIL_UNVERIFIED,
                'cAccounts' => 0,
                'RegistrationIP' => '61.193.100.90',
                'CreationDateTime' => (new \DateTime("-5 day")),
            ],
            [
                'FirstName' => StringHandler::getRandomName()['FirstName'],
                'LastName' => StringHandler::getRandomName()['LastName'],
                'Email' => 'xxxxxxx@gmail.com',
                'EmailVerified' => EMAIL_NDR,
                'cAccounts' => 100,
                'RegistrationIP' => '120.32.100.90',
                'CreationDateTime' => (new \DateTime("-1 month")),
            ],
        ];

        if (isset($options['deleteCoupons']) && $options['deleteCoupons']) {
            $template->coupons = "Invite-123456-RDTIH, Invite-234122-LHFJI, Invite-431234-HFSVS";
        }

        return $template;
    }
}
