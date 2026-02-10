<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Service\NotificationSettings;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\Type\SwitcherType;
use AwardWallet\MobileBundle\Form\View\Block\FreeUserBanner;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use AwardWallet\MobileBundle\Form\View\Block\SubTitle;
use AwardWallet\MobileBundle\Form\View\Block\TitledText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationType extends AbstractType
{
    private ApiVersioningService $versioningService;

    private TranslatorInterface $translator;

    private DataTransformerInterface $dataTransformer;

    private MobileExtensionLoader $mobileExtensionLoader;

    public function __construct(
        ApiVersioningService $versioningService,
        TranslatorInterface $translator,
        DataTransformerInterface $transformer,
        MobileExtensionLoader $mobileExtensionLoader
    ) {
        $this->versioningService = $versioningService;
        $this->translator = $translator;
        $this->dataTransformer = $transformer;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tr = $this->translator;
        $builder->addModelTransformer($this->dataTransformer);

        if ($this->isNativeFormExtension()) {
            $builder->setAttribute('submit_label', false);
            $this->mobileExtensionLoader->loadExtensionByPath($builder, 'mobile/scripts/controllers/profile/NativeNotificationExtension.js');
            $builder->setAttribute('jsFormInterface', "");
        } else {
            $builder->setAttribute(
                'submit_label',
                $this->isAdvancedSettings() ? false : $tr->trans('form.button.update')
            );
            $extensionFileName = $this->isAdvancedSettings() ? "NotificationExtension" : "NotificationExtensionOld";
            $this->mobileExtensionLoader->loadExtensionByPath($builder, "mobile/scripts/controllers/profile/{$extensionFileName}.js");
        }

        if ($this->enabledForFreeUsers()) {
            $builder->setAttribute('free_version', $options['freeVersion']);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->preSetData($event, $options);
        });
    }

    public function getBlockPrefix()
    {
        return 'mobile_notification';
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotificationModel::class,
            'translation_domain' => 'messages',
            'required' => false,
            'isApp' => false,
            'freeVersion' => false,
        ]);
        $resolver->setRequired('groups');
        $resolver->setAllowedTypes('groups', 'array');
    }

    protected function preSetData(FormEvent $event, $options)
    {
        /** @var Usr $user */
        $user = $event->getData();
        /** @var NotificationModel $data */
        $data = $this->dataTransformer->transform($user);
        $form = $event->getForm();
        $tr = $this->translator;
        $advancedSettings = $this->isAdvancedSettings();
        $enabledSettingsInfo = $this->enabledSettingsInfo();
        $isAlwaysDisabled = $this->enabledForFreeUsers() && $options['freeVersion'];

        if ($isAlwaysDisabled) {
            $settingsAttr = ['data-always-disabled' => true];
        } else {
            $settingsAttr = [];
        }

        if ($advancedSettings && in_array(NotificationSettings::KIND_MP, $options['groups'])) {
            $disabled = $data->isMpDisableAll();

            if ($this->enabledGroupTitle()) {
                $form->add('push', BlockContainerType::class, [
                    'blockData' => (new GroupTitle($tr->trans('push-notifications'))),
                ]);
            }

            if ($options['isApp']) {
                $form->add('sound', SwitcherType::class, [
                    'label' => 'app-notifications-sounds',
                    'translation_domain' => 'mobile',
                    'mapped' => false,
                ]);
                $form->add('vibrate', SwitcherType::class, [
                    'label' => 'app-notifications-vibrate',
                    'translation_domain' => 'mobile',
                    'mapped' => false,
                ]);
            }
            $form->add('mpDisableAll', SwitcherType::class, [
                'label' => 'notification.disable-all-push',
                'attr' => $enabledSettingsInfo ? ['notice' => $tr->trans('notification.disable-all.help')] : [],
            ]);

            if ($isAlwaysDisabled) {
                $form->add('mpBanner', BlockContainerType::class, [
                    'blockData' => new FreeUserBanner(),
                ]);
            }

            $form->add('mpRewards', BlockContainerType::class, [
                'blockData' => (new SubTitle(mb_strtoupper($tr->trans('rewards')), true)),
            ]);
            $form->add('mpExpire', SwitcherType::class, [
                'label' => 'notification.expiration',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.expiration.help-v2')] : [],
                ),
            ]);
            $form->add('mpRewardsActivity', SwitcherType::class, [
                'label' => 'notification.rewards-activity',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.rewards-activity.help')] : []
                ),
            ]);
            $form->add('mpRetailCards', SwitcherType::class, [
                'label' => 'notification.channel.retail_cards',
                'translation_domain' => 'mobile',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.retail_cards.help')] : []
                ),
            ]);
            $form->add('mpTravel', BlockContainerType::class, [
                'blockData' => (new SubTitle(mb_strtoupper($tr->trans('travel')), true)),
            ]);
            $form->add('mpNewPlans', SwitcherType::class, [
                'label' => 'notification.new-travel-plan',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.new-travel-plan.help')] : []
                ),
            ]);
            $form->add('mpPlanChanges', SwitcherType::class, [
                'label' => 'notification.change-travel-plan',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.change-travel-plan.help')] : []
                ),
            ]);
            $form->add('mpCheckins', SwitcherType::class, [
                'label' => 'notification.checkinreminder',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.checkinreminder.help')] : []
                ),
            ]);
            $form->add('mpOther', BlockContainerType::class, [
                'blockData' => (new SubTitle(mb_strtoupper($tr->trans('other')), true)),
            ]);
            $form->add('mpProductUpdates', SwitcherType::class, [
                'label' => 'notification.product-updates',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.product-updates.help')] : []
                ),
            ]);
            $form->add('mpOffers', SwitcherType::class, [
                'label' => 'notification.offers',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.offers.help')] : []
                ),
            ]);
            $form->add('mpNewBlogPosts', SwitcherType::class, [
                'label' => 'notification.blog-new-post',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.blog-new-post.help')] : []
                ),
            ]);
            //            $form->add('mpInviteeReg', SwitcherType::class, [
            //                'label' => 'notification.register',
            //                'disabled' => $disabled,
            //                'attr' => ['disabledValue' => false]
            //            ]);
            //            TODO: uncomment after implement
            //            $form->add('mpConnected', SwitcherType::class, [
            //                'label' => 'notification.connected-alerts',
            //                'disabled' => $disabled,
            //                'attr' => ['disabledValue' => false]
            //            ]);
            $form->add('mpNotConnected', SwitcherType::class, [
                'label' => 'notification.not-connected-alerts',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.not-connected-alerts.help')] : []
                ),
            ]);
        } elseif (!$advancedSettings && $options['isApp']) {
            $form->add('push', SwitcherType::class, [
                'label' => 'userinfo.notifications.alert',
                'translation_domain' => 'mobile',
                'mapped' => false,
            ]);
        }

        if (!$advancedSettings || in_array(NotificationSettings::KIND_EMAIL, $options['groups'])) {
            if ($this->enabledGroupTitle()) {
                $form->add('email', BlockContainerType::class, [
                    'blockData' => (new GroupTitle($tr->trans('email-notifications'))),
                ]);
            }
            $disabled = $data->isEmailDisableAll();

            if ($isAlwaysDisabled) {
                $form->add('emailNotice', BlockContainerType::class, [
                    'blockData' => new TitledText(
                        $tr->trans('email.notify.marketing.emails'),
                        $tr->trans('email.notify.unscribe.description')
                    ),
                ]);
            }

            $form->add('emailDisableAll', SwitcherType::class, [
                'label' => 'notification.disable-all-email',
                'attr' => $enabledSettingsInfo ? ['notice' => $tr->trans('notification.disable-all.help')] : [],
            ]);

            if ($isAlwaysDisabled) {
                $form->add('mpBanner', BlockContainerType::class, [
                    'blockData' => new FreeUserBanner(),
                ]);
            }

            if ($advancedSettings) {
                $form->add('emailRewards', BlockContainerType::class, [
                    'blockData' => (new SubTitle(mb_strtoupper($tr->trans('rewards')), true)),
                ]);
            }
            $form->add('emailExpire', ChoiceType::class, [
                'label' => 'notification.expiration',
                'choices' => NotificationModel::getEmailExpireChoices(),
                'attr' => array_merge(
                    ['disabledValue' => Usr::EMAIL_EXPIRATION_NEVER],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.expiration.help-v2')] : [],
                ),
                'disabled' => $disabled,
            ]);
            $form->add('emailRewardsActivity', ChoiceType::class, [
                'label' => 'notification.rewards-activity',
                'choices' => NotificationModel::getEmailRewardsChoices(),
                'attr' => array_merge(
                    ['disabledValue' => REWARDS_NOTIFICATION_NEVER],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.rewards-activity.help')] : [],
                ),
                'disabled' => $disabled,
            ]);

            if ($advancedSettings) {
                $form->add('emailTravel', BlockContainerType::class, [
                    'blockData' => (new SubTitle(mb_strtoupper($tr->trans('travel')), true)),
                ]);
            }
            $form->add('emailNewPlans', SwitcherType::class, [
                'label' => 'notification.new-travel-plan',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.new-travel-plan.help')] : [],
                ),
            ]);
            $form->add('emailPlanChanges', SwitcherType::class, [
                'label' => 'notification.change-travel-plan',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.change-travel-plan.help')] : [],
                ),
            ]);
            $form->add('emailCheckins', SwitcherType::class, [
                'label' => 'notification.checkinreminder',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.checkinreminder.help')] : [],
                ),
            ]);

            if ($advancedSettings) {
                $form->add('emailOther', BlockContainerType::class, [
                    'blockData' => (new SubTitle(mb_strtoupper($tr->trans('other')), true)),
                ]);
            }
            $form->add('emailProductUpdates', SwitcherType::class, [
                'label' => 'notification.product-updates',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.product-updates.help')] : [],
                ),
            ]);
            $form->add('emailOffers', SwitcherType::class, [
                'label' => 'notification.offers',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.offers.help')] : [],
                    $settingsAttr
                ),
            ]);
            $form->add('emailNewBlogPosts', ChoiceType::class, [
                'label' => 'notification.blog-new-post',
                'choices' => NotificationModel::getEmailBlogPostsChoices(),
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_NEVER],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.blog-new-post.help')] : [],
                ),
            ]);
            $form->add('emailInviteeReg', SwitcherType::class, [
                'label' => 'notification.register',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.register.help')] : [],
                ),
            ]);
            //        TODO: uncomment after implement
            //        $form->add('emailConnected', SwitcherType::class, [
            //            'label' => 'notification.connected-alerts',
            //            'disabled' => $disabled,
            //            'attr' => ['disabledValue' => false]
            //        ]);
            $form->add('emailNotConnected', SwitcherType::class, [
                'label' => 'notification.not-connected-alerts',
                'disabled' => $disabled,
                'attr' => array_merge(
                    ['disabledValue' => false],
                    $enabledSettingsInfo ? ['notice' => $tr->trans('notification.not-connected-alerts.help')] : [],
                ),
            ]);
        }
    }

    private function isAdvancedSettings()
    {
        return $this->versioningService->supports(MobileVersions::ADVANCED_NOTIFICATIONS_SETTINGS);
    }

    private function isNativeFormExtension()
    {
        return $this->versioningService->supports(MobileVersions::NATIVE_FORM_EXTENSION);
    }

    private function enabledSettingsInfo()
    {
        return $this->versioningService->supports(MobileVersions::NOTIFICATIONS_SETTINGS_INFO);
    }

    private function enabledGroupTitle()
    {
        return !$this->versioningService->supports(MobileVersions::NOTIFICATIONS_SETTINGS_REMOVE_GROUP_TITLE);
    }

    private function enabledForFreeUsers()
    {
        return $this->versioningService->supports(MobileVersions::NOTIFICATIONS_SETTINGS_FOR_FREE_USER);
    }
}
