<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Controller\Auth\CapitalcardsController;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatableInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\ClientFormTypeInterface;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FormDehydrator
{
    //    const TRANS_ITEM = 1;
    //    const TRANS_LIST = 2;
    //    const TRANS_MAP = 4;
    //    const TRANS_MAP_KEYS = 8;
    //    const TRANS_MAP_VALUES = 16;
    /**
     * @var Desanitizer
     */
    private $desanitizer;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var ObjectRepository
     */
    private $cardImageRep;

    /**
     * @var ObjectRepository
     */
    private $currencyRep;
    private LoggerInterface $logger;

    private bool $isFormFieldsAsObject = false;

    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        ApiVersioningService $apiVersioning,
        AuthorizationCheckerInterface $authorizationChecker,
        ObjectRepository $cardImageRep,
        ObjectRepository $currencyRep,
        Desanitizer $desanitizer,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->router = $router;
        $this->apiVersioning = $apiVersioning;
        $this->authorizationChecker = $authorizationChecker;
        $this->cardImageRep = $cardImageRep;
        $this->desanitizer = $desanitizer;
        $this->currencyRep = $currencyRep;
        $this->logger = $logger;
    }

    /**
     * Converts form object to plain representation, convinient to further serialization etc...
     *
     * @param bool $flat
     * @return array
     */
    public function dehydrateForm(FormInterface $form, $flat = true)
    {
        $formErrors = $form->getErrors();
        $this->isFormFieldsAsObject = $this->apiVersioning->supports(MobileVersions::SPENT_ANALYSIS_FORMAT_V2);

        if (!empty($formErrors) && (is_array($formErrors) || $formErrors instanceof \Traversable)) {
            $errors = [];

            /** @var $formError FormError */
            foreach ($formErrors as $formError) {
                $errors[] = $formError->getMessage();
            }

            if (!empty($errors)) {
                $formData['errors'] = $errors;
            }
        }
        // csrf token
        $formConfig = $form->getConfig();

        if ($formConfig->getOption('csrf_protection')) {
            $formFactory = $formConfig->getFormFactory();
            $csrfProvider = $formConfig->getOption('csrf_token_manager');

            if ($formFactory instanceof FormFactoryInterface && $csrfProvider instanceof CsrfTokenManagerInterface) {
                $tokenId = $formConfig->getOption('csrf_token_id') ?: ($form->getName() ?: get_class($formConfig->getType()->getInnerType()));
                $token = $csrfProvider->getToken($tokenId);
                /** @var Form $csrfForm */
                $csrfForm = $formFactory->createNamed($formConfig->getOption('csrf_field_name'), HiddenType::class, $token->getValue(), [
                    'mapped' => true,
                    'auto_initialize' => false,
                ]);
                //                $formConfigBuilder = $csrfForm->getConfig();
                //                if ($formConfigBuilder instanceof FormConfigBuilderInterface) {
                //                    $formConfigBuilder->setAutoInitialize(false);
                //                }
                //                $form->add($csrfForm);
            }
        }
        $formView = $form->createView();
        $formData['children'] = $this->extractChildren($form, $formView, $flat);
        $formVars = $formView->vars;

        if ($formConfig->hasAttribute('submit_label')) {
            $label = $formConfig->getAttribute('submit_label');
            $formData['submitLabel'] = $label === false
                ? false
                : $this->translator->trans(/** @Ignore */ $label);
        }

        if ($formConfig->hasAttribute('free_version')) {
            $formData['freeVersion'] = $formConfig->getAttribute('free_version');
        }

        if (isset($formVars['jsFormInterface'], $formVars['jsProviderExtension'])) {
            $formData['jsFormInterface'] = $formVars['jsFormInterface'];
            $formData['jsProviderExtension'] = $formVars['jsProviderExtension'];
        }

        if (isset($csrfForm)) {
            if ($csrfFormData = $this->dehydrateChild($csrfForm, $csrfForm->createView())) {
                $formData['children'][] = $csrfFormData;
            }
        }

        return $formData;
    }

    protected function trans($id, $domain = null)
    {
        if ($id instanceof TranslatableInterface) {
            return $id->trans(/** @Ignore */ $this->translator);
        } else {
            return $this->translator->trans(/** @Ignore */ $id, [], $domain);
        }
    }

    private function extractChildren(FormInterface $parent, FormView $parentView, bool $flat = true): array
    {
        $result = [];

        foreach ($parentView->children as $formName => $view) {
            if (!$parent->has($formName)) {
                continue;
            }

            $form = $parent->get($formName);

            if (!$view) {
                $view = $form->createView($parentView);
            }

            $formData = $this->dehydrateChild($form, $view);

            if ($form->getConfig()->getCompound() && count($form) > 0) {
                $childrenData = $this->extractChildren($form, $view, $flat);

                if ($flat) {
                    $result = array_merge($result, $childrenData);

                    continue;
                } else {
                    $formData['children'] = $childrenData;
                }
            }

            if ($formData) {
                $result[] = $formData;
            }
        }

        $options = $parent->getConfig()->getOptions();

        if (
            isset($options['children_order'])
            && is_array($childrenOrder = $options['children_order'])
            && $childrenOrder
        ) {
            $childrenOrder = array_flip($childrenOrder);
            usort($result, function (array $child1, array $child2) use ($childrenOrder) {
                $name1 = $child1['name'];
                $name2 = $child2['name'];
                $childrenOrderMaxPos = count($childrenOrder) - 1;

                if (isset($childrenOrder[$name1], $childrenOrder[$name2])) {
                    return $childrenOrder[$name1] <=> $childrenOrder[$name2];
                } elseif (isset($childrenOrder[$name1])) {
                    return $childrenOrder[$name1] <=> $childrenOrderMaxPos;
                } else {
                    return 0;
                }
            });
        }

        return $result;
    }

    /**
     * @return array form data
     */
    private function dehydrateChild(FormInterface $form, FormView $formView)
    {
        /** @var FormConfigBuilderInterface $formConfig */
        $formConfig = $form->getConfig();
        /** @var ResolvedFormTypeInterface $formType */
        $formType = $formConfig->getType();
        $formVars = $formView->vars;
        $formOptions = $formConfig->getOptions();

        $translationDomain = $formVars['translation_domain'] ?? null;

        $formData = [];

        if (isset($formVars) && is_array($formVars) && !empty($formVars)) {
            $formData = array_intersect_key(
                $formVars,
                [
                    'attr' => null,
                    /** @Ignore */
                    'label' => null,
                    'full_name' => null,
                    'name' => null,
                    'disabled' => null,
                    'required' => null,
                    'trim' => null,
                    'value' => null,
                    /** @Ignore */
                    'placeholder' => null,
                ]
            );

            $formData = array_merge($formData,
                array_intersect_key(
                    $formOptions,
                    [
                        'mapped' => null,
                        'submitData' => null,
                    ]
                )
            );

            $formData['type'] = method_exists($formType, 'getBlockPrefix') ?
                $formType->getBlockPrefix() :
                $formType->getName();

            foreach (['label'] as $key) {
                if (
                    isset($formData[$key])
                    && (
                        is_scalar($formData[$key])
                        || ($formData[$key] instanceof TranslatableInterface)
                    )
                ) {
                    $formData[$key] = $this->trans(/** @Ignore */ $formData[$key], $translationDomain);
                }
            }

            if (isset($formData['attr']['notice'])) {
                $formData['attr']['notice'] = $this->desanitizer->tryDesanitizeChars($this->trans(/** @Ignore */ $formData['attr']['notice'], $translationDomain));
            }

            // fill child errors
            if (isset($formVars['errors']) && (is_array($formVars['errors']) || $formVars['errors'] instanceof \Traversable)) {
                $errors = [];

                /** @var $childErorr FormError */
                foreach ($formVars['errors'] as $childErorr) {
                    $errors[] = $childErorr->getMessage();
                }

                if (!empty($errors)) {
                    $formData['errors'] = $errors;
                }
            }

            $innerType = $formType->getInnerType();

            if ($innerType && $innerType instanceof ClientFormTypeInterface) {
                $formData['type'] = $innerType->getClientType();
            }

            switch ($formData['type']) {
                case 'user_pass':
                    $formData['type'] = $formType->getParent()->getBlockPrefix();
                    $formData['mapped'] = true;

                    break;

                case 'entity':
                case 'owner_choice':
                    $formData['type'] = 'choice';

                    // no break
                case 'choice':
                    $formData['multiple'] = $formVars['multiple'] ?? false;

                    if (isset($formVars['data'])) {
                        $formData['value'] = $formVars['data'];
                    }

                    if ($formData['multiple']) {
                        $formData['value'] = [];
                    }

                    if ($formConfig->hasOption('alerts') && ($alerts = $formConfig->getOption('alerts'))) {
                        foreach ($alerts as $key => $alert) {
                            $alert[$key] = $this->trans(/** @Ignore */ $alert, $translationDomain);
                        }
                        $formData['alerts'] = $alerts;
                    }

                    if ($formConfig->hasOption('formLinks') && ($formLinks = $formConfig->getOption('formLinks'))) {
                        $formData['formLinks'] = $formLinks;
                    }

                    if (
                        !$formVars['placeholder_in_choices']
                        && !is_object($formVars['placeholder'])
                        && !StringHandler::isEmpty($formVars['placeholder'])
                    ) {
                        array_unshift($formVars['choices'], new ChoiceView('', '', $this->trans(/** @Ignore */ $formVars['placeholder'], $translationDomain)));
                    }

                    $allChoicesIsScalar =
                        \is_array($formVars['data'] ?? null)
                        && it($formVars['data'])->all('\\is_scalar');

                    /** @var $choiceView ChoiceView */
                    foreach (array_merge(
                        $formVars['preferred_choices'] ?? [],
                        $formVars['choices'] ?? []
                    ) as $choiceView) {
                        if ($formData['multiple']) {
                            $choice = [
                                'name' => $choiceView->value,
                                /** @Ignore */
                                'label' => $this->trans(/** @Ignore */
                                    $choiceView->label, $translationDomain),
                            ];

                            if (isset($choiceView->attr['value'])) {
                                if ($choiceView->attr['value']) {
                                    $formData['value'][] = $choiceView->value;
                                }
                            } elseif (
                                (($formVars['is_selected'] ?? null) instanceof \Closure)
                                && isset($choiceView->data)
                                && \is_scalar($choiceView->data)
                                && $allChoicesIsScalar
                                && $formVars['is_selected']($choiceView->data, $formVars['data'])
                            ) {
                                $formData['value'][] = $choiceView->value;
                            }

                            if (isset($choiceView->attr) && is_array($choiceView->attr)) {
                                $choice = array_merge($choice, array_diff_key($choiceView->attr, array_flip(['value'])));
                            }
                        } else {
                            $choice = [
                                'value' => $choiceView->value,
                                /** @Ignore */
                                'label' => $this->trans(/** @Ignore */
                                    $choiceView->label, $translationDomain),
                            ];

                            if (
                                (
                                    is_scalar($formData['value']) && is_scalar($choiceView->value)
                                    && (string) $formData['value'] === (string) $choiceView->value
                                )
                                || (
                                    is_object($formData['value']) && is_object($choiceView->data)
                                    && $formData['value'] === $choiceView->data // test object for same object
                                )
                                || (
                                    is_object($choiceView->data)
                                    && isset($formOptions['choice_value']) && ($formOptions['choice_value'] instanceof \Closure)
                                    && $formVars['value'] === $formOptions['choice_value']($choiceView->data)
                                )
                            ) {
                                $formData['value'] = $choiceView->value;
                                $choice['selected'] = true;
                            }

                            if (
                                isset($formVars['attr']['disabledValue'])
                                && (
                                    is_scalar($formVars['attr']['disabledValue']) && is_scalar($choiceView->value)
                                    && (string) $formVars['attr']['disabledValue'] === (string) $choiceView->value
                                )
                            ) {
                                $formData['disabledChoice'] = [
                                    'value' => $choiceView->value,
                                    /** @Ignore */
                                    'label' => $this->trans(/** @Ignore */ $choiceView->label, $translationDomain),
                                ];
                            }
                        }

                        $choice['attr'] = $choiceView->attr;
                        $formData['choices'][] = $choice;
                    }

                    foreach ($formConfig->getOption('extraChoices') as $value => $label) {
                        $formData['choices'][] = [
                            'value' => $value,
                            'label' => $label,
                        ];
                    }

                    $formData['type'] = 'choice';

                    break;

                case 'html':
                    if (isset($formVars['html_text'])) {
                        $formData['value'] = $formVars['html_text'];
                    }

                    break;

                case 'flex_float':
                    $formData['type'] = $formType->getParent()->getBlockPrefix();

                    break;

                case 'integer':
                case 'number':
                    $formData['type'] = 'text';

                    break;

                case 'checkbox':
                case 'switcher':
                    $formData['value'] = $formVars['checked'];

                    break;

                case 'oauth':
                    $formData['programCode'] = $formVars['program_code'];

                    $platform = $this->apiVersioning->supports(MobileVersions::NATIVE_APP)
                        ?
                            (
                                $this->apiVersioning->supports(MobileVersions::CAPITALCARDS_AUTH_V2)
                                ? CapitalcardsController::PLATFORM_MOBILE_NATIVE_V2 : CapitalcardsController::PLATFORM_MOBILE_NATIVE
                            )
                        : CapitalcardsController::PLATFORM_MOBILE_WEB;

                    $formData['callbackUrl'] = $this->router->generate('aw_auth_' . $formVars['program_code'] . '_authorize', [
                        'requestId' => $formVars['request_id'],
                        'platform' => $platform,
                    ], Router::ABSOLUTE_URL
                    );

                    $formData['buttonText'] = $this->translator->trans('button.oauth.authenticate', ['%programName%' => $formVars['program_name']]);
                    $formData['statusText'] = $this->translator->trans('oauth.status');
                    $formData['authenticatedText'] = $this->translator->trans('oauth.status.authenticated');
                    $formData['notAuthenticatedText'] = $this->translator->trans('oauth.status.not-authenticated');
                    $formData['revokeButtonText'] = $this->translator->trans('button.oauth.revoke-authentication', ['%programName%' => $formVars['program_name']]);

                    $formData['requiredText'] = $formVars['attr']['requiredText'];

                    break;

                case 'capitalcards_oauth_mobile':
                    $formData['programCode'] = $formVars['program_code'];
                    $formData['authenticatedText'] = $this->translator->trans('oauth.status.authenticated');
                    $formData['notAuthenticatedText'] = $this->translator->trans('oauth.status.not-authenticated');
                    $formData['requiredText'] = $formVars['attr']['requiredText'];
                    $formData['statusText'] = $this->translator->trans('oauth.status');

                    $formData['miles_callbackUrl'] = $this->router->generate('aw_auth_' . $formVars['program_code'] . '_authorize', [
                        'requestId' => $formVars['request_id'],
                        'platform' => CapitalcardsController::PLATFORM_MOBILE_NATIVE_V2,
                    ], Router::ABSOLUTE_URL
                    ) . '?prefix=rewards';
                    $formData['miles_title'] = 'CAPITAL ONE MILES';
                    $formData['miles_desc'] = '(Provides access to your Capital One Miles reward balance)';
                    $formData['miles_buttonText'] = $this->translator->trans('button.oauth.authenticate', ['%programName%' => $formVars['program_name']]);
                    $formData['miles_revokeButtonText'] = 'Revoke Capital One miles access for AwardWallet';

                    $formData['transactions_callbackUrl'] = $this->router->generate('aw_auth_' . $formVars['program_code'] . '_authorize', [
                        'requestId' => $formVars['request_id'],
                        'platform' => CapitalcardsController::PLATFORM_MOBILE_NATIVE_V2,
                    ], Router::ABSOLUTE_URL
                    ) . '?prefix=tx';
                    $formData['transactions_title'] = 'CAPITAL ONE TRANSACTIONS';
                    $formData['transactions_desc'] = '(Provides access to your Capital One credit card transactions)';
                    $formData['transactions_buttonText'] = $this->translator->trans('button.oauth.authenticate', ['%programName%' => $formVars['program_name']]);
                    $formData['transactions_revokeButtonText'] = 'Revoke Capital One transactions access for AwardWallet';
                    $formData['transactions_confirm'] = $this->translator->trans('capital-one-analyze-tx-text');
                    $formData['transactions_confirm_title'] = $this->translator->trans('capital-one-analyze-tx-title');

                    break;

                case BlockContainerType::class:
                case 'block_container_type':
                    // unwrap
                    $formData = array_merge($formData, (array) $formVars['blockData']);

                    break;

                case 'text_completion':
                    $formData['completionLink'] = $formVars['completionLink'];
                    $formData['attachedAccounts'] = $formOptions['attachedAccounts'];
                    $formData['providerKind'] = $formOptions['providerKind'];
                    $formData['completionList'] = $formOptions['completionList'];

                    break;

                case 'card_images':
                    $imageData = $formData['value'];

                    foreach (['Front', 'Back'] as $side) {
                        if (!isset($imageData[$side], $imageData[$side]['CardImageID'])) {
                            continue;
                        }

                        if (
                            !($cardImage = $this->cardImageRep->find($imageData[$side]['CardImageID']))
                            || !$this->authorizationChecker->isGranted('VIEW', $cardImage)
                        ) {
                            $imageData[$side] = [
                                'FileName' => null,
                                'CardImageID' => null,
                            ];
                        }
                    }

                    $formData['value'] = $imageData;
                    $formData['Val'] = $imageData;
                    $formData['mapped'] = true;

                    break;

                case 'collection':
                    $formData['value'] = null;

                    break;

                case 'form':
                    $formData['value'] = null;

                    break;

                case 'notice':
                    $formData['value'] = $formVars['message'];

                    break;

                case 'sharing_timelines':
                    $formData['value'] = null;
                    $formData['type'] = 'choice';
                    $formData['multiple'] = true;

                    break;

                case 'sharing_options':
                    $formData['type'] = 'hidden';

                    break;

                case 'mailbox_linking':
                    $formData['mailboxes'] = $formVars['mailboxes'];
                    $formData['mailbox_title'] = $formVars['mailbox_title'];
                    $formData['add_mailbox_button'] = $formVars['add_mailbox_button'];
                    $formData['text'] = $formVars['text'];
                    $formData['html'] = $formVars['html'];
            }

            if (isset($formData['mapped'])) {
                $formData['mapped'] =
                    $formData['mapped']
                    || (isset($formData['required']) && $formData['required'])
                    || (isset($formData['submitData']) && $formData['submitData']);
            }
        }

        return $formData;
    }
}
