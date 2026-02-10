<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Account;

use AwardWallet\CardImageParser\CardImageParserLoader;
use AwardWallet\CardImageParser\DOMConverter\DOMConverter;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\CardImageContainerInterface;
use AwardWallet\MainBundle\Entity\CustomLoyaltyProperty;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\ProviderpropertyRepository;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Worker\AsyncProcess\CardImageProviderDetectionTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CardImageMobile implements EventSubscriberInterface
{
    private const ASYNC_TASK_ATTRIBUTE_NAME = 'card_image_mobile_task';

    /**
     * @var CardImageManager
     */
    private $cardImageManager;
    /**
     * @var ObjectRepository
     */
    private $cardImageRep;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var Process
     */
    private $asyncTaskExecutor;
    /**
     * @var CardImageParserLoader
     */
    private $cardImageParserLoader;
    /**
     * @var DOMConverter
     */
    private $domConverter;
    /**
     * @var ProviderpropertyRepository
     */
    private $providerpropertyRep;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AccountMobile constructor.
     */
    public function __construct(
        CardImageManager $cardImageManager,
        ObjectRepository $cardImageRep,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Process $asyncTaskExecutor,
        CardImageParserLoader $cardImageParserLoader,
        DOMConverter $domConverter,
        ProviderpropertyRepository $providerpropertyRep,
        LoggerInterface $logger
    ) {
        $this->cardImageManager = $cardImageManager;
        $this->cardImageRep = $cardImageRep;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->asyncTaskExecutor = $asyncTaskExecutor;
        $this->cardImageParserLoader = $cardImageParserLoader;
        $this->domConverter = $domConverter;
        $this->providerpropertyRep = $providerpropertyRep;
        $this->logger = $logger;
    }

    public function onValid(HandlerEvent $event)
    {
        $this->saveCardImage($event);
        $this->saveBarCode($event);
    }

    public function onCommit(HandlerEvent $event)
    {
        if ($task = $event->getContext()->attributes->get(self::ASYNC_TASK_ATTRIBUTE_NAME)) {
            $this->asyncTaskExecutor->execute($task);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.mobile.account.on_valid' => ['onValid', -1],
            'form.mobile.providercoupon.on_valid' => ['onValid', -1],
            'form.mobile.account.on_commit' => ['onCommit', -1],
            'form.generic.document.on_valid' => ['onValid', -1],
        ];
    }

    protected function saveCardImage(HandlerEvent $event): void
    {
        $form = $event->getForm();

        if (!$form->has('cardImages')) {
            return;
        }

        /** @var LoyaltyProgramInterface $loyaltyProgram */
        $loyaltyProgram = ($form->getNormData() instanceof CardImageContainerInterface) ?
            $form->getNormData() :
            $form->getNormData()->getEntity();

        if ($loyaltyProgram instanceof Account) {
            $kind = $loyaltyProgram->getKind();
        } elseif ($loyaltyProgram instanceof Providercoupon) {
            $kind = $loyaltyProgram->getKind();

            if (
                (PROVIDER_KIND_DOCUMENT === $kind)
                && !\in_array(
                    $loyaltyProgram->getTypeid(),
                    [
                        Providercoupon::TYPE_TRUSTED_TRAVELER,
                        Providercoupon::TYPE_INSURANCE_CARD,
                        Providercoupon::TYPE_DRIVERS_LICENSE,
                    ])
            ) {
                return;
            }
        }

        if (
            isset($kind)
            && (PROVIDER_KIND_CREDITCARD === $kind)
        ) {
            return;
        }

        $imageData = $form->get('cardImages')->getData();

        $cardImagesInfoGrabber = function (iterable $cardImages): array {
            return it($cardImages)
                ->reindexByPropertyPath('kind')
                ->propertyPath('cardimageid')
                ->toArrayWithKeys();
        };
        $formInfo = [
            'program_id' => $loyaltyProgram->getId(),
            'program_type' => \get_class($loyaltyProgram),
            'card_images_before' => $cardImagesInfoGrabber($loyaltyProgram->getCardImages()),
        ];

        foreach (['Front' => CardImage::KIND_FRONT, 'Back' => CardImage::KIND_BACK] as $side => $kind) {
            if (!isset($imageData[$side]['CardImageId'])) {
                continue;
            }

            /** @var CardImage $cardImage */
            if (
                ($cardImage = $this->cardImageRep->find($imageData[$side]['CardImageId']))
                && $this->authorizationChecker->isGranted('VIEW', $cardImage)
            ) {
                $cardImages = $loyaltyProgram->getCardImages();
                $cardImages[$kind] = $cardImage;

                $cardImage
                    ->setContainer($loyaltyProgram)
                    ->setKind($kind);

                $event->getContext()->attributes->set(self::ASYNC_TASK_ATTRIBUTE_NAME, new CardImageProviderDetectionTask($cardImage));
            }

            if (
                ($loyaltyProgram instanceof Account)
                && DateTimeUtils::areEqualByTimestamp($loyaltyProgram->getCreationdate(), $loyaltyProgram->getUpdatedate())
                && DateTimeUtils::areEqualByTimestamp($loyaltyProgram->getUpdatedate(), $loyaltyProgram->getModifydate())
            ) {
                $this->saveAccountProperties($loyaltyProgram);
            }
        }

        $formInfo['card_images_after'] = $cardImagesInfoGrabber($loyaltyProgram->getCardImages());
        $this->logger->warning('card_images_form_log', ['form_debug' => $formInfo]);
        $this->entityManager->persist($loyaltyProgram);
        $this->entityManager->flush();
    }

    protected function saveBarCode(HandlerEvent $event): void
    {
        $form = $event->getForm();

        if (!$form->has('barcode')) {
            return;
        }

        /** @var LoyaltyProgramInterface $loyaltyProgram */
        $loyaltyProgram = ($form->getNormData() instanceof CardImageContainerInterface) ?
            $form->getNormData() :
            $form->getNormData()->getEntity();

        $barcodeData = $form->get('barcode')->getData();

        if (isset(
            $barcodeData['text'],
            $barcodeData['format']
        )) {
            $properties = $loyaltyProgram->getCustomLoyaltyProperties();

            foreach ([
                'format' => 'BarCodeType',
                'text' => 'BarCodeData',
            ] as $formKey => $dbKey) {
                if (!isset($properties[$dbKey])) {
                    $loyaltyProgram->addCustomLoyaltyProperty($property = new CustomLoyaltyProperty(
                        $dbKey,
                        $barcodeData[$formKey]
                    ));
                    $property->setContainer($loyaltyProgram);
                } else {
                    $property = $properties[$dbKey]->setValue($barcodeData[$formKey]);
                }

                $this->entityManager->persist($property);
            }
        } else {
            foreach (['BarCodeType', 'BarCodeData'] as $propertyName) {
                $loyaltyProgram->removeCustomLoyaltyPropertyByName($propertyName);
            }
        }

        $this->entityManager->persist($loyaltyProgram);
        $this->entityManager->flush();
    }

    protected function saveAccountProperties(Account $account)
    {
        if (
            !($provider = $account->getProviderid())
            || !$provider->getCanParseCardImages()
            || !($cardImages = $account->getCardImages())
        ) {
            return;
        }

        $parsedProperties = [];
        $formProperties = [];

        foreach ($cardImages as $cardImage) {
            if (
                $cardImage->hasGoogleVisionResposne()
                && ($computerVisionResult = $cardImage->getComputerVisionResult())
                && isset($computerVisionResult['aw_parsing']['result'])
            ) {
                $parsedProperties = $computerVisionResult['aw_parsing']['result'];

                if ($computerVisionResult['aw_parsing']['form_properties']) {
                    $formProperties = $computerVisionResult['aw_parsing']['form_properties'];
                }
            }
        }

        if (!$parsedProperties) {
            return;
        }

        $parsedProperties = array_filter($parsedProperties, 'is_scalar');
        // filter out properties supported by form only
        $filteredProperties = [];
        $providerProperties = array_keys($this->providerpropertyRep->getProviderProperties($provider));
        $providerPropertiesNormalized = array_map('strtolower', $providerProperties);
        $formPropertiesNormalized = array_map('strtolower', $formProperties);

        foreach ($parsedProperties as $parsedPropertyName => $parsedPropertyValue) {
            if (
                !in_array(strtolower($parsedPropertyName), $providerPropertiesNormalized)
                && in_array(strtolower($parsedPropertyName), $formPropertiesNormalized)
            ) {
                unset($parsedProperties[$parsedPropertyName]);
            }
        }

        if (!$parsedProperties) {
            return;
        }

        $accountInfo = $account->getAccountInfo();
        \AccountAuditor::getNextEliteLevel($accountInfo, $parsedProperties);
        SaveAccountProperties(
            $account->getAccountid(),
            $parsedProperties,
            $accountInfo,
            null
        );
    }
}
