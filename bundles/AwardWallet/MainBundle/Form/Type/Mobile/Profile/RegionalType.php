<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Profile;

use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\Profile\RegionalModel;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Form\Type\Helpers\Profile\RegionalHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegionalType extends AbstractType
{
    private RegionalHelper $regionalHelper;

    private DataTransformerInterface $transformer;

    private MobileExtensionLoader $mobileExtensionLoader;

    private TranslatorInterface $translator;

    private CurrencyHelper $currencyHelper;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        RegionalHelper $regionalHelper,
        DataTransformerInterface $transformer,
        MobileExtensionLoader $mobileExtensionLoader,
        TranslatorInterface $translator,
        CurrencyHelper $currencyHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->regionalHelper = $regionalHelper;
        $this->transformer = $transformer;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->translator = $translator;
        $this->currencyHelper = $currencyHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Usr $user */
        $user = $builder->getData();
        [$langs, $langHints, $localeHints, $regions] = array_values($this->regionalHelper->getData());

        parent::buildForm($builder, $options);

        $builder->add('language', ChoiceType::class, [
            'choices' => $langs,
            'label' => 'user.regional.form.language',
            'placeholder' => false,
            'required' => false,
        ]);

        $builder->add('lang_hints', HiddenType::class, [
            'data' => json_encode($langHints),
            'mapped' => false,
            'disabled' => true,
        ]);

        $builder->add('region', ChoiceType::class, [
            'choices' => array_flip($regions),
            'label' => 'label.country',
            'placeholder' => 'user.regional.form.region.empty',
            'required' => false,
            'attr' => [
                'notice' => $localeHints[$user->getLocale()],
            ],
        ]);

        $builder->add('locale_hints', HiddenType::class, [
            'data' => json_encode($localeHints),
            'mapped' => false,
            'disabled' => true,
        ]);

        if ($this->authorizationChecker->isGranted('ROLE_STAFF')) {
            $builder->add('currency', EntityType::class, [
                'class' => Currency::class,
                'label' => $this->translator->trans('itineraries.currency', [], 'trips'),
                'choice_label' => function (Currency $currency) {
                    return ucfirst(
                        $this->translator->trans('name.' . $currency->getCurrencyid(), [], 'currency')
                    );
                },
                'choices' => $this->currencyHelper->getChoices(),
                'required' => false,
                'choice_translation_domain' => false,
            ]);
        }

        $builder->setAttribute('submit_label', 'form.button.update');
        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'mobile/scripts/controllers/profile/RegionalExtension.js');
        $builder->addModelTransformer($this->transformer);
    }

    public function getBlockPrefix()
    {
        return 'profile_regional_mobile';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegionalModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
