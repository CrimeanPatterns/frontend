<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\Profile\OtherSettingsModel;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\Type\SwitcherType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class OtherSettingsType extends AbstractType
{
    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;

    public function __construct(
        TranslatorInterface $translator,
        DataTransformerInterface $transformer,
        MobileExtensionLoader $mobileExtensionLoader,
        ApiVersioningService $apiVersioning
    ) {
        $this->translator = $translator;
        $this->dataTransformer = $transformer;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->apiVersioning = $apiVersioning;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->dataTransformer);
        $builder->setAttribute('submit_label', false);

        if ($this->apiVersioning->supports(MobileVersions::OTHER_SETTINGS_FORM_EXTENSION)) {
            $this->mobileExtensionLoader->loadExtensionByPath($builder,
                'mobile/scripts/controllers/profile/OtherSettingsExtension.js');
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->preSetData($event, $options);
        });
    }

    public function getBlockPrefix()
    {
        return 'mobile_other_settings';
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OtherSettingsModel::class,
            'translation_domain' => 'messages',
            'required' => false,
        ]);
    }

    protected function preSetData(FormEvent $event, $options)
    {
        /** @var Usr $user */
        $user = $event->getData();
        $form = $event->getForm();
        $awPlusOnly = $this->translator->trans("awplus_only");

        $form->add('splashAdsDisabled', SwitcherType::class, [
            'label' => $this->translator->trans('show-screen-ads-after-logging', ['%awplus_only%' => ''], 'messages'),
            'disabled' => !$user->isAwPlus(),
            'mapped' => $user->isAwPlus(),
            'attr' => ['notice' => $awPlusOnly],
            'data' => !$user->isAwPlus() || !$user->isSplashAdsDisabled(), // reason for inversion, new labels, see #22853
        ]);

        $form->add('linkAdsDisabled', SwitcherType::class, [
            'label' => $this->translator->trans('use-affiliate-links-autologin', ['%awplus_only%' => ''], 'messages'),
            'disabled' => !$user->isAwPlus(),
            'mapped' => $user->isAwPlus(),
            'attr' => ['notice' => $awPlusOnly],
            'data' => !$user->isAwPlus() || !$user->isLinkAdsDisabled(), // reason for inversion, new labels, see #22853
        ]);

        $form->add('listAdsDisabled', SwitcherType::class, [
            'label' => $this->translator->trans('show-card-ads-accountlist', ['%awplus_only%' => ''], 'messages'),
            'disabled' => !$user->isAwPlus(),
            'mapped' => $user->isAwPlus(),
            'attr' => ['notice' => $awPlusOnly],
            'data' => !$user->isAwPlus() || !$user->isListAdsDisabled(), // reason for inversion, new labels, see #22853
        ]);

        $form->add('isBlogPostAds', SwitcherType::class, [
            'label' => $this->translator->trans('show-ads-blog-post-awplus-only', ['%awplus_only%' => ''], 'messages'),
            'disabled' => !$user->isAwPlus(),
            'mapped' => $user->isAwPlus(),
            'attr' => ['notice' => $awPlusOnly],
            'data' => !$user->isAwPlus() || $user->isBlogPostAds(),
        ]);
    }
}
