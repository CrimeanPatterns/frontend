<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign;

use AwardWallet\MainBundle\Form\Type\Mobile\DescType;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use AwardWallet\MainBundle\Security\SiegeModeDetector;
use AwardWallet\MobileBundle\Form\Model\RegisterModel;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\View\Block\Recaptcha;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegisterNewUserType extends AbstractType implements TranslationContainerInterface
{
    private bool $recaptchaEnabled;
    private TranslatorInterface $translator;
    private SiegeModeDetector $siegeModeDetector;
    private ApiVersioningService $apiVersioning;

    public function __construct(
        TranslatorInterface $translator,
        SiegeModeDetector $siegeModeDetector,
        ApiVersioningService $apiVersioning,
        bool $recaptchaEnabled
    ) {
        $this->recaptchaEnabled = $recaptchaEnabled;
        $this->translator = $translator;
        $this->siegeModeDetector = $siegeModeDetector;
        $this->apiVersioning = $apiVersioning;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', NewUserType::class, [
            'error_bubbling' => false,
            'attr' => ['disableLabel' => true],
            'constraints' => [new Valid()],
        ]);

        if ($this->apiVersioning->supports(MobileVersions::TERMS_OF_USE_ON_REGISTER)) {
            $builder->add('termsOfUse', DescType::class, [
                'data' => $this->translator->trans(
                    'registration.terms-of-use',
                    [
                        '%terms-of-use-link%' => 'https://awardwallet.com/m/terms',
                        '%privacy-notice-link%' => 'https://awardwallet.com/m/privacy',
                    ],
                    'mobile'
                ),
            ]);
        } else {
            $builder->add('agree', CheckboxType::class, [
                'label' => new Trans('login.agree', [], 'mobile'),
                'error_bubbling' => false,
                'label_attr' => [
                    'data-error-label' => false,
                ],
            ]);
        }

        if (
            (
                !isset($options['recaptcha_override'])
                || $options['recaptcha_override']
            )
            && $this->recaptchaEnabled
            && $this->siegeModeDetector->isUnderSiege()
        ) {
            $this->attachRecaptcha($builder, $options['captcha_provider']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegisterModel::class,
            'recaptcha_override' => null,
        ]);
        $resolver->setRequired(['captcha_provider']);
    }

    public function getBlockPrefix()
    {
        return 'newapp_register_new_user';
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('registration.terms-of-use', 'mobile'))
                ->setDesc('By clicking "Register", you agree to our <a href="%terms-of-use-link%" target="_blank">Terms of Use</a> and <a href="%privacy-notice-link%" target="_blank">Privacy Notice</a>'),
        ];
    }

    private function attachRecaptcha(FormBuilderInterface $builder, CaptchaProviderInterface $captchaProvider)
    {
        $builder->add("recaptcha", BlockContainerType::class, [
            'blockData' => new Recaptcha(),
            'attr' => [
                'key' => $captchaProvider->getSiteKey(),
                'url' => $captchaProvider->getScriptUrl('onLoadCallback'),
                'vendor' => $captchaProvider->getVendor(),
            ],
        ]);
        $builder->add("recaptcha_response", HiddenType::class, [
            'required' => false,
            'mapped' => false,
        ]);
    }
}
