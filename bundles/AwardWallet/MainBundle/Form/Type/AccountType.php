<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\Builder;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Transformer\AccountFormTransformer;
use AwardWallet\MainBundle\Form\Transformer\BalanceTransformer;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Service\AccountFormHtmlProvider\DesktopHtmlRenderer;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\BalanceWatch\Query;
use AwardWallet\MainBundle\Service\BalanceWatch\TransferOptions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @property AccountRepository accountRepo
 */
class AccountType extends AbstractType
{
    private AwTokenStorageInterface $tokenStorage;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Builder $templateBuilder;

    private TranslatorInterface $translator;

    private EntityManagerInterface $em;

    private ProviderRepository $providerRepo;

    private AccountManager $accountManager;

    private DataTransformerInterface $dataTransformer;

    private RouterInterface $router;

    private LocalizeService $localizeService;

    private LoggerInterface $logger;

    private Query $bwQuery;

    private TransferOptions $bwTransferOptions;

    private CurrencyHelper $currencyHelper;

    private DesktopHtmlRenderer $desktopHtmlRenderer;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        Builder $templateBuilder,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        AccountManager $accountManager,
        AccountFormTransformer $dataTransformer,
        RouterInterface $router,
        Query $bwQuery,
        TransferOptions $bwTransferOptions,
        LocalizeService $localizeService,
        LoggerInterface $logger,
        CurrencyHelper $currencyHelper,
        DesktopHtmlRenderer $desktopHtmlRenderer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->templateBuilder = $templateBuilder;
        $this->translator = $translator;
        $this->em = $em;
        $this->providerRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->accountManager = $accountManager;
        $this->dataTransformer = $dataTransformer;
        $this->router = $router;
        $this->localizeService = $localizeService;
        $this->logger = $logger;
        $this->bwQuery = $bwQuery;
        $this->bwTransferOptions = $bwTransferOptions;
        $this->currencyHelper = $currencyHelper;
        $this->desktopHtmlRenderer = $desktopHtmlRenderer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Account $account */
        $account = $options['data'];
        $provider = $account->getProviderid();
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();
        $isDiscoveredAccount = (ACCOUNT_PENDING === $account->getState());
        $isExistingAccount = (bool) $account->getAccountid();
        $isCustomAccount = !$provider;
        $isBig3 = $provider && $provider->isBig3();

        if (!$isExistingAccount && empty($account->getUser())) {
            $account->setUser($user);
        }

        if ($isExistingAccount && $isBig3) {
            if ($block = $this->desktopHtmlRenderer->getBig3HeaderBlock($account)) {
                $builder->add('big3info', HtmlType::class, [
                    'html' => $block,
                    'mapped' => false,
                    /** @Ignore */
                    'label' => false,
                ]);
            }
        }

        if (!$this->authorizationChecker->isGranted('SITE_MOBILE_APP') && !$this->authorizationChecker->isGranted('SITE_DEV_MODE')) {
            $this->logger->info("adding captcha to account edit form");
            $builder->add('captcha', RecaptchaType::class, [
                'action' => 'edit_account',
            ]);
        }

        $builder->add('owner', OwnerMetaType::class, [
            'label' => 'account.label.owner',
            'translation_domain' => 'messages',
            'designation' => OwnerRepository::FOR_ACCOUNT_ASSIGNMENT,
            'required' => true,
        ]);

        if (!$isCustomAccount) {
            $template = $this->templateBuilder->getFormTemplate($user, $provider, $account->getAccountid());
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($template) {
                /** @var AccountModel $model */
                $model = $event->getForm()->getNormData();
                $model->setTemplate($template);
            });

            $builder->setAttribute('header', $template->title);
            $builder->setAttribute('javascripts', $template->javaScripts);

            foreach ($template->fields as $field) {
                if ($field['type'] === OauthType::class) {
                    // later we could move this IF to capitalcards/functions.php - return "capitalcards_oauth" as type instead of "oauth"
                    if ($provider->getCode() === "capitalcards") {
                        $builder->add('authInfo', CapitalcardsOauthType::class, [
                            'provider' => $provider,
                            'autologin_notice' => isset($template->fields["Login"]),
                        ]);
                    } else {
                        $builder->add('authInfo', OauthType::class, [
                            'provider' => $provider,
                            'autologin_notice' => isset($template->fields["Login"]),
                        ]);
                    }
                } else {
                    $builder->add($field['property'], $field['type'], $field['options']);
                }
            }

            if ($provider->isOauthProvider()) {
                $builder->add('separator', SeparatorType::class, ['mapped' => false]);
            }
        } else {
            $this->addCustomProgramFields($builder, $options);
        }

        if (($isExistingAccount || $isCustomAccount) && !$isDiscoveredAccount) {
            $this->addCommonAccountFields($builder, $account);
        }

        if ($isExistingAccount && $provider instanceof Provider && $provider->canAutologin() && !$isDiscoveredAccount) {
            $offAutologinOptions = [
                'label' => $this->translator->trans(/** @Desc("Disable auto-login for this account") */
                    'account.autologin.disabled'
                ),
                'required' => false,
                'mapped' => !$account->isDisableClientPasswordAccess(),
                'attr' => [
                    'data-disabled' => ($account->isDisableClientPasswordAccess() ? 'true' : 'false'),
                    'data-checked' => 'false',
                ],
            ];
            !$account->isDisableClientPasswordAccess() ?: $offAutologinOptions['attr']['checked'] = 'checked';

            if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
                $offAutologinOptions['mapped'] = false;
                $offAutologinOptions['attr']['disabled'] = true;
            }
            $builder->add('disableclientpasswordaccess', CheckboxType::class, $offAutologinOptions);
        }

        if (
            $isExistingAccount && !$isCustomAccount
            && ($provider->getState() !== PROVIDER_RETAIL)
            && !$isDiscoveredAccount
        ) {
            $this->addDisabledFields($builder, $account);
        }

        if ($builder->has('balance')) {
            $builder->get('balance')->addViewTransformer(new BalanceTransformer());

            if (!$isExistingAccount && $isCustomAccount && !$isDiscoveredAccount) {
                $this->addExpirationField($builder, $account);
            }
        }

        if (!$isDiscoveredAccount) {
            $this->addHideSubaccountsFields($builder, $account, $options);
            $this->accountBalanceWatchFields($builder, $account);
        }

        if ($builder->has('notrelated')) {
            if (!$isExistingAccount && $isBig3) {
                if ($block = $this->desktopHtmlRenderer->getBig3FooterBlock($account)) {
                    $builder->add('big3info', HtmlType::class, [
                        'html' => $block,
                        'mapped' => false,
                        /** @Ignore */
                        'label' => false,
                    ]);
                }
            }

            $field = $builder->get('notrelated');
            $builder->remove('notrelated');
            $builder->add($field);
        }

        if (!$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $builder->add('useragents', SharingOptionsType::class, ['is_add_form' => !$isExistingAccount]);
        }

        if ($isExistingAccount && !empty($_COOKIE['pass' . $account->getId()])) {
            setcookie('pass' . $account->getId(), null, time() - 86400, '/');
        }

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix()
    {
        return 'account';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'provider' => null,
            'data_class' => AccountModel::class,
        ]);
    }

    protected function getCurrencyName(Account $account)
    {
        if (!empty($account->getProviderid())) {
            $currency = $account->getProviderid()->getCurrency();
        } else {
            $currency = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Currency::class)->findOneByName('Points');
        }

        $currencyName = $this->translator->trans(/** @Ignore */
            $curKey = 'name.' . $currency->getCurrencyid(),
            [],
            'currency'
        );

        if ($currencyName == $curKey) {
            return $currency->getName();
        }

        return $currencyName;
    }

    private function addCustomProgramFields(FormBuilderInterface $builder, array $options)
    {
        /** @var Account $account */
        $account = $options['data'];
        $builder->setAttribute('header', $this->translator->trans('track.type.manually.title'));
        $builder
            ->add('kind', ChoiceType::class, [
                'label' => 'coupon.type',
                'choices' => array_filter(array_flip(Provider::getKinds()), function ($kind) {
                    return $kind !== PROVIDER_KIND_DOCUMENT;
                }),
                'required' => true,
                'placeholder' => /** @Desc("Please select") */
                    'please-select',
            ])
            ->add('programname', TextType::class, [
                'required' => true,
                'label' => 'account.program',
                'allow_quotes' => true,
                'allow_urls' => true,
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'attr' => [
                    'class' => 'cp-autocomplete',
                ],
            ])
            ->add('loginurl', TextType::class, [
                'required' => false,
                /** @Ignore */
                'label' => 'URL',
                'allow_urls' => true,
            ])
            ->add(
                'login',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'account.label',
                    'allow_urls' => true,
                    'constraints' => [
                        new Constraints\NotBlank(),
                    ],
                ]
            )
            ->add(
                'login2',
                TextType::class,
                [
                    'required' => false,
                    /** @Desc("Second Login") */
                    'label' => 'second-login',
                    'allow_urls' => true,
                    'attr' => [
                        'notice' => $this->translator->trans(/** @Desc("Typically your last name, when applicable") */
                            "second-login.notice"
                        ),
                    ],
                    'constraints' => [
                        new Constraints\Length(
                            [
                                'min' => 1,
                                'max' => 80,
                                'allowEmptyString' => true,
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'balance',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'account.balance',
                    'constraints' => [
                        new Constraints\NotBlank(),
                    ],
                ]
            )
            ->add('customEliteLevel', TextType::class, [
                'required' => false,
                'label' => 'alliances.elite-level',
                'constraints' => [
                    new Constraints\Length([
                        'max' => 128,
                    ]),
                ],
            ])
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
            ]);
        $this->templateBuilder->addPasswordFields($builder, null, $account->getId());
    }

    private function addCommonAccountFields(FormBuilderInterface $builder, Account $account)
    {
        if (!empty($account->getPwnedTimes())
            && $account->getCheckedby()
            && $account->getSavepassword() !== SAVE_PASSWORD_LOCALLY
            && $this->authorizationChecker->isGranted('EDIT', $account)
            && ($account->getProviderid() && !StringUtils::isEmpty($account->getProviderid()->getPasswordcaption())) // Builder.php getPasswordFields()
        ) {
            $builder->add('NoticePwned', NoticeType::class, [
                'message' => $this->translator->trans('checked-hacked-passwords', [
                    '%bold_on%' => '<b>',
                    '%bold_off%' => '</b>',
                    '%count%' => $account->getPwnedTimes(),
                    '%count_formatted%' => $this->localizeService->formatNumber($account->getPwnedTimes()),
                ]),
            ]);
        }

        $builder->add('comment', TextareaType::class, [
            'label' => /** @Desc("Comment") */
                'account.label.comment',
            'required' => false,
            'allow_quotes' => true,
            'allow_tags' => ['<', '>'],
            'allow_urls' => true,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("optional, up to 10,000 characters") */
                    "account.comment.notice"
                ),
            ],
        ]);

        if (
            ($provider = $account->getProviderid())
            && (PROVIDER_RETAIL === $provider->getState())
        ) {
            return;
        }

        $this->addExpirationFields($builder, $account);
        $builder->add('goal', NumberType::class, [
            'label' => /** @Desc("Goal") */
                'account.label.goal',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("Number of %currency% you wish to have") */
                    "account.goal.notice",
                    ['%currency%' => $this->getCurrencyName($account)]
                ),
            ],
        ]);
        $builder->get('goal')->addViewTransformer(new BalanceTransformer(true));

        $builder->add('isArchived', CheckboxType::class, [
            'label' => $this->translator->trans(/** @Desc("Archive this account") */ 'account.label.is-archived'),
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("Archived accounts are removed from the Active Accounts tab and moved to the ""Archived Accounts"" tab. The background updating of such accounts will be a lot less frequent.") */ 'account.label.is-archived.notice'),
            ],
        ]);
    }

    private function addDisabledFields(FormBuilderInterface $builder, Account $account)
    {
        $builder->add('disable_background_updating', CheckboxType::class, [
            'label' => /** @Desc("Disable background updating") */
                'account.label.disable_background_updating',
            'required' => false,
        ]);

        $builder->add('disabled', CheckboxType::class, [
            'label' => /** @Desc("Disabled. Don't update this account") */
                'account.label.disabled',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("If you have this checkbox checked we will not be attempting to retrieve account information from the provider's website.") */
                    "account.label.disabled.notice"
                ),
            ],
        ]);
    }

    private function addExpirationField(FormBuilderInterface $builder, Account $account)
    {
        $disabled = false;

        if ($account->gotExpirationFromSite()) {
            $notice = $this->translator->trans(/** @Desc("This field is updated by AwardWallet Plus automatically") */
                "account.expiration.notice-auto"
            );

            if (!(empty($account->getExpirationdate()) && !empty($account->getBalance()) && $account->getExpirationdate() < time())) {
                $disabled = true;
            }
        } else {
            $notice = $this->translator->trans(/** @Desc("Optionally you can specify when these %currency% are going to expire. AwardWallet will not retrieve and update this information for you automatically.") */
                "account.expiration.notice-manual",
                ['%currency%' => $this->getCurrencyName($account)]
            );
        }

        if ($builder->has('expirationdate')) {
            $field = $builder->get('expirationdate');
            $builder->remove('expirationdate');
        } else {
            $attr = [
                'notice' => $notice,
                // 'readonly' => 'readonly'
            ];
            !$disabled ?: $attr['data-readonly'] = 'readonly';
            $field = $builder->create('expirationdate', DatePickerType::class, [
                'label' => /** @Desc("Expiration") */
                    'account.label.expiration',
                'required' => false,
                'disabled' => $disabled,
                'input' => 'datetime',
                'attr' => $attr,
                'datepicker_options' => [
                    'defaultDate' => '+1y',
                    'yearRange' => '-10:+50',
                ],
            ]);
        }

        $builder->add($field);
    }

    private function addExpirationFields(FormBuilderInterface $builder, Account $account)
    {
        $this->addExpirationField($builder, $account);
        $currencyName = $this->getCurrencyName($account);
        $builder->add('donttrackexpiration', CheckboxType::class, [
            'label' => $this->translator->trans('award.account.list.currency-dont-expire', ['%currency%' => $currencyName]),
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("I don't want AwardWallet to try to track expiration for this program, I know that my %points% don't expire.") */
                    "currency-dont-expire.notice",
                    ['%points%' => $currencyName]
                ),
            ],
        ]);
    }

    private function addHideSubaccountsFields(FormBuilderInterface $builder, Account $account)
    {
        if ($account->getSubaccounts()) {
            $builder->add('hidesubaccount', EntityType::class, [
                'label' => $this->translator->trans(/** @Desc("Hide Sub Accounts") */
                    'account.subacc.label-subaccounts'
                ),
                'required' => false,
                'mapped' => false,
                'class' => Subaccount::class,
                'choice_label' => 'CreditCardFormattedDisplayName',
                'property_path' => '[id]',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (EntityRepository $er) use ($account) {
                    return $er->createQueryBuilder('su')
                        ->where('su.accountid = ' . ($account->getId() ?: -1))
                        ->orderBy('su.displayname', 'ASC');
                },
                'choice_attr' => function ($subacc, $key, $index) {
                    $attr = [];
                    !$subacc->getIsHidden() ?: $attr['checked'] = 'checked';

                    if (null !== ($expirationDate = $subacc->getExpirationdate()) && 0 < (int) $expirationDate->diff(date_create())->format('%R%a')) {
                        $attr['data-date'] = 'expired';
                    }

                    return $attr;
                },
                'attr' => [
                    'class' => 'multiple-checkboxes',
                    'rowClass' => 'row-entity-group',
                ],
            ]);
        }
    }

    private function accountBalanceWatchFields(FormBuilderInterface $builder, Account $account): void
    {
        if (!$account->isAllowBalanceWatch()) {
            return;
        }

        $user = $this->tokenStorage->getBusinessUser();
        $balanceWatch = $this->bwQuery->getAccountBalanceWatch($account);

        $balanceWatchField = [
            'label' => $this->translator->trans('account.balancewatch.force-label'),
            'required' => false,
            'data' => (null === $account->getBalanceWatchStartDate() ? false : true),
            'attr' => [
                'notice' => $this->translator->trans('account.balancewatch.force-notice', [
                    '%link_faq_on%' => '<a href="' . $this->router->generate('aw_faq_index') . '#74" target="_blank">',
                    '%link_faq_off%' => '</a>',
                    '%link_notify_on%' => '<a href="' . $this->router->generate('aw_profile_notifications') . '" target="_blank">',
                    '%link_notify_off%' => '</a>',
                ]),
            ],
        ];

        $builder->add('IsBusiness', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => ($user->isBusiness() ? 'true' : 'false'),
        ]);
        $builder->add('IsBalanceWatchDisabled', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => ($account->isBalanceWatchDisabled() ? 'true' : 'false'),
        ]);
        $builder->add('IsBalanceWatchAwPlus', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => (!$user->isBusiness() && !$user->isAwPlus() && $user->getBalanceWatchCredits() <= 0 ? 'false' : 'true'),
        ]);

        if ('false' === $builder->get('IsBalanceWatchAwPlus')->getData()) {
            $builder->add('URL_PayAwPlus', HiddenType::class, [
                'required' => false,
                'mapped' => false,
                'data' => $this->router->generate('aw_users_pay', ['forceId' => $account->getId()], $this->router::ABSOLUTE_URL),
            ]);
        }
        $builder->add('IsBalanceWatchCredits', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => !$user->isBusiness() ? $user->getBalanceWatchCredits() : ($user->getBusinessInfo()->getBalance() < BalanceWatchCredit::PRICE ? 0 : 1),
        ]);
        $builder->add('URL_PayCredit', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => ($user->isBusiness()
                ? $this->router->generate('aw_business_pay')
                : $this->router->generate('aw_users_pay_balancewatchcredit', ['forceId' => $account->getId()], $this->router::ABSOLUTE_URL)),
        ]);
        $builder->add('IsBalanceWatchAccountError', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => $account->getErrorcode(),
        ]);
        $builder->add('IsBalanceWatchAccountCanCheck', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => (
                empty($account->getProviderid()->getCancheck())
                || false === $account->getProviderid()->getCancheckbalance()
                || !in_array($account->getProviderid()->getState(), BalanceWatchManager::ALLOW_PROVIDER_STATE)
            ) ? 'false' : 'true',
        ]);
        $builder->add('IsBalanceWatchLocalPasswordExclude', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => in_array($account->getProviderid()->getProviderid(), BalanceWatchManager::EXCLUDED_PROVIDER_LOCAL_PASSWORD) ? 'true' : 'false',
        ]);

        $builder->add('BalanceWatch', CheckboxType::class, $balanceWatchField);

        if ($account->isBalanceWatchDisabled() /* || $account->isDisabled() || SAVE_PASSWORD_LOCALLY == $account->getSavepassword() */) {
            $builder->get('BalanceWatch')->setDisabled(true);
        }

        $commonOptions = [];
        $commonAttr = ['rowClass' => 'row-form-bw'];

        if (null !== $balanceWatch && null !== $account->getBalanceWatchStartDate()) {
            $commonOptions = ['disabled' => true];
            $attrTransfer['rowClass'] = $commonAttr['rowClass'] . (
                in_array($balanceWatch->getPointsSource(), [BalanceWatch::POINTS_SOURCE_PURCHASE, BalanceWatch::POINTS_SOURCE_OTHER], true)
                    ? ' hidden'
                    : ''
            );

            if ($builder->has('disabled')) {
                $builder->get('disabled')->setDisabled(true);
            }
        } else {
            $attrTransfer['rowClass'] = ($commonAttr['rowClass'] .= ' hidden');
        }

        $builder->add('PointsSource', ChoiceType::class, [
            'label' => $this->translator->trans('account.balancewatch.source-points', ['%currency%' => $this->getCurrencyName($account)]),
            'required' => true,
            'choices' => array_flip([
                '' => $this->translator->trans('account.option.please.select'),
                BalanceWatch::POINTS_SOURCE_TRANSFER => $this->translator->trans('account.balancewatch.source-points.transfer'),
                BalanceWatch::POINTS_SOURCE_PURCHASE => $this->translator->trans('account.balancewatch.source-points.purchase'),
                BalanceWatch::POINTS_SOURCE_OTHER => $this->translator->trans('account.balancewatch.source-points.other'),
            ]),
            'attr' => $commonAttr,
        ] + $commonOptions);

        $builder->add('TransferProviderCurrency', HiddenType::class, [
            'required' => false,
            'mapped' => false,
            'data' => (empty($balanceWatch) || null === $balanceWatch->getTransferFromProvider() ? $this->getCurrencyName($account) : $balanceWatch->getTransferFromProvider()->getCurrency()->getName()),
        ]);
        $builder->add('TransferFromProvider', TextType::class, [
            'required' => true,
            'label' => $this->translator->trans('account.balancewatch.transfer-from'),
            'allow_quotes' => true,
            'attr' => [
                'class' => 'cp-autocomplete',
                'data-request-fields' => 'currency,regions',
                'notice' => $this->translator->trans('account.balancewatch.transfer-from.notice'),
            ] + $attrTransfer,
        ] + $commonOptions);
        $builder->get('TransferFromProvider')->addModelTransformer(new CallbackTransformer(
            function ($provider) {
                if (empty($provider)) {
                    return null;
                }

                return $provider->getDisplayname();
            },
            function ($providerName) {
                $qb = $this->providerRepo->createQueryBuilder('p');

                return $qb
                    ->where('p.state in (' . implode(',', BalanceWatchManager::ALLOW_PROVIDER_STATE) . ')')
                    ->andWhere(
                        $qb->expr()->like('p.displayname', ':name')
                    )
                    ->setParameter('name', $providerName)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }
        ));
        $builder->add('SourceProgramRegion', ChoiceType::class, [
            'label' => $this->translator->trans(/** @Desc("Choose region") */ 'choose-region'),
            'required' => false,
            'mapped' => false,
            'choices' => [],
            'validation_groups' => false,
            'attr' => [
                'rowClass' => 'hidden row-form-bw-region',
            ],
        ] + $commonOptions);

        $builder->add('ExpectedPoints', NumberType::class, [
            'label' => ($expectedNumberCaption = $this->translator->trans('account.balancewatch.expected-number-miles', ['%currency%' => $this->getCurrencyName($account)])),
            'required' => false,
            'attr' => [
                'min' => 0,
                'max' => 1000000000,
                'notice' => $this->translator->trans('account.balancewatch.expected-number-miles-notice', ['%expectedNumberCaption%' => $expectedNumberCaption]),
            ] + $commonAttr,
        ] + $commonOptions);

        $builder->add('TransferRequestDate', ChoiceType::class, [
            'label' => $this->translator->trans('account.balancewatch.transfer-requested'),
            'required' => true,
            'choices' => array_flip($this->bwTransferOptions->get()),
            'attr' => $commonAttr,
        ] + $commonOptions);
        $builder->get('TransferRequestDate')->addModelTransformer(new CallbackTransformer(
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

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($account) {
            $data = $event->getData();

            if (!array_key_exists('BalanceWatch', $data) && !empty($data['ExpectedPoints'])) {
                unset($data['ExpectedPoints']);
                $event->setData($data);
            }

            if (!empty($data['SourceProgramRegion'])) {
                $transferFromProvider = $this->providerRepo->findOneBy(['displayname' => $data['TransferFromProvider']]);
                $login2Options = $this->accountManager->fetchLogin2Options($transferFromProvider, $account->getUser());

                $event->getForm()->add('SourceProgramRegion', ChoiceType::class, [
                    'choices' => array_flip($login2Options),
                    'data' => $data['SourceProgramRegion'],
                ]);
            }
        });
    }
}
