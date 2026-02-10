<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Form\Model\ProviderCouponModel;
use AwardWallet\MainBundle\Form\Type\Helpers\AttachProvidercouponToAccountHelper;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountManager;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProvidercouponType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var UseragentRepository
     */
    private $uaRepo;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var AccountManager
     */
    private $accountManager;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var AttachProvidercouponToAccountHelper
     */
    private $couponHelper;
    private CurrencyHelper $currencyHelper;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        UseragentRepository $uaRepo,
        TranslatorInterface $translator,
        EntityManager $em,
        AccountManager $accountManager,
        DataTransformerInterface $dataTransformer,
        AttachProvidercouponToAccountHelper $couponHelper,
        CurrencyHelper $currencyHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->uaRepo = $uaRepo;
        $this->translator = $translator;
        $this->em = $em;
        $this->accountManager = $accountManager;
        $this->dataTransformer = $dataTransformer;
        $this->couponHelper = $couponHelper;
        $this->currencyHelper = $currencyHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $datepicker_options = [
            'required' => false,
            'label' => 'coupon.expiration',
            'input' => 'datetime',
            'datepicker_options' => [
                'yearRange' => '-10:+10',
            ],
            'attr' => [
                'notice' => 'mm/dd/yyyy',
                'autocomplete' => 'off',
                'data-lpignore' => 'true',
                'data-1p-ignore' => 'true',
            ], ];

        $builder
            ->add('owner', OwnerMetaType::class, [
                'label' => 'account.label.owner',
                'translation_domain' => 'messages',
                'designation' => OwnerRepository::FOR_ACCOUNT_ASSIGNMENT,
                'required' => true,
            ])
            ->add('programname', TextType::class, [
                'required' => true,
                'label' => 'coupon.company',
                'allow_urls' => true,
                'attr' => [
                    'class' => 'cp-autocomplete',
                ], ])
            /*
            ->add('typeid', ChoiceType::class, [
                'label' => 'coupon.type',
                'choices' => array_flip(Providercoupon::TYPES),
                'required' => true,
                'placeholder' => 'please-select',
            ])
            */
            ->add('typeName', TextType::class, [
                'label' => 'coupon.type',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'autocomplete-data-choices',
                    'data-choices' => json_encode(array_values(
                        array_map(fn ($item) => ['label' => $item], Providercoupon::TYPES)
                    )),
                ],
            ])
            ->add('cardnumber', TextType::class, [
                'label' => 'coupon.cardnumber',
                'required' => false,
            ])
            ->add('pin', TextType::class, [
                'label' => 'coupon.pin',
                'required' => false,
            ])
            ->add('value', TextType::class, ['required' => false, 'label' => 'coupon.value'])
            ->add('currency', EntityType::class, [
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
            ])
            ->add('expirationdate', DatePickerType::class, $datepicker_options)
            ->add('donttrackexpiration', CheckboxType::class, [
                'label' => $this->translator->trans('coupon.does-not-expire'),
                'required' => false,
            ])
            ->add('isArchived', CheckboxType::class, [
                'label' => $this->translator->trans(/** @Desc("Archive this account") */ 'account.label.is-archived'),
                'required' => false,
                'attr' => [
                    'notice' => $this->translator->trans(/** @Desc("Archived accounts are removed from the Active Accounts tab and moved to the ""Archived Accounts"" tab. The background updating of such accounts will be a lot less frequent.") */ 'account.label.is-archived.notice'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'coupon.note',
                'allow_quotes' => true,
                'allow_urls' => true,
                'required' => false,
            ]);

        /** @var Providercoupon $providerCoupon */
        $providerCoupon = $builder->getData();

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Providercoupon $providerCoupon */
            $providerCoupon = $event->getData();
            $this->addDynamicFields($event->getForm(), $providerCoupon->getProgramname(), $providerCoupon->getOwner());
        });

        $builder->get('programname')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm()->getParent();
            $this->addDynamicFields($event->getForm()->getParent(), $form->get('programname')->getData(), $form->get('owner')->getData());
        });

        $builder->add('useragents', SharingOptionsType::class, ['is_add_form' => empty($providerCoupon->getProvidercouponid())]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix()
    {
        return 'providercoupon';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProviderCouponModel::class,
        ]);
    }

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('coupon.pin'))->setDesc('PIN / Redemption Code'),
            (new Message('coupon.category'))->setDesc('Category'),
            (new Message('coupon.cardnumber'))->setDesc('Cert / Card / Voucher #'),
            (new Message('coupon.note'))->setDesc('Note'),
            (new Message('coupon.does-not-expire'))->setDesc('Does not expire'),
        ];
    }

    protected function addDynamicFields(FormInterface $form, ?string $programName = null, ?Owner $owner = null)
    {
        $provider = StringUtils::isNotEmpty($programName) ?
            $this->couponHelper->getProviderByProgramName($programName) :
            null;

        $form->add('account', EntityType::class, [
            'label' => /** @Desc("Attach to Account") */ 'coupon.attach_to_account',
            'class' => Account::class,
            'choice_label' => \Closure::fromCallable([$this->couponHelper, 'getAccountLabel']),
            'placeholder' => /** @Desc("Standalone") */ 'coupon.standalone',
            'required' => false,
            'choices' => $this->couponHelper->getAccounts($provider, $owner, $programName),
        ]);

        if ($provider) {
            $form->add('kind', ChoiceType::class, [
                'label' => 'coupon.category',
                'choices' => [Provider::getKinds()[$provider->getKind()] => $provider->getKind()],
                'required' => true,
                'placeholder' => false,
            ]);
        } else {
            $form->add('kind', ChoiceType::class, [
                'label' => 'coupon.category',
                'choices' => array_filter(array_flip(Provider::getKinds()), function ($kind) {return $kind !== PROVIDER_KIND_DOCUMENT; }),
                'required' => true,
                'placeholder' => 'please-select',
            ]);
        }
    }
}
