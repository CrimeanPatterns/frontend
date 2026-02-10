<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\BaseFieldsDict as AccountBaseFieldsDict;
use AwardWallet\MainBundle\Form\Extension\SorterFormExtension;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\ProviderCouponModelMobile;
use AwardWallet\MainBundle\Form\Providercoupon\BaseFieldsDict;
use AwardWallet\MainBundle\Form\Type\Helpers\AttachProvidercouponToAccountHelper;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Form\Type\SharingOptionsType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MobileBundle\Form\Type\AccountType\MobileFieldsDict;
use AwardWallet\MobileBundle\Form\Type\AccountType\Redesign2023FallDict as AccountRedesign2023FallDict;
use AwardWallet\MobileBundle\Form\Type\Components\TextCompletionType;
use AwardWallet\MobileBundle\Form\Type\Helpers\AccountHelper;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\ArrayKeyMatcher;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\UnmatchedAnchor;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Sorter;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\CurrencyAndBalanceType;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\ToggleButton;
use AwardWallet\MobileBundle\Form\Type\ProvidercouponType\Redesign2023FallDict;
use AwardWallet\MobileBundle\Form\View\Block\AccountHeader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderCouponType extends AbstractType
{
    /**
     * @var AccountHelper
     */
    private $loyaltyHelper;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;
    /**
     * @var AttachProvidercouponToAccountHelper
     */
    private $couponHelper;
    /**
     * @var ApiVersioningService
     */
    private $versioning;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var AwTokenStorageInterface
     */
    private $awTokenStorage;
    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;

    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private CurrencyHelper $currencyHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        AccountHelper $loyaltyHelper,
        UrlGeneratorInterface $urlGenerator,
        DataTransformerInterface $dataTransformer,
        AttachProvidercouponToAccountHelper $couponHelper,
        ApiVersioningService $versioning,
        AccountRepository $accountRepository,
        ProviderRepository $providerRepository,
        AwTokenStorageInterface $awTokenStorage,
        MobileExtensionLoader $mobileExtensionLoader,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        CurrencyHelper $currencyHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
        $this->urlGenerator = $urlGenerator;
        $this->dataTransformer = $dataTransformer;
        $this->couponHelper = $couponHelper;
        $this->versioning = $versioning;
        $this->accountRepository = $accountRepository;
        $this->providerRepository = $providerRepository;
        $this->awTokenStorage = $awTokenStorage;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->currencyHelper = $currencyHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Providercoupon $coupon */
        $coupon = $builder->getData();

        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $this->addAccountHeader($builder, $coupon);
        }

        $this->loyaltyHelper->addUserAgent($builder, $coupon);

        [$attachedAccounts, $providerKind] = $this->versioning->supports(MobileVersions::LINKED_COUPONS) ?
            $this->getAttachedAccountsData($coupon, $this->awTokenStorage->getBusinessUser()) :
            [[], null];

        $builder
            ->add(
                BaseFieldsDict::PROGRAM_NAME,
                TextCompletionType::class,
                [
                    'required' => true,
                    'label' => 'coupon.company',
                    'completionLink' => $this->urlGenerator->generate('awm_newapp_provider_completion', ['withAccounts' => 1]),
                    'attachedAccounts' => $attachedAccounts,
                    'providerKind' => $providerKind,
                ]
            )
            ->add(
                BaseFieldsDict::KIND,
                ChoiceType::class,
                [
                    'label' => 'coupon.category',
                    'choices' => $this->getKinds(),
                    'required' => true,
                    'placeholder' => 'please-select',
                ]
            )
            /*
            ->add('typeid', ChoiceType::class, [
                'label' => 'coupon.type',
                'choices' => array_flip($coupon->getTypes()),
                'required' => true,
                'placeholder' => 'please-select',
            ])
            */
            ->add(BaseFieldsDict::TYPE_NAME, TextCompletionType::class, [
                'label' => 'coupon.type',
                'required' => true,
                'completionLink' => $this->urlGenerator->generate('awm_coupon_types_completion'),
                'attr' => [
                    'submitOnFocus' => true,
                ],
            ]);

        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $this->modifyRedesign2023Fall($builder, $coupon);
        }

        $builder
            ->add(BaseFieldsDict::CARD_NUMBER, TextType::class, [
                'label' => 'coupon.cardnumber',
                'required' => false,
            ])
            ->add(BaseFieldsDict::PIN, TextType::class, [
                'label' => 'coupon.pin',
                'required' => false,
            ]);
        $this->addCurrencyAndBalance($builder);
        $this->addTrackExpiration($builder, $coupon);
        $builder->add(
            BaseFieldsDict::EXPIRATION_DATE,
            DateType::class,
            [
                'label' => 'account.label.expiration',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'disabled' =>
                    $this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)
                    && $coupon->getDonttrackexpiration(),
                'required' => false,
                'input' => 'datetime',
            ]
        );

        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $expirationFieldOptions = $form->get(BaseFieldsDict::EXPIRATION_DATE)->getConfig()->getOptions();
                $trackExpirationRaw = $data[Redesign2023FallDict::TRACK_EXPIRATION] ?? false;

                $oldDisabled = $expirationFieldOptions['disabled'];
                $newDisabled = $oldDisabled;

                if (\is_scalar($trackExpirationRaw)) {
                    $newDisabled = !$trackExpirationRaw;
                }

                if ($oldDisabled !== $newDisabled) {
                    $form->remove(BaseFieldsDict::EXPIRATION_DATE);
                    $form->add(
                        BaseFieldsDict::EXPIRATION_DATE,
                        DateType::class,
                        \array_merge(
                            $expirationFieldOptions,
                            ['disabled' => $newDisabled]
                        )
                    );
                }
            });
        }
        $builder
            ->add(
                BaseFieldsDict::DESCRIPTION,
                TextareaType::class,
                [
                    'allow_quotes' => true,
                    'allow_tags' => true,
                    'allow_urls' => true,
                    'required' => false,
                    'label' => 'account.label.comment',
                ]
            );

        $this->loyaltyHelper->addCardImagesAndBarCode(
            $builder,
            $coupon->getProvidercouponid() ? $coupon : null
        );
        $extensions = [];

        if ($this->versioning->supports(MobileVersions::LINKED_COUPONS)) {
            $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $coupon = $event->getData();
                $this->addDynamicFields($event->getForm(), $coupon->getProgramname(), $coupon->getOwner());
            });

            $builder->get(BaseFieldsDict::PROGRAM_NAME)->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm()->getParent();
                $this->addDynamicFields($event->getForm()->getParent(), $form->get(BaseFieldsDict::PROGRAM_NAME)->getData(), $form->get(BaseFieldsDict::OWNER)->getData());
            });

            $extensions[] = 'mobile/scripts/controllers/accounts/CouponExtension.js';
        } else {
            $builder->add(BaseFieldsDict::KIND, ChoiceType::class, [
                'label' => 'coupon.category',
                'choices' => $this->getKinds(),
                'required' => true,
                'placeholder' => 'please-select',
            ]);
        }

        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $extensions[] = AccountType::CUSTOM_ACCOUNT_EXTENSION_PATH;
        }

        if ($extensions) {
            $this->mobileExtensionLoader->loadExtensionByPath($builder, $extensions);
        }

        $builder->addModelTransformer($this->dataTransformer);
        $this->addOrdering($builder);

        $isExistingAccount = (bool) $coupon->getId();
        $builder->add(
            'useragents',
            /* This type will be replaced with hidden type in FormDehydrator */
            SharingOptionsType::class,
            [
                'is_add_form' => !$isExistingAccount,
                'hidden_mode' => true,
                'allow_extra_fields' => true,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'mobile_providercoupon';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProviderCouponModelMobile::class,
        ]);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    protected function addDynamicFields(FormInterface $form, ?string $programName = null, ?Owner $owner = null)
    {
        $provider = StringUtils::isNotEmpty($programName) ?
            $this->couponHelper->getProviderByProgramName($programName) :
            null;

        $form->add(BaseFieldsDict::ACCOUNT, EntityType::class, [
            'label' => 'coupon.attach_to_account',
            'class' => Account::class,
            'choice_label' => \Closure::fromCallable([$this->couponHelper, 'getAccountLabel']),
            'placeholder' => 'coupon.standalone',
            'required' => false,
            'choices' => $this->couponHelper->getAccounts($provider, $owner, $programName),
        ]);

        $form->add(BaseFieldsDict::KIND, ChoiceType::class, [
            'label' => 'coupon.category',
            'choices' => $this->getKinds(),
            'required' => true,
            'placeholder' => 'please-select',
        ]);
    }

    protected function getKinds(): array
    {
        $kinds = Provider::getKinds();
        unset($kinds[PROVIDER_KIND_DOCUMENT]);

        return \array_flip($kinds);
    }

    protected function getAttachedAccountsData(Providercoupon $coupon, Usr $user): array
    {
        $providerId = null;
        $kind = null;

        if (
            ($account = $coupon->getAccount())
            && ($provider = $account->getProviderid())
        ) {
            $providerId = $provider->getProviderid();
            $kind = $provider->getKind();
        } elseif (StringUtils::isNotEmpty($coupon->getProgramname())) {
            $providers = $this->providerRepository->findProviderByText($coupon->getProgramname(), 'ASC', 7);

            if ($providers) {
                $kind = (string) $providers[0]['Kind'];
                $providerId = (int) $providers[0]['ProviderID'];
            }
        }

        /** @var Account[][] $accounts */
        $attachAccounts = [];

        if ($providerId) {
            $accounts = $this->accountRepository->getPossibleAccountsForPossibleOwnersByProviders(
                [$providerId],
                $user
            );

            foreach ($accounts[$providerId] ?? [] as $ownerId => $accountsPerOwner) {
                /** @var Account $account */
                foreach ($accountsPerOwner as $account) {
                    $attachAccounts[$ownerId][] = [
                        'value' => $account->getAccountid(),
                        'label' => $this->couponHelper->getAccountLabel($account),
                    ];
                }
            }
        }

        return [$attachAccounts, $kind];
    }

    protected function getCurrencyName(Providercoupon $providercoupon): string
    {
        if ($providercoupon->getId() && $providercoupon->getCurrency()) {
            $currency = $providercoupon->getCurrency();
        } else {
            $currency = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Currency::class)->findOneByName('Points');
        }

        $currencyName = $this->translator->trans(/** @Ignore */ $curKey = 'name.' . $currency->getCurrencyid(), [], 'currency');

        if ($currencyName == $curKey) {
            return $currency->getName();
        }

        return $currencyName;
    }

    private function addOrdering(FormBuilderInterface $builder): void
    {
        $field = static fn (string $field) => ArrayKeyMatcher::create($field);
        $builder->setAttribute(SorterFormExtension::ATTRIBUTE_NAME, new Sorter(
            $this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL) ?
                [
                    $field(MobileFieldsDict::CARD_IMAGES),
                    $field(MobileFieldsDict::BARCODE),
                    $field(AccountRedesign2023FallDict::ACCOUNT_HEADER),
                    $field(BaseFieldsDict::OWNER),
                    $field(BaseFieldsDict::PROGRAM_NAME),
                    $field(BaseFieldsDict::ACCOUNT),
                    $field(BaseFieldsDict::KIND),
                    $field(BaseFieldsDict::TYPE_NAME),
                    $field(AccountRedesign2023FallDict::FIELD_TOGGLER),

                    $field(BaseFieldsDict::CARD_NUMBER),
                    $field(Redesign2023FallDict::CARD_NUMBER_SEPARATOR),

                    $field(BaseFieldsDict::PIN),
                    $field(Redesign2023FallDict::PIN_SEPARATOR),

                    $field(AccountRedesign2023FallDict::CURRENCY_AND_BALANCE),
                    $field(Redesign2023FallDict::TRACK_EXPIRATION),
                    $field(BaseFieldsDict::EXPIRATION_DATE),
                    $field(Redesign2023FallDict::CURRENCY_AND_BALANCE_SEPARATOR),

                    $field(BaseFieldsDict::DESCRIPTION),
                    $field(Redesign2023FallDict::COMMENT_SEPARATOR),
                    UnmatchedAnchor::getInstance(),
                ] :
                [
                    $field(MobileFieldsDict::CARD_IMAGES),
                    $field(MobileFieldsDict::BARCODE),
                    $field(BaseFieldsDict::OWNER),
                    $field(BaseFieldsDict::PROGRAM_NAME),
                    $field(BaseFieldsDict::ACCOUNT),
                    $field(BaseFieldsDict::KIND),
                    $field(BaseFieldsDict::TYPE_NAME),
                    $field(BaseFieldsDict::CARD_NUMBER),
                    $field(BaseFieldsDict::PIN),
                    $field(BaseFieldsDict::VALUE),
                    $field(BaseFieldsDict::CURRENCY),
                    $field(BaseFieldsDict::EXPIRATION_DATE),
                    $field(BaseFieldsDict::DONT_TRACK_EXPIRATION),
                    $field(BaseFieldsDict::DESCRIPTION),
                    UnmatchedAnchor::getInstance(),
                ])
        );
    }

    private function addAccountHeader(FormBuilderInterface $builder, Providercoupon $coupon)
    {
        $headerBlock = new AccountHeader();
        $headerBlock->providerKind = 'coupon';
        $headerBlock->providerName = $coupon->getId() !== null ?
            $coupon->getProgramname() :
            $this->translator->trans('vouchers.gift.card.list.title', [], 'mobile');
        $headerBlock->hint = $this->translator->trans('custom.account.not-tracked', [], 'mobile');

        $builder->add(
            AccountRedesign2023FallDict::ACCOUNT_HEADER,
            BlockContainerType::class,
            ['blockData' => $headerBlock]
        );
    }

    private function modifyRedesign2023Fall(FormBuilderInterface $builder, Providercoupon $coupon): void
    {
        $this->addFieldToggler($builder, $coupon);
    }

    private function addFieldToggler(FormBuilderInterface $builder, Providercoupon $coupon): void
    {
        $togglerButtonsList = [
            new ToggleButton(
                'add-balance',
                $this->translator->trans(/** @Desc("Add Cert / Card / Voucher #") */ 'add-card-number-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove Cert / Card / Voucher #") */ 'remove-card-number-toggle', [], 'mobile'),
                [BaseFieldsDict::CARD_NUMBER],
                Redesign2023FallDict::CARD_NUMBER_SEPARATOR,
            ),
            new ToggleButton(
                'add-password',
                $this->translator->trans(/** @Desc("Add PIN / Redemption Code") */ 'add-pin-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove PIN / Redemption Code") */ 'remove-pin-toggle', [], 'mobile'),
                [BaseFieldsDict::PIN],
                Redesign2023FallDict::PIN_SEPARATOR,
            ),
            new ToggleButton(
                'add-voucher-value',
                $this->translator->trans(/** @Desc("Add value") */ 'add-value-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove value") */ 'remove-value-toggle', [], 'mobile'),
                [
                    AccountRedesign2023FallDict::CURRENCY_AND_BALANCE,
                    BaseFieldsDict::EXPIRATION_DATE,
                    AccountRedesign2023FallDict::TRACK_EXPIRATION,
                ],
                Redesign2023FallDict::CURRENCY_AND_BALANCE_SEPARATOR,
                [
                    AccountRedesign2023FallDict::CURRENCY_AND_BALANCE . '.' . AccountBaseFieldsDict::BALANCE,
                    BaseFieldsDict::EXPIRATION_DATE,
                ],
                true,
            ),
            new ToggleButton(
                'add-comment',
                $this->translator->trans('add-comment-toggle', [], 'mobile'),
                $this->translator->trans('remove-comment-toggle', [], 'mobile'),
                [BaseFieldsDict::DESCRIPTION],
                Redesign2023FallDict::COMMENT_SEPARATOR,
            ),
        ];
        $this->loyaltyHelper->addToggler($builder, $togglerButtonsList);
    }

    private function addCurrencyAndBalance(FormBuilderInterface $builder): void
    {
        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $currencyAndBalanceFieldBuilder = $builder->create(
                AccountRedesign2023FallDict::CURRENCY_AND_BALANCE,
                CurrencyAndBalanceType::class
            );
            $this->loyaltyHelper->addCurrencyRedesign2023Fall($currencyAndBalanceFieldBuilder);
            $currencyAndBalanceFieldBuilder->add(AccountBaseFieldsDict::BALANCE, TextType::class, [
                'required' => false,
                'label' => 'coupon.value',
                'property_path' => AccountBaseFieldsDict::BALANCE,
            ]);
            $builder->add($currencyAndBalanceFieldBuilder);
            $builder->addModelTransformer(
                new CallbackTransformer(
                    function (ProviderCouponModelMobile $value) {
                        $currencyAndBalance = $value->getCurrencyandbalance();
                        $currencyAndBalance->setCurrency($value->getCurrency());
                        $currencyAndBalance->setBalance($value->getValue());

                        return $value;
                    },
                    function (ProviderCouponModelMobile $value) {
                        $currencyAndBalance = $value->getCurrencyandbalance();
                        $value->setCurrency($currencyAndBalance->getCurrency());
                        $value->setValue($currencyAndBalance->getBalance());

                        return $value;
                    }
                ),
                true
            );
        } else {
            $builder
                ->add(BaseFieldsDict::VALUE, TextType::class, ['required' => false, 'label' => 'coupon.value'])
                ->add(BaseFieldsDict::CURRENCY, EntityType::class, [
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
                    'placeholder' => 'please-select',
                ]);
        }
    }

    private function addTrackExpiration(FormBuilderInterface $builder, Providercoupon $coupon): void
    {
        if ($this->versioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $currencyName = $this->getCurrencyName($coupon);
            $builder->add(
                $builder
                    ->create(Redesign2023FallDict::TRACK_EXPIRATION, SwitcherType::class, [
                        'label' => $this->translator->trans(
                            'expiring-points-tracking',
                            \array_merge(
                                [
                                    '%currency%' => StringHandler::mb_ucfirst($currencyName),
                                ],
                                $this->versioning->supports(MobileVersions::REACT_NATIVE_RENDER_HTML_6_TRANSIENT_RENDER_ENGINE) ?
                                    ['%tag_open%' => '<span>', '%tag_close%' => '</span>', '%image%' => '<div class="exp-track"><img src="assets/exp-track.png"/></div>'] :
                                    ['%tag_open%' => ' ',      '%tag_close%' => '',        '%image%' => '<span class="exp-track"><img src="assets/exp-track.png"/></span>']
                            )
                        ),
                        'required' => false,
                        'attr' => [
                            'notice' => $this->translator->trans('expiring-points-tracking.notice', ['%currency%' => $currencyName]),
                            'class' => 'simple',
                        ],
                        'property_path' => MobileFieldsDict::DONT_TRACK_EXPIRATION,
                        'submitData' => true,
                    ])
                    ->addModelTransformer(new CallbackTransformer(
                        fn ($value) => !$value,
                        fn ($value) => !$value
                    ))
            );
        } else {
            $builder->add(BaseFieldsDict::DONT_TRACK_EXPIRATION, CheckboxType::class, [
                'label' => 'coupon.does-not-expire',
                'required' => false,
            ]);
        }
    }
}
