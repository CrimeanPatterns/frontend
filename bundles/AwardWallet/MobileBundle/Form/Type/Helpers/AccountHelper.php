<?php

namespace AwardWallet\MobileBundle\Form\Type\Helpers;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\UserOwnedInterface;
use AwardWallet\MainBundle\Form\Account\BaseFieldsDict;
use AwardWallet\MainBundle\Form\Type\Helpers\CurrencyHelper;
use AwardWallet\MainBundle\Form\Type\Mobile\Loyalty\BarcodeType;
use AwardWallet\MainBundle\Form\Type\Mobile\Loyalty\CardImagesType;
use AwardWallet\MainBundle\Form\Type\OwnerChoiceType;
use AwardWallet\MainBundle\Form\Type\SeparatorType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\Type\AccountType\MobileFieldsDict;
use AwardWallet\MobileBundle\Form\Type\AccountType\Redesign2023FallDict;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\FieldTogglerType;
use AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall\ToggleButton;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AccountHelper implements TranslationContainerInterface
{
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    private CurrencyHelper $currencyHelper;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        ApiVersioningService $apiVersioning,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CurrencyHelper $currencyHelper
    ) {
        $this->apiVersioning = $apiVersioning;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->currencyHelper = $currencyHelper;
    }

    public function addUserAgent(FormBuilderInterface $formBuilder, UserOwnedInterface $loyalty)
    {
        $supportNativeExtension = $this->apiVersioning->supports(MobileVersions::NATIVE_FORM_EXTENSION);
        $options = [];

        if (
            $this->apiVersioning->supports(MobileVersions::FORM_LINKED_CHOICES)
            && !$supportNativeExtension
        ) {
            $options['extraChoices']['new_family_member'] = $this->translator->trans('add.new.person');
            $options['formLinks']['new_family_member'] = [
                'formLink' => $this->urlGenerator->generate('aw_mobile_add_agent'),
                'formTitle' => $this->translator->trans('agents.title'),
            ];
        }

        if (!$supportNativeExtension) {
            $options['attr'] = [];
        }

        $isDocument =
            ($loyalty instanceof Providercoupon)
            && isset(Providercoupon::DOCUMENT_TYPES[$loyalty->getTypeid()]);
        $useragent = $formBuilder
            ->create(
                MobileFieldsDict::OWNER,
                OwnerChoiceType::class,
                array_merge(
                    [
                        'label' => $isDocument ?
                            'document.label.owner' :
                            'account.label.owner',
                        'translation_domain' => 'messages',
                        'designation' => OwnerRepository::FOR_ACCOUNT_ASSIGNMENT,
                        'required' => false,
                        'attr' => [
                            'class' => 'js-useragent-select',
                            'notice' => $isDocument ?
                                $this->translator->trans(/** @Desc("Please choose whose document this is.<br>You can <a href='#' class='js-add-new-person'>add a new person</a> if necessary.") */ 'account.notice.choose.document.program') :
                                $this->translator->trans('account.notice.choose.loyalty.program'),
                        ],
                    ],
                    $options
                )
            );

        $formBuilder->add($useragent);
    }

    public function addCardImagesAndBarCode(
        FormBuilderInterface $formBuilder,
        ?LoyaltyProgramInterface $cardImageContainer = null,
        ?Provider $provider = null
    ) {
        $this->addCardImages($formBuilder, $cardImageContainer, $provider);
        $this->addBarCode($formBuilder, $cardImageContainer, $provider);
    }

    public function addCardImages(
        FormBuilderInterface $formBuilder,
        ?LoyaltyProgramInterface $cardImageContainer = null,
        ?Provider $provider = null
    ): void {
        if (!$this->isSupportsCardRelatedFields($cardImageContainer, $provider)) {
            return;
        }

        $cardsData = [
            'Front' => [
                'Label' => $this->translator->trans('card-pictures.front.title'),
            ],
            'Back' => [
                'Label' => $this->translator->trans('card-pictures.back.title'),
            ],
        ];

        if ($cardImageContainer) {
            /** @var CardImage $cardImage */
            foreach ($cardImageContainer->getCardImages() as $cardImage) {
                $kind = $cardImage->getKind() === CardImage::KIND_FRONT ? 'Front' : 'Back';

                $cardsData[$kind] = array_merge(
                    $cardsData[$kind],
                    [
                        'Url' => $this->urlGenerator->generate('awm_card_image_download', ['cardImageId' => $cardImage->getCardImageId()], UrlGenerator::ABSOLUTE_URL),
                        'FileName' => $cardImage->getFileName(),
                        'CardImageId' => $cardImage->getCardImageId(),
                    ]
                );
            }
        }

        $formBuilder->add(
            MobileFieldsDict::CARD_IMAGES,
            CardImagesType::class,
            [
                'data' => $cardsData,
                'submitData' => true,
            ]
        );
    }

    public function addCurrencyRedesign2023Fall(FormBuilderInterface $builder): void
    {
        $builder
            ->add(BaseFieldsDict::CURRENCY, EntityType::class, [
                'class' => Currency::class,
                'label' => $this->translator->trans('itineraries.currency', [], 'trips'),
                'choice_label' => function (Currency $currency) {
                    switch ($currency->getCurrencyid()) {
                        case Currency::MILES_ID:
                        case Currency::POINTS_ID: return \ucfirst($this->translator->trans('name.' . $currency->getCurrencyid(), [], 'currency'));

                        case Currency::KILOMETERS_ID: return 'KMs';
                    }

                    return \mb_strtoupper($currency->getCode());
                },
                'choice_attr' => fn (Currency $currency) => [
                    'name' => \ucfirst($this->translator->trans('name.' . $currency->getCurrencyid(), [], 'currency')),
                ],
                'choices' => $this->currencyHelper->getChoices(),
                'required' => false,
                'choice_translation_domain' => false,
                'property_path' => BaseFieldsDict::CURRENCY,
            ]);
    }

    /**
     * @param ToggleButton[] $toggleButtonDescList
     */
    public function addToggler(FormBuilderInterface $builder, array $toggleButtonDescList): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $formEvent) use ($toggleButtonDescList) {
            /** @var Form $form */
            $form = $formEvent->getForm();
            $togglerButtonsList = [];

            $getFieldByPropertyPath = static function (FormInterface $form, PropertyPath $propertyPath): FormInterface {
                $field = $form;
                $propertyPathIter = $propertyPath->getIterator();

                foreach ($propertyPathIter as $property) {
                    if (!$propertyPathIter->isProperty()) {
                        throw new \LogicException('Property path should use only property access');
                    }

                    $field = $field->get($property);
                }

                return $field;
            };
            $isEmptyField = static function (FormInterface $form) use (&$isEmptyField): bool {
                if ($form->getConfig()->getCompound()) {
                    return it($form)->all($isEmptyField);
                } else {
                    $data = $form->getData();

                    return (null === $data) || ('' === $data);
                }
            };

            foreach ($toggleButtonDescList as $toggleButton) {
                $existingFields = it($toggleButton->getFields())
                    ->filter(fn (string $fieldName) => $form->has($fieldName))
                    ->toArray();

                $skip = $toggleButton->isAllFieldsRequiredForToggle() ?
                    \count($existingFields) < \count($toggleButton->getFields()) :
                    !\count($existingFields);

                if ($skip) {
                    continue;
                }

                $isAllFieldsEmpty =
                    it($toggleButton->getControlFields())
                    ->all(fn (PropertyPath $propertyPath) =>
                        $isEmptyField($getFieldByPropertyPath($form, $propertyPath))
                    );
                $togglerButtonsList[] = $toggleButton->toggle($existingFields, !$isAllFieldsEmpty);
            }

            if (\count($togglerButtonsList) <= 2) {
                return;
            }

            foreach ($togglerButtonsList as $togglerButton) {
                foreach ($togglerButton->getFields() as $fieldName) {
                    $this->updateAttr($form, $fieldName, function (array $attr) use ($togglerButton) {
                        $attr['visible'] = $togglerButton->isToggled();

                        return $attr;
                    });
                }

                $form->add(
                    $togglerButton->getSeparatorName(),
                    SeparatorType::class,
                    [
                        'mapped' => false,
                        'attr' => ['visible' => $togglerButton->isToggled()],
                    ],
                );
            }

            $form->add(Redesign2023FallDict::FIELD_TOGGLER, FieldTogglerType::class, [
                'data' =>
                    it($togglerButtonsList)
                    ->map(fn (ToggleButton $button) => [
                        'icon' => $button->getIcon(),
                        'label' => $button->getLabel(),
                        'toggledLabel' => $button->getToggledLabel(),
                        'fields' => \array_merge(
                            $button->getFields(),
                            [$button->getSeparatorName()]
                        ),
                        'toggled' => $button->isToggled(),
                    ])
                    ->toArray(),
            ]);
        });
    }

    public function addBarCode(
        FormBuilderInterface $formBuilder,
        ?LoyaltyProgramInterface $cardImageContainer = null,
        ?Provider $provider = null
    ): void {
        if (!$this->isSupportsCardRelatedFields($cardImageContainer, $provider)) {
            return;
        }

        $barcodeData = [];

        if ($cardImageContainer) {
            $properties = $cardImageContainer->getCustomLoyaltyProperties();

            if (isset($properties['BarCodeType'])) {
                $barcodeData['format'] = $properties['BarCodeType']->getValue();
            }

            if (isset($properties['BarCodeData'])) {
                $barcodeData['text'] = $properties['BarCodeData']->getValue();
            }
        }

        $isQRCode =
            ($cardImageContainer instanceof Providercoupon)
            && ($cardImageContainer->getTypeid() == Providercoupon::TYPE_VACCINE_CARD);
        $title = $isQRCode ?
            $this->translator->trans('qrcode.not-detect', [], 'mobile') :
            $this->translator->trans('barcode.not-detect', [], 'mobile');
        $hint = $isQRCode ?
            $this->translator->trans('scan.qrcode', [], 'mobile') :
            $this->translator->trans('scan.barcode', [], 'mobile');

        $formBuilder->add(
            MobileFieldsDict::BARCODE,
            BarcodeType::class,
            [
                'data' => $barcodeData,
                'submitData' => true,
                'attr' => [
                    'title' => $title,
                    'hint' => $hint,
                    'type' => $isQRCode ? MobileMapper::QRCODE_TYPE : MobileMapper::BARCODE_TYPE,
                ],
            ]
        );
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('document.label.owner', 'mobile'))
                ->setDesc('No QR code detected, if you have a QR code please scan it now.'),

            (new Message('scan.qrcode', 'mobile'))
                ->setDesc("Scan QR code"),

            (new Message('qrcode.not-detect', 'mobile'))
                ->setDesc('No QR code detected, if you have a QR code please scan it now.'),
        ];
    }

    /**
     * @param callable(array): array $updater receives attr array and returns updated options array
     */
    private function updateAttr(FormInterface $form, string $fieldName, callable $updater): void
    {
        $field = $form->get($fieldName);
        $options = $field->getConfig()->getOptions();
        $form->add($field, null, $updater($options));
    }

    private function isSupportsCardRelatedFields(?LoyaltyProgramInterface $cardImageContainer, ?Provider $provider): bool
    {
        return $this->apiVersioning->supportsAll([
            MobileVersions::CARD_IMAGES_ON_FORM,
            MobileVersions::NATIVE_APP,
        ])
            && (
                (
                    !isset($provider)
                    && !(
                        (
                            ($cardImageContainer instanceof Account)
                            && (PROVIDER_KIND_CREDITCARD == $cardImageContainer->getKind())
                        )
                        || (
                            ($cardImageContainer instanceof Providercoupon)
                            && (PROVIDER_KIND_CREDITCARD == $cardImageContainer->getKind())
                        )
                    )
                )
                || (
                    isset($provider)
                    && (PROVIDER_KIND_CREDITCARD != $provider->getKind())
                )
            );
    }
}
