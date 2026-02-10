<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\DatePickerType;
use AwardWallet\MainBundle\Form\Type\FlexibleFloatType;
use AwardWallet\MainBundle\Form\Type\HtmlType;
use AwardWallet\MainBundle\Form\Type\MaskedPasswordType;
use AwardWallet\MainBundle\Form\Type\OauthType;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use AwardWallet\MobileBundle\Form\Type\PasswordMaskType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * this class will return customizations for account editing form.
 */
class Builder
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var GlobalVariables
     */
    protected $globals;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var UseragentRepository
     */
    protected $uaRepo;
    /**
     * @var UseragentRepository
     */
    protected $accountRepo;
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var string[]
     */
    private $excludedTypes = [];
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    /**
     * @var string
     */
    private $enginePath;
    /**
     * @var string
     */
    private $projectPath;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var ProviderTranslator
     */
    private $providerTranslator;

    private EmailHelper $emailHelper;

    public function __construct(
        TranslatorInterface $translator,
        GlobalVariables $globals,
        Connection $connection,
        UseragentRepository $uaRepo,
        AccountRepository $accountRepo,
        RequestStack $requestStack,
        AuthorizationChecker $authorizationChecker,
        ApiVersioningService $apiVersioning,
        ProviderTranslator $providerTranslator,
        EmailHelper $emailHelper,
        $enginePath,
        $projectPath
    ) {
        $this->translator = $translator;
        $this->globals = $globals;
        $this->connection = $connection;
        $this->uaRepo = $uaRepo;
        $this->accountRepo = $accountRepo;
        $this->requestStack = $requestStack;
        $this->apiVersioning = $apiVersioning;
        $this->enginePath = $enginePath;
        $this->authorizationChecker = $authorizationChecker;
        $this->projectPath = $projectPath;
        $this->providerTranslator = $providerTranslator;
        $this->emailHelper = $emailHelper;
    }

    /**
     * @param int $accountId
     * @return Template
     */
    public function getFormTemplate(Usr $user, Provider $provider, $accountId = null)
    {
        $postValues = [];
        $request = $this->requestStack->getCurrentRequest();

        if (!empty($request)) {
            $postValues = $request->request->all();
        }
        $result = new Template();
        $result->provider = $provider;
        $result->title = $this->providerTranslator->translateDisplayNameByEntity($provider);
        $mobile = $this->authorizationChecker->isGranted('SITE_MOBILE_AREA');
        $fields = $this->getProviderFields($user, $provider, $mobile);

        if (!empty($accountId)) {
            $result->account = $this->getAccountFields($accountId);
        }
        $checker = $this->globals->getAccountChecker($provider, true);
        $checker->setUserFields($user);

        if (!empty($result->account)) {
            $checker->AccountFields = array_merge($checker->AccountFields, $result->account);
            $checker->account = $this->accountRepo->find($accountId);
        }

        $fieldsBeforeTCheckerTuning = $fields;
        $checker->TuneFormFields($fields, array_merge($checker->AccountFields, $postValues));

        if (
            isset($fieldsBeforeTCheckerTuning['Login2'], $fields['Login2'])
            && ($fieldsBeforeTCheckerTuning['Login2'] === $fields['Login2'])
            && $provider->isLogin2AsCountry()
        ) {
            $this->addRegionField($fields, $provider);
        }

        $fields = $this->filterExcluded($user, $provider, $fields);

        // @see #11884
        if (!empty($fields['ExpirationDate'])) {
            $fields['ExpirationDate']['Other'] = [
                'datepicker_options' => [
                    'defaultDate' => '+1y',
                    'yearRange' => '-10:+50',
                ],
            ];
        }

        if (!isset($_GET['skipping'])) { // @TODO: replace $_GET and $postValues with Request
            if ($provider->isBig3()) {
                $result->messages = $this->emailHelper->getMessages(
                    $checker->AccountFields,
                    [
                        'Login' => $user->getLogin(),
                    ]
                );
            }
        }
        $result->checker = $checker;

        $params = $checker->TuneForm($result->account);

        if (!empty($params['Title'])) {
            $result->title = $params['Title'];
        }

        foreach ($fields as $name => $field) {
            if (in_array($name, ['Login', 'Login2', 'Login3'])
                && array_key_exists('Type', $field)
                && 'date' === $field['Type']) {
                unset($field['Size'], $field['MinSize']);

                if (array_key_exists('Value', $field)) {
                    // set from parser functions.php
                    if (!$field['Value'] instanceof \DateTimeInterface) {
                        $field['Value'] = empty($timeValue = strtotime($field['Value'])) ? new \DateTime() : new \DateTime('@' . $timeValue);
                    }
                } elseif (!empty($checker->AccountFields[$name]) && ('POST' !== $request->getMethod() || empty($field['Value']))) {
                    $field['Value'] = empty($timeValue = strtotime($checker->AccountFields[$name])) ? null : new \DateTime('@' . $timeValue);
                }

                if (empty($field['Value']) && !empty($checker->account)) {
                    $checker->account->setLogin3(null);
                }
            }

            $result->fields[$name] = $this->convertToSymfonyField($name, $field, $mobile, $accountId, $result->account);
        }

        $providerJsFormExtension = $this->enginePath . "/" . $provider->getCode() . "/form.js";

        if (\file_exists($providerJsFormExtension)) {
            $result->javaScripts['provider'] = \file_get_contents($providerJsFormExtension);
        }

        if (isset($checker->account) && $checker->account instanceof Account && $checker->account->isAllowBalanceWatch()) {
            $accountJsFormExtension = $this->projectPath . '/bundles/AwardWallet/MainBundle/Resources/js/Form/Account/balanceWatch.js';

            if (\file_exists($accountJsFormExtension)) {
                $result->javaScripts['balanceWatch'] = \file_get_contents($accountJsFormExtension);
            }
        }

        return $result;
    }

    public function setExcludedTypes($types)
    {
        $this->excludedTypes = $types;

        return $this;
    }

    /**
     * @return Template
     */
    public function getConfirmationFormTemplate(Provider $provider, Request $request, EntityManager $em, ?Useragent $client = null)
    {
        $checker = $this->globals->getAccountChecker($provider, false);

        $result = new Template();
        $result->title = $provider->getDisplayname();
        $fields = $checker->GetConfirmationFields();
        $result->autoSubmit = false;
        $result->checker = $checker;

        $mobile = $this->authorizationChecker->isGranted('SITE_MOBILE_AREA');

        if ($client) {
            if (!$client->isFamilyMember()) {
                $client = $client->getClientid();
            }

            foreach ($fields as $name => $field) {
                switch (strtolower($name)) {
                    case 'firstname':
                        $fields[$name]['Value'] = $client->getFirstname();

                        break;

                    case 'lastname':
                    case 'surname':
                        $fields[$name]['Value'] = $client->getLastname();

                        break;
                }
            }
        }

        foreach ($fields as $name => $field) {
            $result->fields[$name] = $this->convertToSymfonyField($name, $field, $mobile, false, $result->account);
        }

        if ($request->query->has('itKind') && $request->query->has('itId')) {
            $itKind = $request->query->get('itKind');
            $itId = $request->query->get('itId');

            if (isset(Itinerary::$table[$itKind]) && is_numeric($itId)) {
                $itinerary = $em->getRepository(Itinerary::getItineraryClass($itKind))->find($itId);

                if ($itinerary && $this->authorizationChecker->isGranted('UPDATE', $itinerary)) {
                    $itProvider = $itinerary->getProvider()->getProviderid();

                    if ($itProvider === $provider->getId()) {
                        $itFields = $itinerary->getConfFields();
                        $result->autoSubmit = true;
                        // TODO Need remove! Hardcoded replacement for confirmation fields. Case #14045
                        $replacementFields = ['RecordLocator', 'confirmationNumber', 'ReservationNumber', 'BookingReference', 'Number', 'ConfNumber', 'Locator', 'ConfirmationNo'];

                        foreach ($itFields as $name => $field) {
                            if (in_array($name, $replacementFields)) {
                                $name = 'ConfNo';
                            }

                            if (!isset($result->fields[$name])) {
                                continue;
                            }

                            $result->fields[$name]['options']['data'] = $field;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function addPasswordFields(FormBuilderInterface $builder, ?Provider $provider = null, ?int $accountId = null)
    {
        $mobile = $this->authorizationChecker->isGranted('SITE_MOBILE_AREA');
        $account = null;

        if (!empty($accountId)) {
            $account = $this->getAccountFields($accountId);
        }

        foreach ($this->getPasswordFields($provider) as $name => $field) {
            $result = $this->convertToSymfonyField($name, $field, $mobile, $accountId, $account);
            $builder->add($result['property'], $result['type'], $result['options']);
        }
    }

    protected function filterExcluded(Usr $user, Provider $provider, array $fields)
    {
        foreach ($fields as $name => $fieldData) {
            if (isset($fieldData['Type']) && in_array(strtolower($fieldData['Type']), $this->excludedTypes, true)) {
                unset($fields[$name]);
            }
        }

        return $fields;
    }

    /**
     * @return array
     */
    protected function getProviderFields(Usr $user, Provider $provider, $mobile)
    {
        $result = [];
        $isOauthProvider = $this->isOauthProvider($provider);
        $notePattern = '/^([^\(]+)\((.*)\)$/is';

        if (!StringUtils::isEmpty($provider->getLogincaption())) {
            // ## Login ###
            $result['Login'] = [
                /** @Desc("Login") */
                'Caption' => $this->translator->trans('account.label.login'),
                'Note' => $provider->getLogincaption(),
                'Type' => 'string',
                'Size' => $provider->getLoginmaxsize(),
                'MinSize' => $provider->getLoginminsize(),
                'Required' => $isOauthProvider ? false : $provider->isLoginRequired(),
                'Database' => true,
                'HTML' => true,
                'Other' => [
                    'attr' => ['autocapitalize' => 'off'],
                ],
            ];

            if (preg_match($notePattern, $result["Login"]["Note"], $matches)) {
                $result["Login"]["Caption"] = $matches[1];
                $result["Login"]["Note"] = $matches[2];
            } elseif (empty($provider->getLogin2caption())) {
                $result["Login"]["Caption"] = $result["Login"]["Note"];
                unset($result["Login"]["Note"]);
            }
        }

        // ## Login2 ###
        if ($provider->getLogin2caption() != '') {
            $result['Login2'] = [
                'Caption' => $provider->getLogin2caption(),
                'Type' => 'string',
                'HTML' => true,
                'Size' => $provider->getLogin2maxsize(),
                'MinSize' => $provider->getLogin2minsize(),
                'Required' => $isOauthProvider ? false : $provider->isLogin2Required(),
                'Database' => true,
            ];

            if (isset($result['Login']) && empty(preg_match($notePattern, $provider->getLogincaption(), $matches))) {
                $result["Login"]["Caption"] = $result["Login"]["Note"];
                unset($result["Login"]["Note"]);
            }

            if (preg_match($notePattern, $provider->getLogin2caption(), $matches)) {
                $result['Login2']['Caption'] = $matches[1];
                $result['Login2']['Note'] = $matches[2];
            }
        }

        // ## Login3 ###
        if (!empty($provider->getLogin3caption())) {
            $result['Login3'] = [
                'Caption' => $provider->getLogin3caption(),
                'Type' => 'string',
                'HTML' => true,
                'Size' => $provider->getLogin3Maxsize(),
                'Database' => true,
                'Required' => $provider->isLogin3Required(),
            ];

            if (preg_match($notePattern, $provider->getLogin3caption(), $matches)) {
                $result['Login3']['Caption'] = $matches[1];
                $result['Login3']['Note'] = $matches[2];
            }
        }
        // ## Password ###
        $result = array_merge($result, $this->getPasswordFields($provider));
        // ## Not related ###
        $notRelatedCaption = $isOauthProvider ?
            $this->translator->trans(/** @Desc("I understand that AwardWallet’s services are not offered by %providerName%. By continuing, I instruct %providerName% to share the information about my %providerName% rewards account(s) (including balance, currency, and available redemption opportunities) with AwardWallet.") */
                'account.notice.oauth.not.affiliated', [
                    '%providerName%' => $provider->getName(),
                ])
            :
            $this->translator->trans(/** @Desc("I understand that AwardWallet and its services are not affiliated with or related in any manner to %providerName% or any of the loyalty programs they offer, such as %programName%. I also hereby give AwardWallet permission to use my information, including information such as my loyalty account balance information, travel reservations, historical account transactions, as part of the AwardWallet service.") */
                'account.notice.aw.not.affiliated', ['%providerName%' => $provider->getName(), '%programName%' => $provider->getDisplayname() ?? $provider->getProgramname()]);

        $notRelatedError = $this->translator->trans(/** @Desc("You must confirm that you understand that AwardWallet is not affiliated with %displayName%") */ 'account.error.confirm.not.affiliation.understanding', ['%displayName%' => $provider->getDisplayname()]);

        $result['NotRelated'] = [
            'Caption' => $notRelatedCaption,
            'Type' => 'boolean',
            'Required' => true,
            'Database' => true,
            'ErrorMessage' => $notRelatedError,
            'Other' => [
                'label_attr' => [
                    'data-error-label' => false,
                ],
                'error_bubbling' => false,
            ],
        ];

        if ($mobile) {
            $result['NotRelated']['Other']['attr']['class'] = 'smallest shading';
        } // @TODO: remove after new mobile release

        return $result;
    }

    protected function getPasswordFields(?Provider $provider): array
    {
        if ($provider && StringUtils::isEmpty($provider->getPasswordcaption()) /* || !$provider->getPasswordrequired() */) {
            return [];
        }

        $result = [];
        $passCaption = $provider ? $provider->getPasswordcaption() : '';
        $note = StringUtils::isNotEmpty($passCaption) && $passCaption !== 'Password' ? $passCaption : '';
        $result['Pass'] = [
            /** @Desc("Password") */
            'Caption' => $this->translator->trans('account.label.password'),
            'Note' => $note,
            'HTML' => true,
            'Type' => 'string',
            'InputType' => 'password',
            'Size' => $provider ? \max($provider->getPasswordmaxsize(), $provider->getPasswordminsize()) : 80,
            'MinSize' => $provider ? $provider->getPasswordminsize() : 1,
            'Required' => !(!$provider || $this->isOauthProvider($provider)) && $provider->getPasswordrequired(),
            'Database' => true,
        ];

        if (!$provider || $provider->getCode() !== 'aa') {
            $result['SavePassword'] = [
                'Caption' => $this->translator->trans('account.label.save.password'),
                'Note' => $this->translator->trans('account.notice.you.may.store.locally'),
                'Type' => 'integer',
                'InputType' => 'select',
                'Options' => [
                    SAVE_PASSWORD_DATABASE => $this->translator->trans('account.choice.save.password.with.aw'),
                    SAVE_PASSWORD_LOCALLY => $this->translator->trans('account.choice.save.password.locally'),
                ],
                'Required' => isset($provider),
            ];

            if ($provider) {
                $result['SavePassword']['Other'] = [
                    'alerts' => [
                        (string) SAVE_PASSWORD_LOCALLY => $this->translator->trans(/** @Desc("You are choosing to not store your reward password in our encrypted and secure database. AwardWallet will no longer be able to automatically monitor this reward program. Also if you clear your cookies or simply use another computer to check your balances this password would have to be re-entered.") */ 'account.notice.store.locally'),
                        (string) SAVE_PASSWORD_DATABASE => $this->translator->trans(/** @Desc("You are about to delete your reward password from this computer and securely store it in an encrypted AwardWallet database. AwardWallet will now be able to monitor this reward program for changes and expirations.") */ 'account.notice.store.database'),
                    ],
                ];
            }
        }

        return $result;
    }

    protected function addRegionField(array &$fields, Provider $provider)
    {
        if (
            ($providerCountries = $provider->getCountries())
            && count($providerCountries)
            && (count($providerCountries = $providerCountries->matching(new Criteria(null, ['providerCountryId' => Criteria::ASC]))) > 1)
        ) {
            // use id to link account with country
            $fields['Login2']['Type'] = 'integer';
            $login2Options = ['' => 'Select a region'];

            foreach ($providerCountries as $providerCountry) {
                $country = $providerCountry->getCountryId();
                $login2Options[$country->getCountryid()] = $country->getName();
            }

            $fields['Login2']['Options'] = $login2Options;
        }
    }

    /**
     * @return array
     */
    protected function getAccountFields($accountId)
    {
        $result = $this->connection->executeQuery("
			SELECT
			*,
			ROUND(Balance, 2) AS Balance
			FROM Account
			WHERE
			AccountID = ?
			",
            [$accountId],
            [\PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($result === false) {
            $result = [];
        }

        return $result;
    }

    /**
     * @param array $field - field in TBaseForm format
     * @return array
     */
    protected function convertToSymfonyField($name, array $field, $mobile, $accountId, $accountInfo = null)
    {
        $isExistingAccount = (bool) $accountId;
        $isDiscovered = ACCOUNT_PENDING == ($accountInfo['State'] ?? null);
        $result = Template::getFieldTemplate($name, TextType::class, $field['Caption'] ?? null, $field['Note'] ?? null);

        // Type
        if (!isset($field['Type'])) {
            $field['Type'] = 'string';
        }

        switch (strtolower($field['Type'])) {
            case "string":
                $result['type'] = TextType::class;

                break;

            case "integer":
                $result['type'] = TextType::class;

                break;

            case "float":
                $result['type'] = FlexibleFloatType::class;

                break;

            case "boolean":
                $result['type'] = CheckboxType::class;

                break;

            case "hidden":
                $result['type'] = HiddenType::class;

                break;

            case "html":
                $result['type'] = HtmlType::class;
                $result['options']['html'] = ($field['HTML'] ?? '');

                break;

            case "date":
                $result['type'] = $mobile ? DateType::class : DatePickerType::class;
                $result['options']['datepicker_options'] = [
                    'yearRange' => '-5:+5',
                ];

                break;

            case "oauth":
                $result['type'] = OauthType::class;

                break;

            default:
                $result['type'] = TextType::class;

                break;
        }

        if (isset($field['Options']) && is_array($field['Options'])) {
            $field['InputType'] = 'select';
        }

        if (isset($field['InputType'])) {
            switch (strtolower($field['InputType'])) {
                case "password":
                    $result['type'] = $mobile ? PasswordMaskType::class : (!$isExistingAccount || $isDiscovered ? PasswordType::class : MaskedPasswordType::class); // @TODO: remove after old mobile
                    $result['options']['trim'] = true;
                    $result['options']['error_bubbling'] = false;

                    if ($result['type'] == PasswordType::class) {
                        $result['options']['allow_quotes'] = true;
                        $result['options']['allow_tags'] = true;
                    }

                    break;

                case "select":
                    $result['type'] = ChoiceType::class;

                    break;

                case "html":
                    $result['type'] = HtmlType::class;
                    $result['options']['html'] = ($field['HTML'] ?? '');

                    break;
            }
        }

        // Error message
        if (isset($field['ErrorMessage']) && $field['ErrorMessage'] != '') {
            $errorMessage = ['message' => $field['ErrorMessage']];
        } else {
            $errorMessage = null;
        }

        // Select
        if ($result['type'] == ChoiceType::class && isset($field['Options']) && is_array($field['Options'])) {
            if (isset($field['Options']['']) && $field['Options'][''] == '') {
                unset($field['Options']['']);
                $result['options'] = array_merge($result['options'], ['placeholder' => /** @Desc("Please select") */ 'account.option.please.select']);
            }
            $result['options'] = array_merge($result['options'], ['choices' => array_flip($field['Options'])]);
            $result['options']['constraints'][] = new Constraints\Choice(
                [
                    'choices' => array_keys($field['Options']),
                ]
            );
        }

        // Required
        if (!isset($field['Required'])) {
            $field['Required'] = false;
        }

        if ($field['Required']) {
            $result['options'] = array_merge($result['options'], ['required' => true]);

            if ($result['type'] == 'checkbox') {
                $result['options']['constraints'][] = new Constraints\IsTrue($errorMessage);
            } else {
                $result['options']['constraints'][] = new Constraints\NotBlank($errorMessage);
            }
        } else {
            $result['options'] = array_merge($result['options'], ['required' => false]);
        }

        // MinSize && Size
        if (isset($field['MinSize']) || isset($field['Size'])) {
            $min = (isset($field['MinSize'])) ? $field['MinSize'] : null;
            $max = (isset($field['Size'])) ? $field['Size'] : null;
            $result['options']['constraints'][] = new Constraints\Length(
                [
                    'min' => $min,
                    'max' => $max,
                    'allowEmptyString' => true,
                ]
            );
        }

        // RegExp
        if (isset($field['RegExp']) && isset($field['RegExpErrorMessage'])) {
            $result['options']['constraints'][] = new Constraints\Regex(
                [
                    'pattern' => $field['RegExp'],
                    'message' => $field['RegExpErrorMessage'],
                ]
            );
        }

        // Value
        if (isset($field['Value']) && $field['Value'] !== '') {
            $result['options'] = array_merge($result['options'], ['data' => $field['Value']]);
        }

        // Others
        if (isset($field['Other']) && is_array($field['Other'])) {
            $result['options'] = array_merge_recursive($result['options'], $field['Other']);
        }

        if (isset($field['Database']) && $field['Database'] === false) {
            $result['options']['mapped'] = false;
        }

        // set allow_urls option
        if (
            isset($result['property'], $result['type'])
            && in_array($result['type'], [TextType::class, PasswordType::class])
            && in_array($result['property'], [BaseFieldsDict::LOGIN, BaseFieldsDict::LOGIN_2, BaseFieldsDict::LOGIN_3, BaseFieldsDict::PASS, BaseFieldsDict::LOGIN_URL])
        ) {
            $result['options']['allow_urls'] = true;
        }

        return $result;
    }

    private function isOauthProvider(Provider $provider): bool
    {
        return $provider->isOauthProvider()
            && (!$this->authorizationChecker->isGranted('SITE_MOBILE_AREA') || $this->apiVersioning->supports(MobileVersions::OAUTH_PROVIDERS));
    }
}
