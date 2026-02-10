<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\BaseFieldsDict;
use AwardWallet\MainBundle\Form\Account\Builder;
use AwardWallet\MainBundle\Form\Account\Template;
use AwardWallet\MainBundle\Form\Extension\SorterFormExtension;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Model\AccountModelMobile;
use AwardWallet\MainBundle\Form\Transformer\BalanceTransformer;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Form\Type\HtmlType;
use AwardWallet\MainBundle\Form\Type\Mobile\Loyalty\CapitalcardsOauthMobileType;
use AwardWallet\MainBundle\Form\Type\Mobile\MailboxLinkingType;
use AwardWallet\MainBundle\Form\Type\NoticeType;
use AwardWallet\MainBundle\Form\Type\OauthType;
use AwardWallet\MainBundle\Form\Type\SeparatorType;
use AwardWallet\MainBundle\Form\Type\SharingOptionsType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountFormHtmlProvider\MobileHtmlRenderer;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\BalanceWatch\Query;
use AwardWallet\MainBundle\Service\BalanceWatch\TransferOptions;
use AwardWallet\MobileBundle\Form\Type\AccountType\MobileFieldsDict;
use AwardWallet\MobileBundle\Form\Type\AccountType\Redesign2023FallDict;
use AwardWallet\MobileBundle\Form\Type\Components\TextCompletionType;
use AwardWallet\MobileBundle\Form\Type\Helpers\AccountHelper;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\ArrayKeyMatcher;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher\UnmatchedAnchor;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Sorter;
use AwardWallet\MobileBundle\Form\Type\NewDesign\GroupDescType;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\CurrencyAndBalanceType;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\ToggleButton;
use AwardWallet\MobileBundle\Form\View\Block\AccountHeader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AccountType extends AbstractType
{
    public const CUSTOM_ACCOUNT_EXTENSION_PATH = 'bundles/AwardWallet/MainBundle/Resources/js/Form/Account/customType.js';

    /**
     * @var Builder
     */
    protected $templateBuilder;
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var DataTransformerInterface
     */
    protected $dataTransformer;
    /**
     * @var AccountHelper
     */
    protected $loyaltyHelper;
    /**
     * @var AwTokenStorageInterface
     */
    protected $tokenStorage;
    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;
    /**
     * @var LocalizeService
     */
    private $localizeService;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    private Query $bwQuery;
    private TransferOptions $bwTransferOptions;
    private CurrencyHelper $currencyHelper;

    private MobileHtmlRenderer $mobileHtmlRenderer;

    public function __construct(
        Builder $templateBuilder,
        AwTokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        ApiVersioningService $apiVersioning,
        EntityManagerInterface $entityManager,
        DataTransformerInterface $dataTransformer,
        AccountHelper $loyaltyHelper,
        MobileExtensionLoader $mobileExtensionLoader,
        Query $bwQuery,
        TransferOptions $bwTransferOptions,
        LocalizeService $localizeService,
        AuthorizationCheckerInterface $authorizationChecker,
        CurrencyHelper $currencyHelper,
        MobileHtmlRenderer $mobileHtmlRenderer
    ) {
        $this->templateBuilder = $templateBuilder;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->apiVersioning = $apiVersioning;
        $this->entityManager = $entityManager;
        $this->dataTransformer = $dataTransformer;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->tokenStorage = $tokenStorage;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->localizeService = $localizeService;
        $this->authorizationChecker = $authorizationChecker;
        $this->bwQuery = $bwQuery;
        $this->bwTransferOptions = $bwTransferOptions;
        $this->currencyHelper = $currencyHelper;
        $this->mobileHtmlRenderer = $mobileHtmlRenderer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->tokenStorage->getBusinessUser();
        /** @var Account $account */
        $account = $builder->getData();
        /** @var Provider $provider */
        $provider = $options['provider'];
        $account->setProviderid($provider);

        $isCustomAccount = !$provider;
        $isDiscoveredAccount = (ACCOUNT_PENDING === $account->getState());
        $isExistingAccount = ((bool) $account->getAccountid());

        $this->loyaltyHelper->addUserAgent($builder, $account);
        $oauthFormName = null;

        if ($isCustomAccount) {
            $this->buildCustomAccountFields($builder, $options, $user);
            $this->mobileExtensionLoader->loadExtensionByPath(
                $builder,
                $this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL) ?
                    [self::CUSTOM_ACCOUNT_EXTENSION_PATH] :
                    []
            );
        } else {
            $template = $this->templateBuilder->getFormTemplate($this->tokenStorage->getBusinessUser(), $provider, $account->getAccountid());
            $oauthFormName = $this->buildAccountFields($builder, $template);
            $this->mobileExtensionLoader->loadExtensionByPath($builder);
        }

        if (($isExistingAccount || $isCustomAccount) && !$isDiscoveredAccount) {
            $this->buildCommonAccountFields($builder, $options);
        }

        if ($this->apiVersioning->supports(MobileVersions::PASSWORD_ACCESS)) {
            $this->addDisableClientPasswordAccessField($builder, $account);
        }

        if (
            $isExistingAccount && !$isCustomAccount
            && ($provider->getState() !== PROVIDER_RETAIL)
            && !$isDiscoveredAccount
        ) {
            $this->addDisabledFields($builder, $account);
        }

        if ($builder->has(BaseFieldsDict::BALANCE)) {
            $builder->get(BaseFieldsDict::BALANCE)->addViewTransformer(new BalanceTransformer());

            if (!$isExistingAccount && $isCustomAccount && !$isDiscoveredAccount) {
                $this->addExpirationField($builder, $account);
            }
        }

        $this->addHideSubaccountsFields($builder, $account);

        if (
            $this->apiVersioning->supports(MobileVersions::ACCOUNT_BALANCE_WATCH)
            && !$isDiscoveredAccount
        ) {
            $this->accountBalanceWatchFields($builder, $account);
        }

        if (!$isDiscoveredAccount) {
            $this->loyaltyHelper->addCardImagesAndBarCode(
                $builder,
                $account->getAccountid() ? $account : null,
                $provider
            );
        }

        $builder->addModelTransformer($this->dataTransformer);

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $this->modifyRedesign2023Fall($builder, $account);
        }

        $builder->setAttribute(SorterFormExtension::ATTRIBUTE_NAME, $this->buildSorter($provider, $oauthFormName));

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
        return 'mobile_account';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'provider' => null,
            'data_class' => AccountModel::class,
        ]);
    }

    protected function addDisableClientPasswordAccessField(FormBuilderInterface $builder, Account $account)
    {
        $isDiscoveredAccount = (ACCOUNT_PENDING === $account->getState());
        $isExistingAccount = ((bool) $account->getAccountid());

        if (
            $isExistingAccount
            && ($provider = $account->getProviderid())
            && $provider->canAutologin()
            && !$isDiscoveredAccount
        ) {
            $builder->add(
                MobileFieldsDict::DISABLE_CLIENT_PASSWORD_ACCESS,
                CheckboxType::class,
                array_merge(
                    [
                        'label' => $this->translator->trans('account.autologin.disabled'),
                        'required' => false,
                        'mapped' => !$account->isDisableClientPasswordAccess(),
                        'submitData' => true,
                        'attr' => [
                            'passwordAccess' => [
                                'route' => $this->urlGenerator->generate('awm_account_autologin_enable', ['accountId' => $account->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                                'trigger_value' => true,
                            ],
                        ],
                    ],
                    $account->isDisableClientPasswordAccess() ? ['data' => true] : []
                )
            );
        }
    }

    protected function getCurrencyName(Account $account)
    {
        if ($account->getProviderid()) {
            $currency = $account->getProviderid()->getCurrency();
        } elseif ($account->getId() && $account->getCurrency()) {
            $currency = $account->getCurrency();
        } else {
            $currency = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Currency::class)->findOneByName('Points');
        }

        $currencyName = $this->translator->trans(/** @Ignore */ $curKey = 'name.' . $currency->getCurrencyid(), [], 'currency');

        if ($currencyName == $curKey) {
            return $currency->getName();
        }

        return $currencyName;
    }

    protected function addExpirationField(FormBuilderInterface $builder, Account $account)
    {
        if (!$this->apiVersioning->supports(MobileVersions::FORM_DATE_PICKER)) {
            return;
        }

        if ($account->gotExpirationFromSite()) {
            $notice = $this->translator->trans("account.expiration.notice-auto");
            $disabled =
                !(empty($account->getExpirationdate())
                && !empty($account->getBalance())
                && $account->getExpirationdate() < time());
        } else {
            $disabled =
                $this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)
                && $account->getDonttrackexpiration();
            $notice = $this->translator->trans("account.expiration.notice-manual", ['%currency%' => $this->getCurrencyName($account)]);
        }

        if (!$builder->has(BaseFieldsDict::EXPIRATION_DATE)) {
            $builder->add(BaseFieldsDict::EXPIRATION_DATE, DateType::class, [
                'label' => 'account.label.expiration',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'required' => false,
                'disabled' => $disabled,
                'input' => 'datetime',
                'attr' => [
                    'notice' => $notice,
                    'readonly' => 'readonly',
                ],
            ]);
        }

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
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
    }

    protected function buildCustomAccountFields(FormBuilderInterface $builder, array $options, Usr $user)
    {
        /** @var Account $account */
        $account = $builder->getData();
        $builder
            ->add(BaseFieldsDict::KIND, ChoiceType::class, [
                'label' => 'coupon.type',
                'choices' => (function () {
                    $kinds = Provider::getKinds();
                    unset($kinds[PROVIDER_KIND_DOCUMENT]);

                    return \array_flip($kinds);
                })(),
                'required' => true,
                'placeholder' => 'please-select',
            ])
            ->add(BaseFieldsDict::PROGRAM_NAME, TextCompletionType::class, [
                'required' => true,
                'label' => 'account.program',
                'completionLink' => $this->urlGenerator->generate('awm_newapp_provider_completion'),
            ])
            ->add(BaseFieldsDict::LOGIN_URL, TextType::class, [
                'required' => false,
                'label' => /** @Ignore */ 'URL',
                'allow_urls' => true,
            ])
            ->add(BaseFieldsDict::LOGIN, TextType::class, [
                'required' => true,
                'label' => 'account.label',
                'allow_urls' => true,
            ])
            ->add(BaseFieldsDict::LOGIN_2, TextType::class, [
                'required' => false,
                'label' => 'second-login',
                'allow_urls' => true,
                'attr' => [
                    'notice' => $this->translator->trans("second-login.notice"),
                ],
                'constraints' => [
                    new Length([
                        'min' => 1,
                        'max' => 80,
                        'allowEmptyString' => true,
                    ]),
                ],
            ])
            ->add(BaseFieldsDict::CUSTOM_ELITE_LEVEL, TextType::class, [
                'required' => false,
                'label' => 'alliances.elite-level',
                'constraints' => [
                    new Length([
                        'max' => 128,
                    ]),
                ],
            ]);

        $this->addCurrencyAndBalance($builder);
        $this->templateBuilder->addPasswordFields($builder, null, $account->getId());
    }

    protected function buildAccountFields(FormBuilderInterface $builder, Template $template): ?string
    {
        $user = $this->tokenStorage->getBusinessUser();
        /** @var Account $account */
        $account = $builder->getData();
        $provider = $account->getProviderid();
        $big3Improvements = $this->apiVersioning->supports(MobileVersions::ACCOUNT_BIG3_IMPROVEMENTS);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($template) {
            /** @var AccountModel $model */
            $model = $event->getForm()->getNormData();
            $model->setTemplate($template);
        });

        if (isset($template->fields['Pass'])) {
            if ($this->apiVersioning->supports(MobileVersions::JS_FORM_EXTENSIONS)) {
                $template->fields['Pass']['type'] = PasswordEditType::class;
            }

            if (
                StringUtils::isNotEmpty($account->getPass())
                && $this->authorizationChecker->isGranted('READ_PASSWORD', $account)
            ) {
                $template->fields['Pass']['options']['attr']['revealUrl'] = $this->urlGenerator->generate('awm_get_pass', ['accountId' => $account->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        if (!$big3Improvements) {
            // convert messages to html fields
            $messages = [];
            $n = 0;

            foreach ($template->messages as $n => $message) {
                $html = "<div>" . $message->text . "</div>";

                if (!empty($message->title)) {
                    $html = "<h1 class='{$message->class}'>" . $message->title . "</h1>" . $html;
                }
                $messages['message' . $n] = Template::getFieldTemplate('message' . $n, HtmlType::class, null, null, ['html' => $html]);
                $messages['message' . $n]['options']['mapped'] = false;
            }
            $n++;

            // build form
            $fields = array_merge($messages, $template->fields);
        } else {
            $fields = $template->fields;

            if ($provider && $provider->isBig3()) {
                $isEditMode = !empty($account->getId());

                if ($isEditMode) {
                    $builder->add('big3info_edit', MailboxLinkingType::class, [
                        'account' => $account,
                        'mapped' => false,
                    ]);
                    $builder->add('big3info_edit_message', HtmlType::class, [
                        'html' => $this->mobileHtmlRenderer->getBig3StatementNotice($account),
                        'mapped' => false,
                    ]);
                } else {
                    $builder->add('big3info_new', MailboxLinkingType::class, [
                        'account' => $account,
                        'mapped' => false,
                    ]);
                }
            }
        }

        $oauthFieldName = $this->getOauthField($fields);
        $oauthFormName = null;

        if (null !== $oauthFieldName) {
            $fields = $this->enrichAuthField($fields, $oauthFieldName, $template);
            $oauthFormName = $fields[$oauthFieldName]['property'];
        }

        foreach ($fields as $field) {
            if (DateType::class === $field['type']) {
                $field = $this->enrichDateTypeField($field);
            }

            // trim choice labels from parsers
            if (isset($field['options']['choices'])) {
                $field = $this->enrichChoiceTypeField($field);
            }

            $builder->add($field['property'], $field['type'], $field['options']);
        }

        if (
            $template->provider->isOauthProvider()
            && (null !== $oauthFieldName)
            && $this->apiVersioning->supports(MobileVersions::OAUTH_PROVIDERS)
        ) {
            $programName = $template->provider->getName();

            if ($this->apiVersioning->supports(MobileVersions::SEPARATORLESS_OAUTH_ACCOUNT_FORM)) {
                $builder->add(
                    MobileFieldsDict::TOP_DESC,
                    GroupDescType::class,
                    ['text' => new Trans('oauth.info', ['%programName%' => $programName]), 'attr' => ['blockName' => 'authInfo']]
                );

                if ($builder->has(BaseFieldsDict::LOGIN_2)) {
                    $builder->add(
                        MobileFieldsDict::MIDDLE_DESC,
                        GroupDescType::class,
                        ['text' => new Trans('oauth.info.2', ['%programName%' => $programName]), 'attr' => ['blockName' => 'authInfo']]
                    );
                }
            } else {
                $builder
                    ->add(
                        MobileFieldsDict::TOP_SEPARATOR,
                        SeparatorType::class,
                        ['mapped' => false, 'attr' => ['blockName' => 'authInfo']],
                    )
                    ->add(
                        MobileFieldsDict::TOP_DESC,
                        GroupDescType::class,
                        ['text' => new Trans('oauth.info', ['%programName%' => $programName]), 'attr' => ['blockName' => 'authInfo']]
                    )
                    ->add(
                        MobileFieldsDict::MIDDLE_SEPARATOR,
                        SeparatorType::class,
                        ['mapped' => false, 'attr' => ['blockName' => 'authInfo']]
                    );

                if ($builder->has(BaseFieldsDict::LOGIN_2)) {
                    $builder
                        ->add(
                            MobileFieldsDict::MIDDLE_DESC,
                            GroupDescType::class,
                            ['text' => new Trans('oauth.info.2', ['%programName%' => $programName]), 'attr' => ['blockName' => 'authInfo']]
                        )
                        ->add(
                            MobileFieldsDict::BOTTOM_SEPARATOR,
                            SeparatorType::class,
                            ['mapped' => false, 'attr' => ['blockName' => 'authInfo']]
                        );
                }
            }
        } else {
            $builder->remove($oauthFormName);
        }

        if (
            $this->apiVersioning->supports(MobileVersions::PWNED_TIMES_INFO)
            && !empty($account->getPwnedTimes())
            && $account->getCheckedby()
            && ($account->getSavepassword() !== SAVE_PASSWORD_LOCALLY)
        ) {
            $builder->add(BaseFieldsDict::NOTICE_PWNED, NoticeType::class, [
                'message' => $this->translator->trans('checked-hacked-passwords', [
                    '%bold_on%' => '<strong>',
                    '%bold_off%' => '</strong>',
                    '%count%' => $account->getPwnedTimes(),
                    '%count_formatted%' => $this->localizeService->formatNumber($account->getPwnedTimes()),
                ]),
            ]);
        }

        if ($this->apiVersioning->supports(MobileVersions::MULTIPLE_JS_FORM_EXTENSIONS)) {
            $builder->setAttribute('jsProviderExtension', \array_values($template->javaScripts));
        } else {
            if (isset($template->javaScripts['provider'])) {
                $builder->setAttribute('jsProviderExtension', $template->javaScripts['provider']);
            }
        }

        return $oauthFormName;
    }

    protected function buildCommonAccountFields(FormBuilderInterface $builder, $options)
    {
        /** @var Account $account */
        $account = $builder->getData();
        $isDiscoveredAccount = (ACCOUNT_PENDING === $account->getState());

        if (
            $this->apiVersioning->supports(MobileVersions::FORM_TEXTAREA)
            && !$isDiscoveredAccount
        ) {
            $builder->add(BaseFieldsDict::COMMENT, TextareaType::class,
                [
                    'label' => 'account.label.comment',
                    'allow_quotes' => true,
                    'allow_tags' => true,
                    'allow_urls' => true,
                    'required' => false,
                    'attr' => [
                        'notice' => $this->translator->trans("account.comment.notice"),
                    ],
                ]
            );
        }

        if (
            ($provider = $account->getProviderid())
            && (PROVIDER_RETAIL === $provider->getState())
        ) {
            return;
        }

        if (!$isDiscoveredAccount) {
            $this->addExpirationFields($builder, $account);
            $builder->add(BaseFieldsDict::GOAL, NumberType::class,
                [
                    'label' => 'account.label.goal',
                    'required' => false,
                    'attr' => [
                        'notice' => $this->translator->trans("account.goal.notice", ['%currency%' => $this->getCurrencyName($account)]),
                    ],
                ]
            );
            $builder->get(BaseFieldsDict::GOAL)->addViewTransformer(new BalanceTransformer(true));
        }
    }

    protected function addDisabledFields(FormBuilderInterface $builder, Account $account)
    {
        $builder->add(BaseFieldsDict::DISABLED, CheckboxType::class,
            [
                'label' => 'account.label.disabled',
                'required' => false,
                'attr' => [
                    'notice' => $this->translator->trans("account.label.disabled.notice"),
                ],
            ]
        );
    }

    protected function addExpirationFields(FormBuilderInterface $builder, Account $account)
    {
        if (!$this->apiVersioning->supports(MobileVersions::FORM_DATE_PICKER)) {
            return;
        }

        $this->addExpirationField($builder, $account);
        $currencyName = $this->getCurrencyName($account);

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $builder->add(
                $builder
                    ->create(Redesign2023FallDict::TRACK_EXPIRATION, SwitcherType::class, [
                        'label' => $this->translator->trans(/** @Desc("%image%%tag_open%%currency% expire%tag_close%") */ 'expiring-points-tracking',
                            \array_merge(
                                [
                                    '%currency%' => StringHandler::mb_ucfirst($currencyName),
                                ],
                                $this->apiVersioning->supports(MobileVersions::REACT_NATIVE_RENDER_HTML_6_TRANSIENT_RENDER_ENGINE) ?
                                    ['%tag_open%' => '<span>', '%tag_close%' => '</span>', '%image%' => '<div class="exp-track"><img src="assets/exp-track.png"/></div>'] :
                                    ['%tag_open%' => ' ',      '%tag_close%' => '',        '%image%' => '<span class="exp-track"><img src="assets/exp-track.png"/></span>']
                            )
                        ),
                        'required' => false,
                        'attr' => [
                            'notice' => $this->translator->trans(/** @Desc("Disable this option if you don't want AwardWallet to try to track the expiration of this program and you know these %currency% don't expire") */ 'expiring-points-tracking.notice', ['%currency%' => $currencyName]),
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
            $builder->add(MobileFieldsDict::DONT_TRACK_EXPIRATION, CheckboxType::class, [
                'label' => $this->translator->trans('award.account.list.currency-dont-expire', ['%currency%' => $currencyName]),
                'required' => false,
                'attr' => [
                    'notice' => $this->translator->trans("currency-dont-expire.notice", ['%points%' => $currencyName]),
                ],
            ]);
        }
    }

    protected function getOauthField(array $fields): ?string
    {
        foreach ($fields as $fieldName => $field) {
            if (OauthType::class === $field['type']) {
                return $fieldName;
            }
        }

        return null;
    }

    private function enrichChoiceTypeField(array $field): array
    {
        $field['options']['choices'] =
            it($field['options']['choices'])
            ->mapKeys(fn (string $label) => \trim($label))
            ->toArrayWithKeys();

        return $field;
    }

    private function enrichDateTypeField(array $field): array
    {
        $field['options']['format'] = 'yyyy-MM-dd';
        $field['options']['input'] = 'datetime';
        $field['options']['widget'] = 'single_text';

        unset($field['options']['datepicker_options']);

        return $field;
    }

    private function addHideSubaccountsFields(FormBuilderInterface $builder, Account $account)
    {
        if (!$this->apiVersioning->supports(MobileVersions::HIDE_SUBACCOUNTS)) {
            return;
        }

        if ($account->getSubaccounts()) {
            $builder->add(MobileFieldsDict::HIDE_SUBACCOUNT, EntityType::class, [
                'label' => $this->translator->trans(/** @Desc("Hide Sub Accounts") */ 'account.subacc.label-subaccounts'),
                'required' => false,
                'mapped' => false,
                'submitData' => true,
                'class' => Subaccount::class,
                'choice_label' => 'CreditCardFormattedDisplayName',
                'property_path' => '[id]',
                'multiple' => true,
                'expanded' => false,
                'query_builder' => function (EntityRepository $er) use ($account) {
                    return $er->createQueryBuilder('su')
                        ->where('su.accountid = ' . ($account->getId() ?: -1))
                        ->orderBy('su.displayname', 'ASC');
                },
                'choice_attr' => function ($subacc) {
                    $attr = [];
                    $attr['value'] = $subacc->getIsHidden();

                    if (null !== ($expirationDate = $subacc->getExpirationdate()) && 0 < (int) $expirationDate->diff(date_create())->format('%R%a')) {
                        $attr['inactive'] = true;
                    }

                    return $attr;
                },
            ]);
        }
    }

    private function accountBalanceWatchFields(FormBuilderInterface $builder, Account $account): void
    {
        if (!$account->isAllowBalanceWatch()) {
            return;
        }

        $user = $this->tokenStorage->getBusinessUser();

        if ($user->isBusiness()) {
            return;
        }

        $balanceWatch = $this->bwQuery->getAccountBalanceWatch($account);

        $providerRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $balanceWatchField = [
            'label' => $this->translator->trans('account.balancewatch.force-label'),
            'required' => false,
            'data' => (null === $account->getBalanceWatchStartDate() ? false : true),
            'attr' => [
                'notice' => $this->translator->trans('account.balancewatch.force-notice', [
                    '%link_faq_on%' => '<a href="' . $this->urlGenerator->generate('aw_faq_index', [], UrlGeneratorInterface::ABSOLUTE_URL) . '#74" target="_blank">',
                    '%link_faq_off%' => '</a>',
                    '%link_notify_on%' => '<a href="' . $this->urlGenerator->generate('aw_profile_notifications', [], UrlGeneratorInterface::ABSOLUTE_URL) . '" target="_blank">',
                    '%link_notify_off%' => '</a>',
                ]),
            ],
        ];

        $builder->add(MobileFieldsDict::IS_BUSINESS, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => 'false',
        ]);
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_DISABLED, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => ($account->isBalanceWatchDisabled() ? 'true' : 'false'),
        ]);
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_AW_PLUS, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => (!$user->isAwPlus() && $user->getBalanceWatchCredits() <= 0 ? 'false' : 'true'),
        ]);

        if ('false' === $builder->get(MobileFieldsDict::IS_BALANCE_WATCH_AW_PLUS)->getData()) {
            $builder->add(MobileFieldsDict::URL_PAY_AW_PLUS, HiddenType::class, [
                'required' => false,
                'mapped' => false,
                'submitData' => true,
                'data' => $this->urlGenerator->generate('aw_users_pay', ['forceId' => $account->getId()], $this->urlGenerator::ABSOLUTE_URL),
            ]);
        }
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_CREDITS, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => $user->getBalanceWatchCredits(),
        ]);
        $builder->add(MobileFieldsDict::URL_PAY_CREDIT, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => $this->urlGenerator->generate('aw_users_pay_balancewatchcredit', ['forceId' => $account->getId()], $this->urlGenerator::ABSOLUTE_URL),
        ]);
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_ACCOUNT_ERROR, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => $account->getErrorcode(),
        ]);
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_ACCOUNT_CAN_CHECK, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => (
                empty($account->getProviderid()->getCancheck())
                || false === $account->getProviderid()->getCancheckbalance()
                || !in_array($account->getProviderid()->getState(), [PROVIDER_ENABLED, PROVIDER_CHECKING_WITH_MAILBOX, PROVIDER_TEST])
            ) ? 'false' : 'true',
        ]);
        $builder->add(MobileFieldsDict::IS_BALANCE_WATCH_LOCAL_PASSWORD_EXCLUDE, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => in_array($account->getProviderid()->getProviderid(), BalanceWatchManager::EXCLUDED_PROVIDER_LOCAL_PASSWORD) ? 'true' : 'false',
        ]);

        $builder->add(MobileFieldsDict::BALANCE_WATCH, CheckboxType::class, $balanceWatchField);

        if ($account->isBalanceWatchDisabled() /* || $account->isDisabled() || SAVE_PASSWORD_LOCALLY == $account->getSavepassword() */) {
            $builder->get(MobileFieldsDict::BALANCE_WATCH)->setDisabled(true);
        }

        $commonOptions = [];

        if (null !== $balanceWatch && null !== $account->getBalanceWatchStartDate()) {
            $commonOptions = ['disabled' => true];

            if ($builder->has(BaseFieldsDict::DISABLED)) {
                $builder->get(BaseFieldsDict::DISABLED)->setDisabled(true);
            }
        }

        $builder->add(MobileFieldsDict::POINTS_SOURCE, ChoiceType::class, [
            'label' => $this->translator->trans('account.balancewatch.source-points', ['%currency%' => $this->getCurrencyName($account)]),
            'required' => true,
            'choices' => array_flip([
                0 => $this->translator->trans('account.option.please.select'),
                BalanceWatch::POINTS_SOURCE_TRANSFER => $this->translator->trans('account.balancewatch.source-points.transfer'),
                BalanceWatch::POINTS_SOURCE_PURCHASE => $this->translator->trans('account.balancewatch.source-points.purchase'),
                BalanceWatch::POINTS_SOURCE_OTHER => $this->translator->trans('account.balancewatch.source-points.other'),
            ]),
        ] + $commonOptions);

        $builder->add(MobileFieldsDict::TRANSFER_PROVIDER_CURRENCY, HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'submitData' => true,
            'data' => (empty($balanceWatch) || null === $balanceWatch->getTransferFromProvider() ? $this->getCurrencyName($account) : $balanceWatch->getTransferFromProvider()->getCurrency()->getName()),
        ]);
        $builder->add(MobileFieldsDict::TRANSFER_FROM_PROVIDER, TextCompletionType::class, [
            'required' => true,
            'label' => $this->translator->trans('account.balancewatch.transfer-from'),
            'completionLink' => $this->urlGenerator->generate('awm_newapp_provider_completion', ['requestFields' => 'currency']),
            //                'attr' => [ // todo: uncomment after fix form hint
            //                    'notice' => $this->translator->trans('account.balancewatch.transfer-from.notice'),
            //                ]
        ] + $commonOptions);
        $builder->get(MobileFieldsDict::TRANSFER_FROM_PROVIDER)->addModelTransformer(new CallbackTransformer(
            function ($provider) {
                if (empty($provider)) {
                    return null;
                }

                return htmlspecialchars_decode($provider->getDisplayname());
            },
            function ($providerName) use ($providerRep) {
                $result = $providerRep->createQueryBuilder('p')
                    ->where('p.name = :name')
                    ->orWhere('p.displayname = :name')
                    ->orWhere('p.programname = :name')
                    ->setParameter('name', htmlspecialchars($providerName))
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getResult();

                return empty($result) ? null : $result[0];
            }
        ));

        $builder->add(MobileFieldsDict::EXPECTED_POINTS, NumberType::class, [
            'label' => ($expectedNumberCaption = $this->translator->trans('account.balancewatch.expected-number-miles', ['%currency%' => $this->getCurrencyName($account)])),
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans('account.balancewatch.expected-number-miles-notice', ['%expectedNumberCaption%' => $expectedNumberCaption]),
            ],
        ] + $commonOptions);

        $builder->add(MobileFieldsDict::TRANSFER_REQUEST_DATE, ChoiceType::class, [
            'label' => $this->translator->trans('account.balancewatch.transfer-requested'),
            'required' => true,
            'choices' => array_flip($this->bwTransferOptions->get()),
        ] + $commonOptions);
        $builder->get(MobileFieldsDict::TRANSFER_REQUEST_DATE)->addModelTransformer(new CallbackTransformer(
            function ($requestDate) use ($balanceWatch) {
                if (empty($requestDate)) {
                    return null;
                }

                $diff = $requestDate->diff($balanceWatch->getCreationDate());
                $hours = ($diff->days ? $diff->days * 24 : 0) + $diff->h;

                $allowHours = $this->bwTransferOptions->get();

                return array_key_exists($hours, $allowHours) ? $hours : array_keys($allowHours)[\count($allowHours) - 1];
            },
            function (?int $requestDate) {
                if (null === $requestDate) {
                    return null;
                }

                return new \DateTime('-' . $requestDate . ' hour');
            }
        ));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (!array_key_exists(MobileFieldsDict::BALANCE_WATCH, $data) && !empty($data['ExpectedPoints'])) {
                unset($data['ExpectedPoints']);
                $event->setData($data);
            }
        });
    }

    private function enrichAuthField(array $fields, string $oauthFieldName, Template $template): array
    {
        $programName = $template->provider->getName();
        $fields[$oauthFieldName]['options'] = array_merge(
            $fields[$oauthFieldName]['options'],
            [
                'provider' => $template->provider,
                //                        'constraints' => [new Constraints\NotBlank(['message' => $this->translator->trans('error.auth.code-required', ['%programName%' => $programName])])],
                'error_bubbling' => true,
                'required' => false,
                'attr' => \array_merge(
                    $fields[$oauthFieldName]['options']['attr'] ?? [],
                    [
                        'blockName' => 'authinfo',
                        'requiredText' => $this->translator->trans(/** @Desc("Please authenticate this account via %programName% before saving this form.") */ 'error.auth.code-required', ['%programName%' => $programName]),
                    ]
                ),
            ]
        );

        if ($this->apiVersioning->supports(MobileVersions::CAPITALCARDS_AUTH_V3)) {
            if ($template->provider->getCode() === "capitalcards") {
                $fields[$oauthFieldName]['type'] = CapitalcardsOauthMobileType::class;
            }

            $fields[$oauthFieldName]['property'] = 'authinfo';
        }

        return $fields;
    }

    private function buildSorter(?Provider $provider, ?string $oauthFormName): Sorter
    {
        $field = static fn (string $field) => ArrayKeyMatcher::create($field);
        $regex = static fn (string $regex) => ArrayKeyMatcher::createByRegex($regex);
        $superpositionFactory = static fn ($cond) => [
            static fn (?string $fieldName) => $cond ? $field($fieldName) : null,
            static fn (?string $fieldName) => $cond ? null : $field($fieldName),
        ];
        [$whenBig3, $whenNotBig3] = $superpositionFactory($provider && $provider->isBig3());
        [$whenOauth, $whenNotOauth] = $superpositionFactory($provider && $provider->isOauthProvider());
        [$whenOauthFormExists] = $superpositionFactory(null !== $oauthFormName);
        [$whenUnited, $whenNotUnited] = $superpositionFactory($provider && (Provider::UNITED_ID === $provider->getId()));

        return new Sorter(\array_filter(
            $this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL) ?
                [
                    $regex('/^message\d+$/'),
                    $field('big3info_edit'),
                    $field('big3info_edit_message'),
                    $field(MobileFieldsDict::CARD_IMAGES),
                    $field(MobileFieldsDict::BARCODE),
                    $field(Redesign2023FallDict::ACCOUNT_HEADER),
                    $whenOauth(BaseFieldsDict::LOGIN_2),
                    $field(MobileFieldsDict::TOP_DESC),
                    $whenOauthFormExists($oauthFormName),
                    $field(MobileFieldsDict::MIDDLE_DESC),
                    $field(MobileFieldsDict::OWNER),
                    $field(BaseFieldsDict::KIND),
                    $field(BaseFieldsDict::PROGRAM_NAME),
                    $field(BaseFieldsDict::LOGIN),
                    $field(Redesign2023FallDict::FIELD_TOGGLER),
                    $whenNotOauth(BaseFieldsDict::LOGIN_2),
                    $whenNotOauth(Redesign2023FallDict::LOGIN_2_SEPARATOR),
                    $whenNotBig3(BaseFieldsDict::BALANCE),
                    $field(Redesign2023FallDict::CURRENCY_AND_BALANCE),
                    $field(Redesign2023FallDict::TRACK_EXPIRATION),
                    $whenNotUnited(BaseFieldsDict::EXPIRATION_DATE),
                    $field(Redesign2023FallDict::BALANCE_SEPARATOR),
                    $field(BaseFieldsDict::CUSTOM_ELITE_LEVEL),
                    $field(Redesign2023FallDict::CUSTOM_ELITE_LEVEL_SEPARATOR),
                    $field(BaseFieldsDict::LOGIN_3),
                    $field(BaseFieldsDict::PASS),
                    $field(BaseFieldsDict::NOTICE_PWNED),
                    $field(BaseFieldsDict::SAVE_PASSWORD),
                    $field(Redesign2023FallDict::PASS_SEPARATOR),
                    $whenBig3(BaseFieldsDict::BALANCE),
                    $whenUnited(BaseFieldsDict::STATUS),
                    $whenUnited(BaseFieldsDict::EXPIRATION_DATE),
                    $field(BaseFieldsDict::LOGIN_URL),
                    $field(Redesign2023FallDict::LOGIN_URL_SEPARATOR),
                    $field(BaseFieldsDict::GOAL),
                    $field(Redesign2023FallDict::GOAL_SEPARATOR),
                    $field(BaseFieldsDict::COMMENT),
                    $field(Redesign2023FallDict::COMMENT_SEPARATOR),
                    $field(MobileFieldsDict::DISABLE_CLIENT_PASSWORD_ACCESS),
                    $field(BaseFieldsDict::DISABLED),
                    $field(MobileFieldsDict::BALANCE_WATCH),
                    $field(MobileFieldsDict::POINTS_SOURCE),
                    $field(MobileFieldsDict::TRANSFER_PROVIDER_CURRENCY),
                    $field(MobileFieldsDict::TRANSFER_FROM_PROVIDER),
                    $field(MobileFieldsDict::EXPECTED_POINTS),
                    $field(MobileFieldsDict::TRANSFER_REQUEST_DATE),
                    UnmatchedAnchor::getInstance(),
                    $field('big3info_new'),
                    $field(BaseFieldsDict::NOT_RELATED),
                ] :
                [
                    $regex('/^message\d+$/'),
                    $field(MobileFieldsDict::CARD_IMAGES),
                    $field(MobileFieldsDict::BARCODE),
                    $whenOauth(BaseFieldsDict::LOGIN_2),
                    $field(MobileFieldsDict::TOP_SEPARATOR),
                    $field(MobileFieldsDict::TOP_DESC),
                    $whenOauthFormExists($oauthFormName),
                    $field(MobileFieldsDict::MIDDLE_SEPARATOR),
                    $field(MobileFieldsDict::MIDDLE_DESC),
                    $field(MobileFieldsDict::OWNER),
                    $field(BaseFieldsDict::KIND),
                    $field(BaseFieldsDict::PROGRAM_NAME),
                    $field(BaseFieldsDict::LOGIN_URL),
                    $field(BaseFieldsDict::LOGIN),
                    $whenNotOauth(BaseFieldsDict::LOGIN_2),
                    $whenNotBig3(BaseFieldsDict::BALANCE),
                    $field(BaseFieldsDict::CUSTOM_ELITE_LEVEL),
                    $field(BaseFieldsDict::CURRENCY),
                    $field(BaseFieldsDict::LOGIN_3),
                    $field(BaseFieldsDict::PASS),
                    $field(BaseFieldsDict::NOTICE_PWNED),
                    $field(BaseFieldsDict::SAVE_PASSWORD),
                    $whenBig3(BaseFieldsDict::BALANCE),
                    $field(BaseFieldsDict::COMMENT),
                    $field(MobileFieldsDict::DONT_TRACK_EXPIRATION),
                    $field(BaseFieldsDict::GOAL),
                    $whenUnited(BaseFieldsDict::STATUS),
                    $field(BaseFieldsDict::EXPIRATION_DATE),
                    $field(MobileFieldsDict::DISABLE_CLIENT_PASSWORD_ACCESS),
                    $field(BaseFieldsDict::DISABLED),
                    $field(MobileFieldsDict::BALANCE_WATCH),
                    $field(MobileFieldsDict::POINTS_SOURCE),
                    $field(MobileFieldsDict::TRANSFER_PROVIDER_CURRENCY),
                    $field(MobileFieldsDict::TRANSFER_FROM_PROVIDER),
                    $field(MobileFieldsDict::EXPECTED_POINTS),
                    $field(MobileFieldsDict::TRANSFER_REQUEST_DATE),
                    UnmatchedAnchor::getInstance(),
                    $field(MobileFieldsDict::BOTTOM_SEPARATOR),
                    $field(BaseFieldsDict::NOT_RELATED),
                ]
        ));
    }

    private function modifyRedesign2023Fall(FormBuilderInterface $builder, Account $account): void
    {
        if (!$account->getProviderid()) {
            $this->addToggler($builder);
        }

        $this->replaceSavePasswordWithCheckbox($builder);
        $this->addAccountHeader($builder, $account);
    }

    private function addToggler(FormBuilderInterface $builder): void
    {
        /** @var ToggleButton[] $toggleButtonDescList */
        $toggleButtonDescList = [
            new ToggleButton(
                'add-login',
                $this->translator->trans(/** @Desc("Add another login field") */ 'add-another-login-field-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove another login field") */ 'remove-another-login-field-toggle', [], 'mobile'),
                [BaseFieldsDict::LOGIN_2],
                Redesign2023FallDict::LOGIN_2_SEPARATOR,
            ),
            new ToggleButton(
                'add-password',
                $this->translator->trans(/** @Desc("Add password") */ 'add-password-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove password") */ 'remove-password-toggle', [], 'mobile'),
                [
                    BaseFieldsDict::PASS,
                    BaseFieldsDict::SAVE_PASSWORD,
                ],
                Redesign2023FallDict::PASS_SEPARATOR,
                [BaseFieldsDict::PASS]
            ),
            new ToggleButton(
                'add-balance',
                $this->translator->trans(/** @Desc("Add account balance") */ 'add-account-balance-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove account balance") */ 'remove-account-balance-toggle', [], 'mobile'),
                [
                    Redesign2023FallDict::CURRENCY_AND_BALANCE,
                    BaseFieldsDict::EXPIRATION_DATE,
                    Redesign2023FallDict::TRACK_EXPIRATION,
                ],
                Redesign2023FallDict::BALANCE_SEPARATOR,
                [
                    Redesign2023FallDict::CURRENCY_AND_BALANCE . '.' . BaseFieldsDict::BALANCE,
                    BaseFieldsDict::EXPIRATION_DATE,
                ],
                true,
            ),
            new ToggleButton(
                'add-elite',
                $this->translator->trans(/** @Desc("Add elite level") */ 'add-elite-level-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove elite level") */ 'remove-elite-level-toggle', [], 'mobile'),
                [BaseFieldsDict::CUSTOM_ELITE_LEVEL],
                Redesign2023FallDict::CUSTOM_ELITE_LEVEL_SEPARATOR,
            ),
            new ToggleButton(
                'add-goal',
                $this->translator->trans(/** @Desc("Add goal") */ 'add-goal-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove goal") */ 'remove-goal-toggle', [], 'mobile'),
                [BaseFieldsDict::GOAL],
                Redesign2023FallDict::GOAL_SEPARATOR,
            ),
            new ToggleButton(
                'add-url',
                $this->translator->trans(/** @Desc("Add URL") */ 'add-url', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove URL") */ 'remove-url', [], 'mobile'),
                [BaseFieldsDict::LOGIN_URL],
                Redesign2023FallDict::LOGIN_URL_SEPARATOR,
            ),
            new ToggleButton(
                'add-comment',
                $this->translator->trans(/** @Desc("Add comment") */ 'add-comment-toggle', [], 'mobile'),
                $this->translator->trans(/** @Desc("Remove comment") */ 'remove-comment-toggle', [], 'mobile'),
                [BaseFieldsDict::COMMENT],
                Redesign2023FallDict::COMMENT_SEPARATOR,
            ),
        ];
        $this->loyaltyHelper->addToggler($builder, $toggleButtonDescList);
    }

    private function replaceSavePasswordWithCheckbox(FormBuilderInterface $builder): void
    {
        if ($builder->has(BaseFieldsDict::SAVE_PASSWORD)) {
            $fieldBuilder = $builder->get(BaseFieldsDict::SAVE_PASSWORD);
            $oldOptions = $fieldBuilder->getOptions();
            $fieldBuilder->remove(BaseFieldsDict::SAVE_PASSWORD);
            $builder->add(
                $builder
                    ->create(
                        BaseFieldsDict::SAVE_PASSWORD,
                        SwitcherType::class,
                        [
                            'label' => $this->translator->trans(
                                /** @Desc("%image%%tag_open%Save encrypted password with AwardWallet%tag_close%") */ 'save-password-encrypted',
                                \array_merge(
                                    ['%image%' => '<div class="icon-aw"><img src="assets/aw.png"/></div>'],
                                    $this->apiVersioning->supports(MobileVersions::REACT_NATIVE_RENDER_HTML_6_TRANSIENT_RENDER_ENGINE) ?
                                        ['%tag_open%' => '<span>', '%tag_close%' => '</span>'] :
                                        ['%tag_open%' => ' ',     '%tag_close%' => '']
                                ),
                                'mobile'
                            ),
                            'attr' => [
                                'notice' => $this->translator->trans('account.notice.you.may.store.locally', [], 'mobile'),
                                'class' => 'simple',
                            ],
                            'required' => $oldOptions['required'],
                        ]
                    )
                    ->addModelTransformer(new CallbackTransformer(
                        fn ($value) => $value === \SAVE_PASSWORD_DATABASE,
                        fn ($value) => $value ? \SAVE_PASSWORD_DATABASE : \SAVE_PASSWORD_LOCALLY
                    ))
            );
        }
    }

    private function addCurrencyAndBalance(FormBuilderInterface $builder): void
    {
        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FORM_REDESIGN_2023_FALL)) {
            $currencyAndBalanceFieldBuilder = $builder->create(
                Redesign2023FallDict::CURRENCY_AND_BALANCE,
                CurrencyAndBalanceType::class
            );
            $this->loyaltyHelper->addCurrencyRedesign2023Fall($currencyAndBalanceFieldBuilder);
            $currencyAndBalanceFieldBuilder->add(
                (fn () =>
                    $currencyAndBalanceFieldBuilder->create(BaseFieldsDict::BALANCE, TextType::class, [
                        'required' => false,
                        'label' => 'account.balance',
                        'property_path' => BaseFieldsDict::BALANCE,
                    ])
                    ->addViewTransformer(new class() extends BalanceTransformer {
                        public function reverseTransform($value)
                        {
                            $value = parent::reverseTransform($value);

                            if (empty($value)) {
                                return null;
                            }

                            return $value;
                        }
                    })
                )()
            );
            $builder->add($currencyAndBalanceFieldBuilder);
            $builder->addModelTransformer(
                new CallbackTransformer(
                    function (AccountModelMobile $value) {
                        $currencyAndBalance = $value->getCurrencyandbalance();
                        $currencyAndBalance->setCurrency($value->getCurrency());
                        $currencyAndBalance->setBalance($value->getBalance());

                        return $value;
                    },
                    function (AccountModelMobile $value) {
                        $currencyAndBalance = $value->getCurrencyandbalance();
                        $value->setCurrency($currencyAndBalance->getCurrency());
                        $value->setBalance($currencyAndBalance->getBalance());

                        return $value;
                    }
                ),
                true
            );
        } else {
            $builder
                ->add(BaseFieldsDict::CURRENCY, EntityType::class, [
                    'class' => Currency::class,
                    'label' => $this->translator->trans('itineraries.currency', [], 'trips'),
                    'choice_label' => fn (Currency $currency) => ucfirst($this->translator->trans('name.' . $currency->getCurrencyid(), [], 'currency')),
                    'choices' => $this->currencyHelper->getChoices(),
                    'required' => false,
                    'choice_translation_domain' => false,
                    'placeholder' => 'please-select',
                ])
                ->add(BaseFieldsDict::BALANCE, TextType::class, [
                    'required' => true,
                    'label' => 'account.balance',
                ]);
        }
    }

    private function addAccountHeader(FormBuilderInterface $builder, Account $account)
    {
        $headerBlock = new AccountHeader();
        $provider = $account->getProviderid();

        if ($provider) {
            $headerBlock->providerCode = $provider->getCode();
            $headerBlock->providerName = $provider->getDisplayname();
            $headerBlock->providerKind = $provider->getKind();
        } else {
            $headerBlock->providerKind = (null !== $account->getId()) ?
                $account->getKind() :
                'custom';
            $headerBlock->providerName = (null !== $account->getId()) ?
                $account->getProgramname() :
                $this->translator->trans('custom.account.list.title', [], 'mobile');
            $headerBlock->hint = $this->translator->trans(/** @Desc("Not tracked by AwardWallet") */ 'custom.account.not-tracked', [], 'mobile');
            $potentialProvider = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['displayname' => $account->getProgramname()]);

            if ($potentialProvider) {
                $headerBlock->providerCode = $potentialProvider->getCode();
            }
        }

        $builder->add(
            Redesign2023FallDict::ACCOUNT_HEADER,
            BlockContainerType::class,
            ['blockData' => $headerBlock]
        );
    }
}
