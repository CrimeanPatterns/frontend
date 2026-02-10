<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\ThemeResolver;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebsiteSettingsType extends AbstractType implements TranslationContainerInterface
{
    public const APPEARANCE_CHOICES = [
        'auto' => self::AUTO_MODE,
        'dark_mode' => ThemeResolver::THEME_DARK,
        'light_mode' => ThemeResolver::THEME_LIGHT,
    ];

    private const AUTO_MODE = '';

    /**
     * @var Router
     */
    private $router;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var UserMailboxCounter
     */
    private $mailboxCounter;

    public function __construct(RouterInterface $router, TranslatorInterface $translator, UserMailboxCounter $mailboxCounter)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->mailboxCounter = $mailboxCounter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Usr $user */
            $user = $event->getData();
            $form = $event->getForm();

            if ($user->twoFactorAllowed()) {
                $form->add('2factorLink', ProfileButtonType::class, [
                    'mapped' => false,
                    'label' => 'personal_info.site_settings.2factor',
                    'value' => /** @Ignore */ $this->translator->trans($user->enabled2Factor() ? 'turned_on' : 'turned_off'),
                    'link' => $this->router->generate('aw_profile_2factor'),
                    'link_label' => /** @Ignore */ $this->translator->trans($user->enabled2Factor() ? 'turn_off' : 'set_up'),
                ]);
            }

            $choices = [
                "account.choice.save.password.with.aw" => SAVE_PASSWORD_DATABASE,
                "account.choice.save.password.locally" => SAVE_PASSWORD_LOCALLY,
            ];
            $form->add('savepassword', ChoiceType::class, [
                'label' => 'default_password_storage',
                'choices' => $choices,
                'constraints' => [
                    new Assert\Choice([
                        'choices' => $choices,
                    ]),
                ],
            ]);

            $form->add('autogatherplans', CheckboxType::class, [
                'label' => 'personal_info.site_settings.gather_plans',
            ]);

            $form->add('splashadsdisabled', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'show-screen-ads-after-logging',
                    ['%awplus_only%' => ' (' . $this->translator->trans('awplus_only') . ')']
                ),
                'disabled' => !$user->isAwPlus(),
                'mapped' => $user->isAwPlus(),
                'data' => !$user->isAwPlus() || !$user->isSplashAdsDisabled(), // see FormEvents::POST_SUBMIT
            ]);

            $form->add('linkadsdisabled', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'use-affiliate-links-autologin',
                    ['%awplus_only%' => ' (' . $this->translator->trans('awplus_only') . ')']
                ),
                'help' => 'keeping-option-checked-addit-revenue',
                'disabled' => !$user->isAwPlus(),
                'mapped' => $user->isAwPlus(),
                'data' => !$user->isAwPlus() || !$user->isLinkAdsDisabled(), // see FormEvents::POST_SUBMIT
            ]);

            $form->add('listadsdisabled', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'show-card-ads-accountlist',
                    ['%awplus_only%' => ' (' . $this->translator->trans('awplus_only') . ')']
                ),
                'disabled' => !$user->isAwPlus(),
                'mapped' => $user->isAwPlus(),
                'data' => !$user->isAwPlus() || !$user->isListAdsDisabled(), // see FormEvents::POST_SUBMIT
            ]);

            $form->add('isBlogPostAds', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'show-ads-blog-post-awplus-only',
                    ['%awplus_only%' => ' (' . $this->translator->trans('awplus_only') . ')']
                ),
                'help' => 'keeping-feature-aw-addit-revenue',
                'disabled' => !$user->isAwPlus(),
                'mapped' => $user->isAwPlus(),
                'data' => !$user->isAwPlus() || $user->isBlogPostAds(),
            ]);

            /*
                        $form->add('oldsite', ProfileButtonType::class, [
                            'mapped' => false,
                            'label' => 'interface_design',
                            'value' => $this->translator->trans('new_style'),
                            'link' => $this->router->generate('aw_user_old_interface_switch'),
                            'link_label' => 'switch_old_style',
                        ]);
            */

            $form->add('appearance', ChoiceType::class, [
                'label' => 'appearance',
                'mapped' => false,
                'choices' => self::APPEARANCE_CHOICES,
                'constraints' => [
                    new Assert\Choice([
                        'choices' => self::APPEARANCE_CHOICES,
                    ]),
                ],
            ]);

            if ($user->hasRole('ROLE_STAFF')) {
                $form->add('mailboxLink', ProfileButtonType::class, [
                    'mapped' => false,
                    'label' => 'personal_info.site_settings.email_scanner',
                    'value' => $this->translator->trans('personal_info.site_settings.email_scanner.mailboxes'),
                    'valueCounter' => $this->mailboxCounter->onlyMy($user->getId()),
                    'link' => $this->router->generate('aw_usermailbox_view'),
                    'link_label' => 'personal_info.site_settings.add_mailboxes',
                ]);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var Usr $user */
                $user = $event->getData();

                if ($user->isAwPlus()) {
                    // reason for inversion, new labels, see #22853
                    $user->setSplashAdsDisabled(!$user->isSplashAdsDisabled());
                    $user->setLinkAdsDisabled(!$user->isLinkAdsDisabled());
                    $user->setListAdsDisabled(!$user->isListAdsDisabled());
                }

                $event->setData($user);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Usr',
            'required' => false,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'websettings';
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('turned_off'))->setDesc('Turned off'),
            (new Message('turned_off.autenification'))->setDesc('Turned off'),
            (new Message('turned_on'))->setDesc('Turned on'),
            (new Message('turn_off'))->setDesc('Turn off'),
            (new Message('turn_on'))->setDesc('Turn on'),
            (new Message('personal_info.site_settings.disable_extension.note'))->setDesc('Please note that by disabling AwardWallet extension you lose many of the AwardWallet features. If you change your browser or clear your cookies you will need to check this checkbox again.'),
            (new Message('interface_design'))->setDesc('Interface design'),
            (new Message('new_style'))->setDesc('New style'),
            (new Message('switch_old_style'))->setDesc('Switch to Old style'),
            (new Message('appearance'))->setDesc('Appearance'),
            (new Message('dark_mode'))->setDesc('Dark'),
            (new Message('light_mode'))->setDesc('Light'),
            (new Message('show-screen-ads-after-logging'))->setDesc('Show splash screen ads and promos immediately after logging in %awplus_only%'),
            (new Message('use-affiliate-links-autologin'))->setDesc('Use affiliate links during auto-login %awplus_only%'),
            (new Message('keeping-option-checked-addit-revenue'))->setDesc('By keeping this option checked, you are helping AwardWallet make additional revenue.'),
            (new Message('show-card-ads-accountlist'))->setDesc('Show credit card ads in the list of accounts %awplus_only%'),
            (new Message('show-ads-blog-post-awplus-only'))->setDesc('Show ads in the AwardWallet blog posts %awplus_only%'),
            (new Message('keeping-feature-aw-addit-revenue'))->setDesc('Keeping this feature on you are helping AwardWallet make additional revenue'),
        ];
    }
}
