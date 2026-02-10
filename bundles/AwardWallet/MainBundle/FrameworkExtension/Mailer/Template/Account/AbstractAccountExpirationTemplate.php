<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

abstract class AbstractAccountExpirationTemplate extends AbstractTemplate
{
    public const TO_PERSONAL = 1;
    public const TO_FM = 2;
    public const TO_BUSINESS_ADMIN = 3;

    /**
     * @var Usr if account owner - business, but the recipient of the email - admin
     */
    public $businessRecipient;

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('to', ChoiceType::class, [
            /** @Ignore */
            'label' => 'To',
            'choices' => [
                /** @Ignore */
                'Personal' => self::TO_PERSONAL,
                /** @Ignore */
                'Family member' => self::TO_FM,
                /** @Ignore */
                'Business admin' => self::TO_BUSINESS_ADMIN,
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = [])
    {
        // personal account
        $user = Tools::createUser(ACCOUNT_LEVEL_AWPLUS);

        // family member
        $familyMember = Tools::createFamilyMember($user);

        $options['to'] = !isset($options['to']) ? self::TO_PERSONAL : $options['to'];

        if ($options['to'] == self::TO_PERSONAL) {
            $template = new static($user, false);
        } elseif ($options['to'] == self::TO_FM) {
            $template = new static($familyMember, false);
        } else {
            $business = clone $user;
            $business->setAccountlevel(ACCOUNT_LEVEL_BUSINESS);
            $template = new static($user, true);
            $template->businessRecipient = $business;
        }

        return $template;
    }
}
