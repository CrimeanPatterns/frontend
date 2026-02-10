<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class PassportExpiration extends AbstractAccountExpirationTemplate
{
    /**
     * @var Providercoupon
     */
    public $passport;

    /**
     * @var int
     */
    public $expiresInMonths = 6;

    public static function getDescription(): string
    {
        return 'Passport expiration notice';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        $builder->add('issueCountryId', ChoiceType::class, [
            'label' => /** @Ignore */ 'Country of Issue',
            'choices' => array_merge([
                /** @Ignore */
                '' => null,
            ], array_flip($container->get(LocalizeService::class)->getLocalizedCountries())),
        ]);
        $builder->add('expiresInMonths', ChoiceType::class, [
            'label' => /** @Ignore */ 'Expires In',
            'choices' => [
                /** @Ignore */
                '6 months' => 6,
                /** @Ignore */
                '9 months' => 9,
                /** @Ignore */
                '12 months' => 12,
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        $template->expiresInMonths = (int) ($options['expiresInMonths'] ?? 6);

        $to = $template->getUser();

        if ($to instanceof Useragent) {
            $user = $to->getAgentid();
            $ua = $to;
        } else {
            $user = $to;
            $ua = null;
        }
        $template->passport = (new Providercoupon());
        Tools::setValue($template->passport, 'providercouponid', rand(1000, 9999));
        $template->passport
            ->setUser($user)
            ->setUserAgent($ua)
            ->setExpirationdate(new \DateTime("-{$template->expiresInMonths} months"))
            ->setProgramname(Providercoupon::DOCUMENT_TYPES[Providercoupon::TYPE_PASSPORT])
            ->setKind(PROVIDER_KIND_DOCUMENT)
            ->setCustomFields([
                'passport' => [
                    'name' => 'My Passport',
                    'number' => '1234567890',
                    'issueDate' => new \DateTime('-1 year'),
                    'country' => $options['issueCountryId'] ?? null,
                ],
            ]);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
