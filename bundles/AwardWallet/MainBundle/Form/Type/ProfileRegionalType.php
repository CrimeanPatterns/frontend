<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\RegionalModel;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Form\Type\Helpers\Profile\RegionalHelper;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileRegionalType extends AbstractType implements TranslationContainerInterface
{
    private RegionalHelper $regionalHelper;

    private DataTransformerInterface $transformer;

    private TranslatorInterface $translator;

    private CurrencyHelper $currencyHelper;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        RegionalHelper $regionalHelper,
        DataTransformerInterface $transformer,
        TranslatorInterface $translator,
        CurrencyHelper $currencyHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->regionalHelper = $regionalHelper;
        $this->transformer = $transformer;
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
            'label' => /** @Desc("Language") */ 'user.regional.form.language',
            'placeholder' => false,
            'required' => false,
            'attr' => [
                'class' => 'js-user-regional-language',
                'data-hints' => json_encode($langHints),
            ],
        ]);

        $builder->add('region', ChoiceType::class, [
            'choices' => array_flip($regions),
            'label' => 'label.country',
            'placeholder' => /** @Desc("Auto") */ 'user.regional.form.region.empty',
            'required' => false,
            'attr' => [
                'notice' => $localeHints[$user->getLocale()],
                'class' => 'js-user-regional-country',
                'data-hints' => json_encode($localeHints),
            ],
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

        $builder->addModelTransformer($this->transformer);
    }

    public function getBlockPrefix()
    {
        return 'profile_regional';
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

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
        ];
    }

    private function gmtOffset($offset)
    {
        $value = abs($offset / 3600);
        $hours = floor($value);
        $hours = strlen($hours) == 1 ? "0" . $hours : $hours;
        $minutes = $value - floor($value);
        $minutes = $minutes * 60 == 0 ? '00' : $minutes * 60;
        $sign = $offset < 0 ? "-" : ($offset == 0 ? "" : "+");

        return "(GMT" . (($hours == '00' && $minutes == '00') ? "" : $sign . $hours . ":" . $minutes) . ")";
    }
}
