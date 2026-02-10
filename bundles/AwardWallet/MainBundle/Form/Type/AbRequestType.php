<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Transformer\Entity2IdTransformer;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AbRequestType extends AbstractType implements TranslationContainerInterface
{
    /** @var EntityManager */
    private $em;

    /** @var LocalizeService */
    private $localizer;

    public function __construct(EntityManager $em, LocalizeService $localizer)
    {
        $this->em = $em;
        $this->localizer = $localizer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildPassengersPage($builder, $options);
        $this->buildSegmentsPage($builder, $options);
        $this->buildMilesPage($builder, $options);
        $this->buildContactPage($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbRequest',
            'translation_domain' => 'booking',
            'csrf_protection' => false,
            'user' => null,
            'max_passengers' => 10,
            'by_booker' => false,
        ]);
        $resolver->setRequired(['booker']);
        $resolver->setDefined(['user', 'max_passengers']);
        $resolver->setAllowedTypes('user', ['null', 'AwardWallet\\MainBundle\\Entity\\Usr']);
        $resolver->setAllowedTypes('booker', 'AwardWallet\\MainBundle\\Entity\\Usr');
    }

    public function getBlockPrefix()
    {
        return 'booking_request';
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            new Message('booking.request.add.form.prior.question', 'booking'),
            new Message('booking.request.add.form.prior.title', 'booking'),
            new Message('booking.request.add.form.contact-search', 'booking'),
            new Message('booking.request.add.form.passengers.cabin', 'booking'),
            new Message('booking.request.add.form.passengers.errors.not-exists', 'booking'),
            new Message('booking.request.add.form.passengers.errors.duplication', 'booking'),
            new Message('booking.request.add.form.contact.errors.not-exist', 'booking'),
            new Message('booking.request.add.form.miles.errors.not-exist', 'booking'),
            new Message('booking.request.add.form.miles.errors.duplication', 'booking'),
            (new Message('booking.email.notice', 'booking'))->setDesc('Please add any additional email contacts (separated with a comma) that will need to be copied during the booking process.'),
        ];
    }

    public static function validate(
        $request,
        ExecutionContextInterface $context,
        EntityManager $em,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        AuthorizationChecker $authorizationChecker)
    {
        if (!$request instanceof AbRequest) {
            return;
        }

        self::validateCabinClass($request, $context, $authorizationChecker);
        self::validatePassengersPage($request, $context, $em, $authorizationChecker);
        self::validateMilesPage($request, $context, $accountListManager, $optionsFactory, $authorizationChecker);
        self::validateContactPage($request, $context, $em, $authorizationChecker);
        self::validateEmails($request, $context, $em, $authorizationChecker);
    }

    public static function validateCabinClass(AbRequest $request, ExecutionContextInterface $context, AuthorizationChecker $authorizationChecker)
    {
        if ($authorizationChecker->isGranted("BOOKER", $request)) {
            return;
        }

        if ($request->getBooker()->getBookerInfo()->getServeEconomyClass()) {
            if (
                !(
                    $request->getCabinEconomy()
                    || $request->getCabinBusiness()
                    || $request->getCabinFirst()
                    || $request->isCabinPremiumEconomy()
                )
            ) {
                $context->buildViolation('booking.request.add.form.passengers.cabin')
                    ->setTranslationDomain('booking')
                    ->atPath('CabinEconomy')
                    ->addViolation();
            }
        }
    }

    public static function validatePassengersPage(AbRequest $request, ExecutionContextInterface $context, EntityManager $em, AuthorizationChecker $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted("BOOKER", $request)) {
            return;
        }

        $uids = [];

        /** @var AbPassenger $passenger */
        foreach ($request->getPassengers() as $i => $passenger) {
            $ua = $passenger->getUseragent();

            if ($ua instanceof Useragent) {
                if (!self::validateUserAgent($ua, $request->getBooker(), $em)) {
                    $context->buildViolation('booking.request.add.form.passengers.errors.not-exists')
                        ->setTranslationDomain('booking')
                        ->atPath('Passengers[' . $i . '].Useragent')
                        ->addViolation();

                    continue;
                }
                $uid = $ua->getUseragentid();

                if (in_array($uid, $uids)) {
                    $context->buildViolation('booking.request.add.form.passengers.errors.duplication')
                        ->setTranslationDomain('booking')
                        ->atPath('Passengers[' . $i . '].Useragent')
                        ->addViolation();

                    continue;
                }
                $uids[] = $uid;
            }
        }
    }

    public static function validateMilesPage(AbRequest $request, ExecutionContextInterface $context, AccountListManager $accountListManager, OptionsFactory $optionsFactory, AuthorizationChecker $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted("BOOKER", $request) || !$request->getByBooker()) {
            return;
        }

        $listOptions = $optionsFactory->createDefaultOptions()
            ->set(Options::OPTION_USER, $request->getBooker())
            ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_NOLOAD)
            ->set(Options::OPTION_LOAD_SUBACCOUNTS, false)
            ->set(Options::OPTION_LOAD_PROPERTIES, false);
        $aids = [];

        /** @var AbAccountProgram $abaccount */
        foreach ($request->getAccounts() as $i => $abaccount) {
            if ($abaccount->getAccount()) {
                $aids[] = $abaccount->getAccount()->getAccountid();
            }
        }
        $accounts = [];

        if (sizeof($aids)) {
            $listOptions->set(OPtions::OPTION_FILTER, ' AND a.AccountID IN (' . implode(",", $aids) . ')');
            $accounts = $accountListManager->getAccountList($listOptions);
        }

        $aids = [];

        /** @var AbAccountProgram $abaccount */
        foreach ($request->getAccounts() as $i => $abaccount) {
            $aid = ($abaccount->getAccount()) ? $abaccount->getAccount()->getAccountid() : null;

            if (!$aid || !isset($accounts['a' . $aid])) {
                $context->buildViolation('booking.request.add.form.miles.errors.not-exist')
                    ->setTranslationDomain('booking')
                    ->atPath('Accounts[' . $i . ']')
                    ->addViolation();

                continue;
            }

            if (in_array($aid, $aids)) {
                $context->buildViolation('booking.request.add.form.miles.errors.duplication')
                    ->setTranslationDomain('booking')
                    ->atPath('Accounts[' . $i . ']')
                    ->addViolation();

                continue;
            }
            $aids[] = $aid;
        }
    }

    public static function validateContactPage(AbRequest $request, ExecutionContextInterface $context, EntityManager $em, AuthorizationChecker $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted("BOOKER", $request)) {
            return;
        }

        $booker = $request->getBooker();
        $user = $request->getUser();

        if (!$booker || !$user) {
            return;
        }

        if (!$booker->findUserAgent($user->getUserid())) {
            $context->buildViolation('booking.request.add.form.contact.errors.not-exist')
                ->setTranslationDomain('booking')
                ->atPath('User')
                ->addViolation();
        }
    }

    public static function validateEmails(AbRequest $request, ExecutionContextInterface $context, EntityManager $em, AuthorizationChecker $authorizationChecker)
    {
        $validator = $context->getValidator();
        $emailConstraint = new Email();

        $emails = $request->getContactEmails();

        foreach ($emails as $email) {
            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if (count($errors)) {
                /** @var ConstraintViolation $error */
                $error = $errors[0];
                $context->buildViolation($email . ' - ' . $error->getMessage())
                    ->atPath('ContactEmail')
                    ->addViolation();

                return;
            }
        }
    }

    protected function buildPassengersPage(FormBuilderInterface $builder, array $options)
    {
        $passengerRange = range(1, $options['max_passengers']);
        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $bookerInfo */
        $bookerInfo = $options['booker']->getBookerInfo();

        $builder->add('CabinFirst', CheckboxType::class, [
            'label' => 'booking.request.add.form.cabin.first',
            'required' => false,
            'validation_groups' => ['passenger'],
        ]);

        if ($bookerInfo->getServeEconomyClass()) {
            $builder->add('CabinEconomy', CheckboxType::class, [
                'label' => 'booking.request.add.form.cabin.economy',
                'required' => false,
                'validation_groups' => ['passenger'],
            ]);
        }

        if ($bookerInfo->isServePremiumEconomy()) {
            $builder->add('CabinPremiumEconomy', CheckboxType::class, [
                'label' => /** @Desc("Premium Economy Class") */ 'booking.premium_economy.class',
                'required' => false,
                'validation_groups' => ['passenger'],
            ]);
        }

        $builder->add('Passengers', CollectionType::class, [
            'entry_type' => AbPassengerType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'validation_groups' => ['passenger'],
            'by_reference' => false,
            'entry_options' => [
                'user' => $options['user'],
                'booker' => $options['booker'],
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($passengerRange, $bookerInfo) {
            /** @var \AwardWallet\MainBundle\Entity\AbRequest $abRequest */
            $abRequest = $event->getData();
            $form = $event->getForm();

            if ($abRequest) {
                $form->add('NumberPassengers', ChoiceType::class, [
                    'label' => 'booking.request.add.form.passengers',
                    'choices' => array_combine($passengerRange, $passengerRange),
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Valid(),
                        new Assert\Range([
                            'min' => min($passengerRange),
                            'max' => max($passengerRange),
                        ]),
                    ],
                    'validation_groups' => ['passenger'],
                    'data' => $abRequest->getPassengers()->count(),
                ]);

                $allowEconom = $bookerInfo->getServeEconomyClass();
                $attrs = [];

                if (!$abRequest->getAbRequestID()) {
                    $abRequest->setCabinBusiness(!$allowEconom);
                }
                $form->add('CabinBusiness', CheckboxType::class, [
                    'label' => 'booking.request.add.form.cabin.business',
                    'required' => false,
                    'validation_groups' => ['passenger'],
                    'attr' => $attrs,
                ]);
            }
        });
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($bookerInfo) {
            /** @var \AwardWallet\MainBundle\Entity\AbRequest $abRequest */
            $abRequest = $event->getData();

            if ($abRequest) {
                if (!$bookerInfo->getServeEconomyClass()) {
                    $abRequest->setCabinBusiness(true);
                }
            }
        });
    }

    protected function buildSegmentsPage(FormBuilderInterface $builder, array $options)
    {
        $builder->add('Segments', CollectionType::class, [
            'entry_type' => AbSegmentType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'validation_groups' => ['segments'],
            'by_reference' => false,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var \AwardWallet\MainBundle\Entity\AbRequest $abRequest */
            $abRequest = $event->getData();

            if ($abRequest) {
                $segments = $abRequest->getSegments();
                /** @var \AwardWallet\MainBundle\Entity\AbSegment $first */
                $first = null;

                /** @var \AwardWallet\MainBundle\Entity\AbSegment $segment */
                foreach ($segments as $segment) {
                    if (!isset($first)) {
                        $first = $segment;
                    } else {
                        if (in_array($first->getRoundTrip(), [AbSegment::ROUNDTRIP_ONEWAY, AbSegment::ROUNDTRIP_ROUND])) {
                            $abRequest->removeSegment($segment);
                        } else {
                            $segment->setRoundTrip(AbSegment::ROUNDTRIP_ONEWAY);
                            $segment->setReturnDateIdeal(null);
                            $segment->setReturnDateFrom(null);
                            $segment->setReturnDateTo(null);
                        }
                    }
                }

                if (isset($first)) {
                    if (in_array($first->getRoundTrip(), [AbSegment::ROUNDTRIP_ONEWAY, AbSegment::ROUNDTRIP_MULTIPLE])) {
                        $first->setReturnDateIdeal(null);
                        $first->setReturnDateFrom(null);
                        $first->setReturnDateTo(null);
                    }

                    if ($abRequest->getSegments()->count() == 1 && $first->getRoundTrip() == AbSegment::ROUNDTRIP_MULTIPLE) {
                        $first->setRoundTrip(AbSegment::ROUNDTRIP_ONEWAY);
                    }
                }
            }
        });
    }

    protected function buildMilesPage(FormBuilderInterface $builder, array $options)
    {
        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = $options['user'];
        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $bookerInfo */
        $bookerInfo = $options['booker']->getBookerInfo();

        if ($bookerInfo->getServePaymentCash()) {
            $builder->add('paymentCash', CheckboxType::class, [
                'label' => /** @Desc("I am OK with paying money instead of miles for the tickets.") */ 'booking.paying_money_instead_miles.iam',
                'required' => false,
            ]);
        }

        if ($user) {
            if ($user->isBusiness() && $user->isBooker() && $options['by_booker']) {
                $builder->add('Accounts', CollectionType::class, [
                    'entry_type' => AccountsSelectorExtendedType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'validation_groups' => ['programs'],
                    'entry_options' => [
                        /** @Ignore */
                        'label' => false,
                        'user' => $options['booker'],
                    ],
                    'by_reference' => true,
                    'error_bubbling' => false,
                ]);
                $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                    $data = $event->getData();

                    if (isset($data['Accounts']) && is_array($data['Accounts'])) {
                        foreach ($data['Accounts'] as $k => $acc) {
                            if (!isset($acc['AccountID']) || empty($acc['AccountID'])) {
                                unset($data['Accounts'][$k]);
                            }
                        }
                        $event->setData($data);
                    }
                });
            } else {
                $whiteListCodes = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getWhiteListProgramCodes();
                $filter = ' AND p.ProviderID IS NOT NULL';

                if (!empty($whiteListCodes)) {
                    $filter .= ' AND (p.Kind in (' . PROVIDER_KIND_AIRLINE . ', ' . PROVIDER_KIND_CREDITCARD . ')';
                    $filter .= ' OR p.Code in ("' . implode('", "', $whiteListCodes) . '"))';
                } else {
                    $filter .= ' AND p.Kind in (' . PROVIDER_KIND_AIRLINE . ', ' . PROVIDER_KIND_CREDITCARD . ')';
                }
                $builder->add('Accounts', AccountsSelectorType::class, [
                    'validation_groups' => ['programs'],
                    'filter' => $filter,
                    'read_only_list' => $user->isBusiness() && $user->isBooker() && !$options['by_booker'],
                ]);
            }
        }

        if (!$user || !$user->isBusiness() || !$user->isBooker() || !$options['by_booker']) {
            $builder->add('CustomPrograms', CollectionType::class, [
                'entry_type' => AbCustomProgramType::class,
                'property_path' => 'filteredCustomPrograms',
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'validation_groups' => ['passenger'],
                'entry_options' => [
                    /** @Ignore */
                    'label' => false,
                ],
                'by_reference' => false,
            ]);
        }
    }

    protected function buildContactPage(FormBuilderInterface $builder, array $options)
    {
        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = $options['user'];
        $booker = $options['booker'];
        $localizer = $this->localizer;
        $isBooker = $user && $user->isBusiness() && $user->isBooker();
        $em = $this->em;
        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $bookerInfo */
        $bookerInfo = $options['booker']->getBookerInfo();

        if (!$isBooker && $bookerInfo->getRequirePriorSearches()) {
            $builder->add('PriorSearchResults', TwoChoicesOrTextType::class, [
                'required' => true,
                'yes_label' => 'no',
                'no_label' => 'yes',
                'yes_value' => '-',
                'no_value' => 1,
                /** @Ignore */
                'label' => false,
                'text_widget' => TextareaType::class,
                'text_widget_options' => [
                    'allow_tags' => true,
                    'allow_quotes' => true,
                    'allow_urls' => true,
                    'attr' => [
                        'maxlength' => 4000,
                    ],
                ],
                'before_text' => 'booking.request.add.form.prior.question',
                'title_text' => 'booking.request.add.form.prior.title',
                'widget_options' => [
                    'default_text' => "Loyalty program: \rClass of service: \rOutbound # of stops/layover times: \rInbound # of stops/layover times: \r# miles to be redeemed per person: ",
                ],
            ]);
        }
        $builder->add('Notes', TextareaType::class, [
            /** @Ignore */
            'label' => false,
            'allow_quotes' => true,
            'allow_tags' => true,
            'allow_urls' => true,
            'required' => false,
            'attr' => [
                'maxlength' => 4000,
            ],
        ]);

        if ($bookerInfo->isAllowBusinessOrPersonalSelect()) {
            $builder->add('BusinessTravel', ChoiceType::class, [
                /** @Desc("This request is for") */
                'label' => 'booking.request.add.form.travel_type',
                'choices' => [
                    /** @Desc("Business Travel") */
                    'booking.request.add.form.travel_type.business' => true,

                    /** @Desc("Personal Travel") */
                    'booking.request.add.form.travel_type.personal' => false,
                ],
                'required' => true,
                'expanded' => true,
                'multiple' => false,
            ]);
        }

        if (!$user) {
            $builder
                ->add('User', UserType::class, [
                    'constraints' => [new Valid()],
                    'booking_form' => true,
                ])
                ->add('RememberMe', CheckboxType::class, ['mapped' => false, 'data' => true, 'required' => false, 'label' => 'booking.remember_me'])
                ->add('Terms', CheckboxType::class, ['mapped' => false, 'label' => 'booking.terms']);
            $builder->add('ContactEmail', EmailMultipleType::class, [
                'required' => false,
                'label' => /** @Desc("Additional Email(s)") */ 'booking.email.additional',
                'help' => 'booking.email.notice',
                'allow_urls' => true,
                'attr' => [
                    'maxlength' => 250,
                ],
            ]);
        } else {
            if ($isBooker && $options['by_booker']) {
                $builder->add('User', Select2HiddenType::class, [
                    'label' => 'booking.request.add.form.contact-search',
                    'required' => true,
                    'configs' => 'f:$.extend({}, AddForm.select2hiddenOptions, Info.userSelect2Options)',
                    'init-data' => function ($value) use ($booker, $localizer, $em) {
                        if (!empty($value)) {
                            /** @var Useragent $connection */
                            $connection = $booker->findUserAgent(intval($value));

                            if ($connection) {
                                $u = $connection->getClientid();

                                if ($u) {
                                    $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

                                    return $uaRep->getAgentInfo($booker->getUserid(), $connection->getUseragentid(), $localizer, " AND u.UserID IS NOT NULL");
                                }
                            }
                        }

                        return null;
                    },
                    'transformer' => new Entity2IdTransformer(
                        $em,
                        Usr::class,
                        'Userid'
                    ),
                ]);
            }
            $builder->add('ContactName', TextType::class, ['required' => true, 'label' => 'booking.fullname']);
            $builder->add('ContactEmail', EmailMultipleType::class, [
                'required' => true,
                'label' => /** @Desc("Email(s)") */ 'booking.emails',
                'help' =>  /** @Desc("Please add any additional email contacts (separated with a comma) that will need to be copied during the booking process.") */ 'booking.email.notice',
                'allow_urls' => true,
                'attr' => [
                    'maxlength' => 250,
                ],
            ]);
            $builder->add('ContactPhone', TextType::class, ['required' => true, 'label' => 'booking.phone']);
        }

        if ($isBooker) {
            $builder->add('SendMailUser', CheckboxType::class, [
                /** @Ignore */
                'label' => false,
                'required' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options, $isBooker) {
            $data = $event->getData();

            if ($options['user']) {
                if ($isBooker) {
                    return;
                }

                $emails = $data['ContactEmail'];
                $emails = array_map('trim', explode(',', $emails));

                if (isset($data['User']) && isset($data['User']['firstname']) && isset($data['User']['lastname'])) {
                    $data['ContactName'] = $data['User']['firstname'] . " " . $data['User']['lastname'];
                }

                if (isset($data['User']) && isset($data['User']['email']) && isset($data['User']['email']['Email'])) {
                    $mainEmail = strtolower(trim($data['User']['email']['Email']));
                    array_unshift($emails, $mainEmail);
                    $emails = array_unique($emails);
                    $data['ContactEmail'] = implode(', ', $emails);
                }

                if (isset($data['User']) && isset($data['User']['phone1'])) {
                    $data['ContactPhone'] = $data['User']['phone1'];
                }

                unset($data['User']);
                unset($data['RememberMe']);
                unset($data['Terms']);
                $event->setData($data);
            }
        });
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            if (!$options['user']) {
                $data = $event->getData();
                /** @var $data AbRequest */
                $contactName = $event->getForm()->get('User')->get('firstname')->getNormData() . ' ' . $event->getForm()->get('User')->get('lastname')->getNormData();
                $data->setContactName($contactName);
                $data->setContactPhone($event->getForm()->get('User')->get('phone1')->getNormData());

                $mainEmail = strtolower(trim($event->getForm()->get('User')->get('email')->getNormData()));
                $emails = $event->getForm()->get('ContactEmail')->getNormData();
                $emails = array_map('trim', explode(',', $emails));
                array_unshift($emails, $mainEmail);
                $emails = array_unique($emails);
                $data->setContactEmail(implode(', ', $emails));
            }
        });
    }

    private static function validateUserAgent(Useragent $ua, Usr $booker, EntityManager $em)
    {
        $query = "
            SELECT
                1
            FROM
                UserAgent ua
            WHERE
                ua.UserAgentID = ?
                AND ua.AgentID = ?
                AND ua.IsApproved = 1
        ";
        $row = $em->getConnection()->executeQuery(
            $query,
            [$ua->getUseragentid(), $booker->getUserid()],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        return $row !== false;
    }
}
