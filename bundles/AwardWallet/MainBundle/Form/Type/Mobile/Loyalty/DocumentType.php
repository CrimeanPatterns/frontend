<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Loyalty;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\StateRepository;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\DocumentModel;
use AwardWallet\MainBundle\Form\Transformer\DocumentTransformer;
use AwardWallet\MainBundle\Form\Type\Helpers\DocumentHelper;
use AwardWallet\MainBundle\Form\Type\Mobile\KeyboardType;
use AwardWallet\MainBundle\Form\Type\SharingOptionsType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MobileBundle\Form\Type\AccountType\Redesign2023FallDict;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\Components\TextCompletionType;
use AwardWallet\MobileBundle\Form\Type\Helpers\AccountHelper;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\View\Block\AccountHeader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentType extends AbstractType
{
    private LocalizeService $localizer;
    private DocumentTransformer $transformer;
    private TranslatorInterface $translator;
    private AccountHelper $accountHelper;
    private DocumentHelper $documentHelper;
    private CountryRepository $countryRepository;
    private StateRepository $stateRepository;
    private UrlGeneratorInterface $mobileUrlGenerator;
    private MobileExtensionLoader $extensionLoader;
    private string $projectPath;
    private ApiVersioningService $apiVersioning;
    private AwTokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        LocalizeService $localizer,
        DocumentTransformer $transformer,
        TranslatorInterface $translator,
        AccountHelper $accountHelper,
        UrlGeneratorInterface $mobileUrlGenerator,
        DocumentHelper $documentPreFillHelper,
        CountryRepository $countryRepository,
        StateRepository $stateRepository,
        MobileExtensionLoader $extensionLoader,
        ApiVersioningService $apiVersioning,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        string $projectPath
    ) {
        $this->localizer = $localizer;
        $this->transformer = $transformer;
        $this->translator = $translator;
        $this->accountHelper = $accountHelper;
        $this->documentHelper = $documentPreFillHelper;
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->mobileUrlGenerator = $mobileUrlGenerator;
        $this->extensionLoader = $extensionLoader;
        $this->projectPath = $projectPath;
        $this->apiVersioning = $apiVersioning;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Providercoupon $document */
        $document = $builder->getData();

        if (\in_array(
            $document->getTypeid(),
            [
                Providercoupon::TYPE_TRUSTED_TRAVELER,
                Providercoupon::TYPE_DRIVERS_LICENSE,
                Providercoupon::TYPE_INSURANCE_CARD,
                Providercoupon::TYPE_PRIORITY_PASS,
            ]
        )) {
            $this->accountHelper->addCardImages(
                $builder,
                $document->getProvidercouponid() ? $document : null
            );
        } elseif ($document->getTypeid() == Providercoupon::TYPE_VACCINE_CARD) {
            $this->accountHelper->addBarCode($builder, $document);
        }

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $this->addAccountHeader($builder, $document);
        }

        $this->accountHelper->addUserAgent($builder, $document);

        switch ($document->getTypeid()) {
            case Providercoupon::TYPE_PASSPORT:
                $this->buildPassportForm($builder);

                break;

            case Providercoupon::TYPE_TRUSTED_TRAVELER:
                $this->buildTrustedTravelerForm($builder);

                break;

            case Providercoupon::TYPE_VACCINE_CARD:
                $this->buildVaccineCardForm($builder);

                break;

            case Providercoupon::TYPE_INSURANCE_CARD:
                $this->buildInsuranceCardForm($builder);

                break;

            case Providercoupon::TYPE_VISA:
                $this->buildVisaForm($builder);

                break;

            case Providercoupon::TYPE_DRIVERS_LICENSE:
                $this->buildDriversLicenseForm($builder);

                break;

            case Providercoupon::TYPE_PRIORITY_PASS:
                $this->buildPriorityPassForm($builder);

                break;
        }

        $this->documentHelper->addIsNewDocument($builder, $document);
        $builder->add(
            'description',
            TextareaType::class,
            [
                'allow_quotes' => true,
                'allow_tags' => true,
                'allow_urls' => true,
                'required' => false,
                'label' => 'account.label.comment',
            ]
        );

        if (
            isset(Providercoupon::DOCUMENT_TYPES[$document->getTypeid()])
            && $this->apiVersioning->supports(MobileVersions::DOCUMENT_VACCINE_VISA_INSURANCE_TYPES)
        ) {
            $this->extensionLoader->loadExtensionByPath(
                $builder,
                ['bundles/AwardWallet/MainBundle/Resources/js/Form/Document/edit.js']
            );
            $this->documentHelper->addHiddenOwnersList($builder);
        }

        $isExistingAccount = (bool) $document->getId();
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
        $builder->addModelTransformer($this->transformer);
    }

    public function buildPassportForm(FormBuilderInterface $builder)
    {
        $builder->add('passportName', TextType::class, [
            'label' => 'traveler_profile.passport-name',
            'required' => true,
            'attr' => [
                'notice' => $this->translator->trans('traveler_profile.passport-notice'),
            ],
        ]);

        $builder->add('passportNumber', TextType::class, [
            'label' => 'traveler_profile.passport-number',
            'required' => true,
        ]);

        $builder->add('passportIssueDate', DateType::class, [
            'label' => 'traveler_profile.passport-issueDate',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('passportCountry', ChoiceType::class, [
            'choices' => \array_flip($this->localizer->getLocalizedCountries()),
            'label' => 'traveler_profile.passport-country',
            'required' => false,
            'placeholder' => 'please-select',
        ]);

        $builder->add('expirationDate', DateType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'invalid_message' => 'invalid_date_and_time',
            'required' => false,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
    }

    public function buildTrustedTravelerForm(FormBuilderInterface $builder)
    {
        $builder->add('travelerNumber', TextType::class, [
            'label' => 'traveler_profile.number',
            'required' => true,
        ]);

        $builder->add('expirationDate', DateType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'invalid_message' => 'invalid_date_and_time',
            'required' => false,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'mobile_document_form';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DocumentModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function buildPriorityPassForm(FormBuilderInterface $builder)
    {
        $builder->add('accountNumber', TextType::class, [
            'label' => 'priority-pass-number',
            'required' => true,
        ]);

        $builder->add('expirationDate', DateType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('isSelect', CheckboxType::class, [
            'label' => 'aw.onecard.th.select',
            'required' => false,
            'value' => 1,
            'attr' => [
                'notice' => $this->translator->trans('does-say-select-pass-card'),
            ],
        ]);

        $cardsChoices = $this->documentHelper->getCreditCards(['isOfferPriorityPass' => 1]);
        $builder->add('creditCardId', ChoiceType::class, [
            'choices' => \array_flip($cardsChoices ?? []),
            'label' => 'credit-card',
            'required' => false,
            'placeholder' => 'please-select',
            'attr' => [
                'notice' => $this->translator->trans('which-card-receive-priority-pass'),
            ],
        ]);
    }

    private function buildVisaForm(FormBuilderInterface $builder): void
    {
        $builder->add('countryVisa', ChoiceType::class, [
            'choices' => array_flip($this->localizer->getLocalizedCountries()),
            'label' => 'label.country',
            'required' => true,
            'placeholder' => 'please-select',
        ]);

        $builder->add('numberEntries', TextType::class, [
            'label' => 'number-of-entries',
            'required' => true,
            'attr' => [
                'notice' => $this->translator->trans('multiple-1-etc'),
            ],
        ]);

        $builder->add('fullName', TextType::class, \array_merge(
            [
                'label' => 'cart.full-name',
                'required' => true,
            ],
            $this->documentHelper->preFillData('fullName', $builder)
        ));

        $builder->add('issueDate', DateType::class, [
            'label' => 'issue-date',
            'invalid_message' => 'invalid_date_and_time',
            'required' => false,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('validFrom', DateType::class, [
            'label' => 'valid-from',
            'invalid_message' => 'invalid_date_and_time',
            'required' => false,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('expirationDate', DateType::class, [
            'label' => 'valid-until',
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
            'required' => false,
        ]);

        $builder->add('visaNumber', TextType::class, [
            'label' => 'visa-number',
            'required' => false,
        ]);

        $builder->add('category', TextType::class, [
            'label' => 'coupon.category',
            'required' => false,
        ]);

        $builder->add('durationInDays', IntegerType::class, [
            'label' => 'duration-in-days',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('how-long-allowed-stay-country'),
                'keyboardType' => KeyboardType::NUMERIC,
            ],
        ]);

        $builder->add('issuedIn', TextType::class, [
            'label' => 'issued-in',
            'required' => false,
        ]);
    }

    private function buildInsuranceCardForm(FormBuilderInterface $builder)
    {
        $builder->add('insuranceType', ChoiceType::class, [
            'choices' => \array_flip(Providercoupon::INSURANCE_TYPE_LIST),
            'label' => 'insurance-type',
            'required' => true,
        ]);

        $builder->add('insuranceCompany', TextType::class, [
            'label' => 'insurance-company',
            'required' => true,
        ]);

        $builder->add('nameOnCard', TextType::class, \array_merge(
            [
                'label' => 'name-on-card',
                'required' => true,
            ],
            $this->documentHelper->preFillData('fullName', $builder)
        ));

        $builder->add('memberNumber', TextType::class, [
            'label' => 'member-number-id',
            'required' => true,
        ]);

        $builder->add('groupNumber', TextType::class, [
            'label' => 'group-number',
            'required' => false,
        ]);

        $builder->add('policyHolder', ChoiceType::class, \array_merge(
            [
                'choices' => \array_flip(Providercoupon::INSURANCE_POLICY_HOLDER_LIST),
                'label' => 'policy-holder',
                'required' => false,
            ],
            $this->documentHelper->preFillData('policyHolder', $builder)
        ));

        $builder->add('insuranceType2', ChoiceType::class, [
            'choices' => \array_flip(Providercoupon::INSURANCE_TYPE2_LIST),
            'label' => 'insurance-type',
            'required' => false,
            'placeholder' => 'please-select',
        ]);

        $builder->add('effectiveDate', DateType::class, \array_merge(
            [
                'label' => 'effective-date',
                'invalid_message' => 'invalid_date_and_time',
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'input' => 'datetime',
            ],
            $this->documentHelper->preFillData('effectiveDate', $builder)
        ));

        $builder->add('expirationDate', DateType::class, \array_merge(
            [
                'label' => 'card.expiration',
                'invalid_message' => 'invalid_date_and_time',
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'input' => 'datetime',
            ],
            $this->documentHelper->preFillData('insuranceExpirationDate', $builder)
        ));

        $builder->add('memberServicePhone', TextType::class, [
            'label' => 'member-service-phone',
            'required' => false,
        ]);

        $builder->add('preauthPhone', TextType::class, [
            'label' => 'preauthorization-phone',
            'required' => false,
        ]);

        $builder->add('otherPhone', TextType::class, [
            'label' => 'other-phone',
            'required' => false,
        ]);
    }

    private function buildVaccineCardForm(FormBuilderInterface $builder)
    {
        $builder->add('disease', TextCompletionType::class, [
            'completionLink' => $this->mobileUrlGenerator->generate('awm_disease_completion'),
            'label' => 'disease',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('i.e-covid-19'),
            ],
        ]);

        $builder->add('firstDoseDate', DateType::class, [
            'label' => 'first-dose-date',
            'required' => true,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
        $builder->add('firstDoseVaccine', TextType::class, [
            'label' => 'first-dose-vaccine',
            'required' => true,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('secondDoseDate', DateType::class, [
            'label' => 'second-dose-date',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
        $builder->add('secondDoseVaccine', TextType::class, [
            'label' => 'second-dose-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('boosterDate', DateType::class, [
            'label' => 'booster-date',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
        $builder->add('boosterVaccine', TextType::class, [
            'label' => 'booster-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('secondBoosterDate', DateType::class, [
            'label' => 'second-booster-date',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);
        $builder->add('secondBoosterVaccine', TextType::class, [
            'label' => 'second-booster-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('vaccinePassportName', TextType::class, \array_merge(
            [
                'label' => 'traveler_profile.passport-name',
                'required' => false,
            ],
            $this->documentHelper->preFillData('fullName', $builder)
        ));
        $builder->add('dateOfBirth', DateType::class, [
            'label' => 'traveler_profile.date-of-birth',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('vaccinePassportNumber', TextType::class, [
            'label' => 'traveler_profile.passport-number',
            'required' => false,
        ]);

        $builder->add('certificateIssued', DateType::class, [
            'label' => 'certificate-issued',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('countryIssue', ChoiceType::class, \array_merge(
            [
                'choices' => \array_flip($this->localizer->getLocalizedCountries()),
                'label' => 'country-issue',
                'required' => false,
                'placeholder' => 'please-select',
            ],
            $this->documentHelper->preFillData('countryId', $builder)
        ));
    }

    private function buildDriversLicenseForm(FormBuilderInterface $builder)
    {
        [
            'countries' => $countries,
            'stateChoices' => $stateChoices,
            'statePreFillData' => $statePreFillData
        ] = $this->documentHelper->prepareCountries($builder);

        $builder->add('country', ChoiceType::class, \array_merge(
            [
                'choices' => \array_flip($countries),
                'label' => 'label.country',
                'placeholder' => 'please-select',
                'required' => true,
            ],
            $this->documentHelper->preFillData('countryId', $builder)
        ));

        $builder->add('state', ChoiceType::class, \array_merge(
            [
                'choices' => \array_flip($stateChoices ?? []),
                'label' => 'cart.state',
                'required' => false,
                'placeholder' => 'please-select',
            ],
            $statePreFillData
        ));

        $builder->add('internationalLicense', CheckboxType::class, [
            'label' => 'international-drivers-license',
            'required' => false,
            'value' => 1,
        ]);

        $builder->add('licenseNumber', TextType::class, [
            'label' => 'driver-license-number',
            'required' => true,
        ]);

        $builder->add('dateOfBirth', DateType::class, [
            'label' => 'traveler_profile.date-of-birth',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('issueDate', DateType::class, [
            'label' => 'issue-date',
            'required' => false,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('expirationDate', DateType::class, [
            'label' => 'card.expiration',
            'required' => true,
            'invalid_message' => 'invalid_date_and_time',
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'input' => 'datetime',
        ]);

        $builder->add('fullName', TextType::class, \array_merge(
            [
                'label' => 'cart.full-name',
                'required' => false,
            ],
            $this->documentHelper->preFillData('fullName', $builder)
        ));

        $builder->add('sex', TextType::class, [
            'label' => 'sex',
            'required' => false,
        ]);

        $builder->add('eyes', TextType::class, [
            'label' => 'eyes',
            'required' => false,
        ]);

        $builder->add('height', TextType::class, [
            'label' => 'height',
            'required' => false,
        ]);

        $builder->add('class', TextType::class, [
            'label' => 'class',
            'required' => false,
        ]);

        $builder->add('organDonor', CheckboxType::class, [
            'label' => 'organ-donor',
            'required' => false,
            'value' => 1,
        ]);
    }

    private function addAccountHeader(FormBuilderInterface $builder, Providercoupon $document)
    {
        $headerBlock = new AccountHeader();
        $headerBlock->providerKind = Providercoupon::DOCUMENT_TYPE_TO_KEY_MAP[$document->getTypeid()];
        $headerBlock->providerName = $this->documentHelper->getTranslatedDocumentTitle($document);

        $builder->add(
            Redesign2023FallDict::ACCOUNT_HEADER,
            BlockContainerType::class,
            ['blockData' => $headerBlock]
        );
    }
}
