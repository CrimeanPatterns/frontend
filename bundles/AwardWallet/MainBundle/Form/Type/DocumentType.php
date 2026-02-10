<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Form\Model\DocumentModel;
use AwardWallet\MainBundle\Form\Transformer\DocumentTransformer;
use AwardWallet\MainBundle\Form\Type\Helpers\DocumentHelper;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var DataTransformerInterface
     */
    private $transformer;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AccountListManager */
    private $accountListManager;
    private DocumentHelper $documentHelper;
    private string $projectPath;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        LocalizeService $localizer,
        DocumentTransformer $transformer,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        DocumentHelper $documentHelper,
        string $projectPath
    ) {
        $this->localizer = $localizer;
        $this->transformer = $transformer;
        $this->translator = $translator;
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->accountListManager = $accountListManager;
        $this->documentHelper = $documentHelper;
        $this->projectPath = $projectPath;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Providercoupon $document */
        $document = $builder->getData();

        parent::buildForm($builder, $options);

        $builder->add('owner', OwnerMetaType::class, [
            'label' => 'document.label.owner',
            'translation_domain' => 'messages',
            'designation' => OwnerRepository::FOR_ACCOUNT_ASSIGNMENT,
            'required' => true,
        ]);

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

        $builder->add('isArchived', CheckboxType::class, [
            'label' => $this->translator->trans('account.label.is-archived'),
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('account.label.is-archived.notice'),
            ],
        ]);

        $builder->add('description', TextareaType::class,
            [
                'allow_quotes' => true,
                'allow_tags' => true,
                'allow_urls' => true,
                'required' => false,
                'label' => 'account.label.comment',
            ]
        );
        $this->documentHelper->addIsNewDocument($builder, $document);

        $builder->add('useragents', SharingOptionsType::class, ['is_add_form' => empty($document->getProvidercouponid())]);

        $builder->addModelTransformer($this->transformer);

        $javaScripts = [
            'documentEdit' => file_get_contents($this->projectPath . '/bundles/AwardWallet/MainBundle/Resources/js/Form/Document/edit.js'),
        ];
        $builder->setAttribute('javascripts', $javaScripts);
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

        $builder->add('passportIssueDate', DatePickerType::class, [
            'label' => 'traveler_profile.passport-issueDate',
            'required' => false,
            'invalid_message' => /** @Desc("Please, enter valid date and time.") */ 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '1910:' . date('Y'),
            ],
        ]);

        $builder->add('passportCountry', ChoiceType::class, [
            'choices' => array_flip($this->localizer->getLocalizedCountries()),
            'label' => 'traveler_profile.passport-country',
            'required' => false,
        ]);

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'invalid_message' => /** @Desc("Please, enter valid date and time.") */ 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-10:+30',
            ],
            'required' => false,
        ]);
    }

    public function buildTrustedTravelerForm(FormBuilderInterface $builder)
    {
        $builder->add('travelerNumber', TextType::class, [
            'label' => 'traveler_profile.number',
            'required' => true,
        ]);

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'invalid_message' => /** @Desc("Please, enter valid date and time.") */ 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-10:+30',
            ],
            'required' => false,
        ]);
    }

    public function buildVaccineCardForm(FormBuilderInterface $builder): void
    {
        $this->documentHelper->addHiddenOwnersList($builder);

        $builder->add('disease', TextType::class, [
            'label' => 'disease',
            'required' => false,
            'attr' => [
                'class' => 'autocomplete-data-choices',
                'data-choices' => json_encode(array_values(
                    array_map(fn ($item) => ['label' => $item], DocumentHelper::getDiseaseList())
                )),
                'notice' => $this->translator->trans('i.e-covid-19'),
            ],
        ]);

        $builder->add('firstDoseDate', DatePickerType::class, [
            'label' => 'first-dose-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+1',
            ],
            'required' => true,
        ]);
        $builder->add('firstDoseVaccine', TextType::class, [
            'label' => 'first-dose-vaccine',
            'required' => true,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('secondDoseDate', DatePickerType::class, [
            'label' => 'second-dose-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+1',
            ],
            'required' => false,
        ]);
        $builder->add('secondDoseVaccine', TextType::class, [
            'label' => 'second-dose-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('boosterDate', DatePickerType::class, [
            'label' => 'booster-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+5',
            ],
            'required' => false,
        ]);
        $builder->add('boosterVaccine', TextType::class, [
            'label' => 'booster-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('secondBoosterDate', DatePickerType::class, [
            'label' => 'second-booster-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+5',
            ],
            'required' => false,
        ]);
        $builder->add('secondBoosterVaccine', TextType::class, [
            'label' => 'second-booster-vaccine',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('vaccine-pfizer-etc'),
            ],
        ]);

        $builder->add('vaccinePassportName', TextType::class, [
            'label' => 'traveler_profile.passport-name',
            'required' => false,
        ] + $this->documentHelper->preFillData('fullName', $builder)
        );

        $builder->add('dateOfBirth', DatePickerType::class, [
            'label' => 'traveler_profile.date-of-birth',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-121:+0',
            ],
            'required' => false,
        ]);

        $builder->add('vaccinePassportNumber', TextType::class, [
            'label' => 'traveler_profile.passport-number',
            'required' => false,
        ]);

        $builder->add('certificateIssued', DatePickerType::class, [
            'label' => 'certificate-issued',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+5',
            ],
            'required' => false,
        ]);

        $builder->add('countryIssue', ChoiceType::class, [
            'choices' => array_flip($this->localizer->getLocalizedCountries()),
            'label' => 'country-issue',
            'required' => false,
        ] + $this->documentHelper->preFillData('countryId', $builder)
        );
    }

    public function buildInsuranceCardForm(FormBuilderInterface $builder): void
    {
        $this->documentHelper->addHiddenOwnersList($builder);

        $builder->add('insuranceType', ChoiceType::class, [
            'choices' => array_flip(Providercoupon::INSURANCE_TYPE_LIST),
            'label' => 'insurance-type',
            'required' => true,
        ]);

        $builder->add('insuranceCompany', TextType::class, [
            'label' => 'insurance-company',
            'required' => true,
        ]);

        $builder->add('nameOnCard', TextType::class, [
            'label' => 'name-on-card',
            'required' => true,
        ] + $this->documentHelper->preFillData('fullName', $builder)
        );

        $builder->add('memberNumber', TextType::class, [
            'label' => 'member-number-id',
            'required' => true,
        ]);

        $builder->add('groupNumber', TextType::class, [
            'label' => 'group-number',
            'required' => false,
        ]);

        $builder->add('policyHolder', ChoiceType::class, [
            'choices' => array_flip(Providercoupon::INSURANCE_POLICY_HOLDER_LIST),
            'label' => 'policy-holder',
            'required' => true,
        ] + $this->documentHelper->preFillData('policyHolder', $builder)
        );

        $builder->add('insuranceType2', ChoiceType::class, [
            'choices' => array_flip(Providercoupon::INSURANCE_TYPE2_LIST),
            'label' => 'insurance-type',
            'required' => false,
        ]);

        $builder->add('effectiveDate', DatePickerType::class, [
            'label' => 'effective-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+5',
            ],
            'required' => false,
        ] + $this->documentHelper->preFillData('effectiveDate', $builder)
        );

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'card.expiration',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+15',
            ],
            'required' => false,
        ] + $this->documentHelper->preFillData('insuranceExpirationDate', $builder)
        );

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

    public function buildVisaForm(FormBuilderInterface $builder): void
    {
        $this->documentHelper->addHiddenOwnersList($builder);

        $builder->add('countryVisa', ChoiceType::class, [
            'choices' => array_flip(['' => $this->translator->trans('select-country')] + $this->localizer->getLocalizedCountries()),
            'label' => 'label.country',
            'required' => true,
        ]);

        $builder->add('numberEntries', TextType::class, [
            'label' => 'number-of-entries',
            'required' => true,
            'attr' => [
                'notice' => $this->translator->trans('multiple-1-etc'),
            ],
        ]);

        $builder->add('fullName', TextType::class, [
            'label' => 'cart.full-name',
            'required' => true,
        ] + $this->documentHelper->preFillData('fullName', $builder)
        );

        $builder->add('issueDate', DatePickerType::class, [
            'label' => 'issue-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+20',
            ],
            'required' => false,
        ]);

        $builder->add('validFrom', DatePickerType::class, [
            'label' => 'valid-from',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+20',
            ],
            'required' => false,
        ]);

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'valid-until',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+20',
            ],
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
            ],
        ]);

        $builder->add('issuedIn', TextType::class, [
            'label' => 'issued-in',
            'required' => false,
        ]);
    }

    public function buildDriversLicenseForm(FormBuilderInterface $builder): void
    {
        $this->documentHelper->addHiddenOwnersList($builder);

        [
            'countries' => $countries,
            'stateChoices' => $stateChoices,
            'statePreFillData' => $statePreFillData
        ] = $this->documentHelper->prepareCountries($builder);

        $builder->add('country', ChoiceType::class, [
            'choices' => array_flip(['' => $this->translator->trans('select-country')] + $countries),
            'label' => 'label.country',
            'required' => true,
        ] + $this->documentHelper->preFillData('countryId', $builder)
        );

        $builder->add('state', ChoiceType::class, [
            'choices' => array_flip($stateChoices ?? []),
            'label' => 'cart.state',
            'required' => false,
        ] + $statePreFillData);

        $builder->add('internationalLicense', CheckboxType::class, [
            'label' => 'international-drivers-license',
            'required' => false,
            'value' => 1,
        ]);

        $builder->add('licenseNumber', TextType::class, [
            'label' => 'driver-license-number',
            'required' => true,
        ]);

        $builder->add('dateOfBirth', DatePickerType::class, [
            'label' => 'traveler_profile.date-of-birth',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-121:+0',
            ],
            'required' => false,
        ]);

        $builder->add('issueDate', DatePickerType::class, [
            'label' => 'issue-date',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-20:+20',
            ],
            'required' => false,
        ]);

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'card.expiration',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '0:+25',
            ],
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ]);

        $builder->add('fullName', TextType::class, [
            'label' => 'cart.full-name',
            'required' => false,
        ] + $this->documentHelper->preFillData('fullName', $builder));

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
            'allow_quotes' => true,
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

    public function buildPriorityPassForm(FormBuilderInterface $builder): void
    {
        $this->documentHelper->addHiddenOwnersList($builder);

        $builder->add('accountNumber', TextType::class, [
            'label' => 'priority-pass-number',
            'required' => true,
        ]);

        $builder->add('expirationDate', DatePickerType::class, [
            'label' => 'traveler_profile.passport-expiration',
            'invalid_message' => 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '-10:+30',
            ],
            'required' => false,
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
            'choices' => array_flip($cardsChoices ?? []),
            'label' => 'credit-card',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('which-card-receive-priority-pass'),
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'document_form';
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

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('document.label.owner'))->setDesc('Document Owner'),
            (new Message('traveler_profile.number'))->setDesc('Trusted Traveler Number'),
            (new Message('traveler_profile.date-of-birth'))->setDesc('Date of Birth'),
            (new Message('traveler_profile.seat-preference'))->setDesc('Seat preference'),
            (new Message('traveler_profile.meal-preference'))->setDesc('Meal preference'),
            (new Message('traveler_profile.home-airport'))->setDesc('Home Airport'),
            (new Message('traveler_profile.passport'))->setDesc('Passport'),
            (new Message('traveler_profile.passport-notice'))->setDesc('As it appears on the passport'),
            (new Message('traveler_profile.passport-name'))->setDesc('Name'),
            (new Message('traveler_profile.passport-number'))->setDesc('Passport Number'),
            (new Message('traveler_profile.passport-issueDate'))->setDesc('Date of Issue'),
            (new Message('traveler_profile.passport-country'))->setDesc('Country of Issue'),
            (new Message('traveler_profile.passport-expiration'))->setDesc('Expiration'),

            (new Message('vaccine-card'))->setDesc('Vaccine Card'),
            (new Message('disease'))->setDesc('Disease'),
            (new Message('i.e-covid-19'))->setDesc('i.e. Covid 19'),
            (new Message('first-dose-date'))->setDesc('1st Dose Date'),
            (new Message('first-dose-vaccine'))->setDesc('1st Dose Vaccine'),
            (new Message('second-dose-date'))->setDesc('2nd Dose Date'),
            (new Message('second-dose-vaccine'))->setDesc('2nd Dose Vaccine'),
            (new Message('booster-date'))->setDesc('Booster Date'),
            (new Message('booster-vaccine'))->setDesc('Booster Vaccine'),
            (new Message('second-booster-date'))->setDesc('2nd Booster Date'),
            (new Message('second-booster-vaccine'))->setDesc('2nd Booster Vaccine'),
            (new Message('certificate-issued'))->setDesc('Certificate Issued'),
            (new Message('country-issue'))->setDesc('Country of Issue'),
            (new Message('doses'))->setDesc('Doses'),
            (new Message('vaccine-pfizer-etc'))->setDesc('Vaccine manufacturer, i.e. Pfizer, Moderna, etc.'),

            (new Message('insurance-card'))->setDesc('Insurance Card'),
            (new Message('insurance-type'))->setDesc('Insurance Type'),
            (new Message('insurance-company'))->setDesc('Insurance Company'),
            (new Message('name-on-card'))->setDesc('Name on the Card'),
            (new Message('member-number-id'))->setDesc('Member Number / ID'),
            (new Message('group-number'))->setDesc('Group Number'),
            (new Message('policy-holder'))->setDesc('Policy Holder'),
            (new Message('effective-date'))->setDesc('Effective Date'),
            (new Message('member-service-phone'))->setDesc('Member Services Phone'),
            (new Message('preauthorization-phone'))->setDesc('Preauthorization Phone'),
            (new Message('other-phone'))->setDesc('Other Phone'),

            (new Message('visa'))->setDesc('Visa'),
            (new Message('visa-for'))->setDesc('Visa for %name%'),
            (new Message('number-of-entries'))->setDesc('Number of Entries'),
            (new Message('issue-date'))->setDesc('Issue Date'),
            (new Message('valid-from'))->setDesc('Valid From'),
            (new Message('valid-until'))->setDesc('Valid Until'),
            (new Message('visa-number'))->setDesc('Visa Number'),
            (new Message('duration-in-days'))->setDesc('Duration in days'),
            (new Message('issued-in'))->setDesc('Issued In'),
            (new Message('select-country'))->setDesc('Select Country'),
            (new Message('how-long-allowed-stay-country'))->setDesc('How long you are allowed to stay in the country after entry'),
            (new Message('multiple-1-etc'))->setDesc('Multiple, 1, etc'),

            (new Message('international-drivers-license'))->setDesc("International Driver's License"),
            (new Message('driver-license-number'))->setDesc("Driver's License Number"),
            (new Message('title-drivers-license'))->setDesc("%name% Driver's License"),
            (new Message('sex'))->setDesc('Sex'),
            (new Message('eyes'))->setDesc('Eyes'),
            (new Message('height'))->setDesc('Height'),
            (new Message('class'))->setDesc('Class'),
            (new Message('organ-donor'))->setDesc('Organ Donor'),

            (new Message('priority-pass'))->setDesc('Priority Pass'),
            (new Message('does-say-select-pass-card'))->setDesc('Does it say "Select" on your Priority Pass card?'),
            (new Message('credit-card'))->setDesc('Credit Card'),
            (new Message('which-card-receive-priority-pass'))->setDesc('Through which credit card (if any) did you receive this Priority Pass?'),
            (new Message('priority-pass-number'))->setDesc('Priority Pass Number'),
            (new Message('pass-type'))->setDesc('Pass Type'),
        ];
    }
}
