<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\CardImageParser\Annotation\Detector;
use AwardWallet\CardImageParser\CardImageParserInterface;
use AwardWallet\CardImageParser\CardImageParserLoader;
use AwardWallet\CardImageParser\CreditCardDetection\CreditCardDetectorInterface;
use AwardWallet\CardImageParser\DOMConverter\DOMConverter;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\StackTraceUtils;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;

class ParserHandler
{
    public const CC_DETECTOR_VERSION_PREFIX = 1;

    /**
     * @var CardImageParserLoader
     */
    private $parserLoader;
    /**
     * @var DOMConverter
     */
    private $domConverter;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ImageCensor
     */
    private $imageCensor;
    /**
     * @var CardRecognitionResultFactory
     */
    private $cardRecognitionResultFactory;
    /**
     * @var DocParser
     */
    private $docParser;
    /**
     * @var bool
     */
    private $dryRun;

    public function __construct(
        CardImageParserLoader $parserLoader,
        DOMConverter $domConverter,
        EntityManager $entityManager,
        ImageCensor $imageCensor,
        CardRecognitionResultFactory $cardRecognitionResultFactory,
        LoggerInterface $logger,
        bool $dryRun = false
    ) {
        $this->parserLoader = $parserLoader;
        $this->domConverter = $domConverter;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->imageCensor = $imageCensor;
        $this->cardRecognitionResultFactory = $cardRecognitionResultFactory;
        $this->docParser = new DocParser();
        $this->docParser->setImports(['detector' => Detector::class]);
        $this->docParser->setIgnoreNotImportedAnnotations(true);
        $this->dryRun = $dryRun;
    }

    /**
     * @param CardImage[] $cardImagesByKind
     * @return CardImage[]
     */
    public function handle(Provider $provider, array $cardImagesByKind, ?FormInterface $form = null): array
    {
        $firstImage = current($cardImagesByKind);

        if (
            $provider->getCanDetectCreditCards()
            && ($parser = $this->parserLoader->loadParser($provider->getCode()))
            && ($parser instanceof CreditCardDetectorInterface)
        ) {
            [$isCCDetected, $cardImagesByKind] = $this->handleCreditCardDetection($parser, $cardImagesByKind, $provider->getCode());

            if ($isCCDetected) {
                return $cardImagesByKind;
            }
        }

        if (
            $firstImage->getAccount()
            && $provider->getCanParseCardImages()
            && ($parser = $this->parserLoader->loadParser($provider->getCode()))
            && ($parser instanceof CardImageParserInterface)
        ) {
            $this->handleCardImageParsing($parser, $cardImagesByKind, $form);
        }

        return $cardImagesByKind;
    }

    /**
     * @param CardImage[] $cardImagesByKind
     * @return array [bool, CardImage[]]
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleCreditCardDetection(CreditCardDetectorInterface $creditCardDetector, array $cardImagesByKind, string $providerCode): array
    {
        $isCCdetected = false;
        $currentDetectorVersion = $this->getCurrentDetectorVersion($providerCode);

        if ($cardRecognitionResult = $this->cardRecognitionResultFactory->makeCardRecognitionResult($cardImagesByKind)) {
            try {
                $ccDetectionResult = $creditCardDetector->detect($cardRecognitionResult);
            } catch (\Throwable $e) {
                $this->logger->critical(
                    'Credit card image detection error: ' . $e->getMessage(),
                    ['traces' => StackTraceUtils::flattenExceptionTraces($e)]
                );
            }

            if (isset($ccDetectionResult)) {
                $isCCdetected = $ccDetectionResult->isDetected();
            }

            if ($this->dryRun) {
                return [$isCCdetected, $cardImagesByKind];
            }

            foreach (
                [
                    [CardImage::KIND_FRONT, $ccDetectionResult->getFront()],
                    [CardImage::KIND_BACK, $ccDetectionResult->getBack()],
                ] as [$sideKind, $sideRects]
            ) {
                if (!isset($cardImagesByKind[$sideKind])) {
                    continue;
                }

                $originalCardImage = $cardImagesByKind[$sideKind];

                $originalCardImage
                    ->setCcDetected($isCCdetected)
                    ->setCcDetectorVersion($currentDetectorVersion);
                $this->entityManager->flush($originalCardImage);

                if ($isCCdetected) {
                    if ($originalCardImage->hasGoogleVisionResposne()) {
                        $visionResponse = $originalCardImage->getGoogleVisionResponse();

                        if (isset($visionResponse['textAnnotations'])) {
                            $visionResponse['textAnnotations'] = $this->domConverter->filter(
                                $visionResponse['textAnnotations'],
                                $sideRects,
                                $originalCardImage->getWidth(),
                                $originalCardImage->getHeight()
                            );
                        }

                        if (isset($visionResponse['fullTextAnnotation'])) {
                            $visionResponse['fullTextAnnotation'] = ['censored' => true];
                        }

                        $originalCardImage->setGoogleVisionResponse($visionResponse);
                        $originalCardImage->setCreditCardDetectionResult($sideRects);
                    }

                    $censoredCardImage = $this->imageCensor->censorImage($originalCardImage, $sideRects);

                    if ($censoredCardImage !== $originalCardImage) {
                        if ($originalCardImage->hasContainer()) {
                            $container = $originalCardImage->getContainer();
                            $container->removeCardImage($originalCardImage);
                            $originalCardImage->setUser($container->getUserid());
                            $this->entityManager->flush();
                            $censoredCardImage->setContainer($container);
                        }

                        $censoredCardImage
                            ->setUploadDate($originalCardImage->getUploadDate())
                            ->setComputerVisionResult($originalCardImage->getComputerVisionResult() ?? [])
                            ->setKind($originalCardImage->getKind())
                            ->setClientUUID($originalCardImage->getClientUUID())
                            ->setDetectedProviderId($originalCardImage->getDetectedProviderId())
                            ->setCcDetected(true)
                            ->setCcDetectorVersion($currentDetectorVersion);

                        $originalCardImage->setClientUUID(null);
                        $cardImagesByKind[$sideKind] = $censoredCardImage;
                        $this->entityManager->flush();
                    }
                }
            }
        } else {
            foreach ($cardImagesByKind as $cardImage) {
                $cardImage
                    ->setCcDetected(false)
                    ->setCcDetectorVersion($currentDetectorVersion);
                $this->entityManager->flush($cardImage);
            }
        }

        return [$isCCdetected, $cardImagesByKind];
    }

    /**
     * @param CardImage[] $cardImagesByKind
     */
    public function handleCardImageParsing(CardImageParserInterface $cardImageParser, array $cardImagesByKind, ?FormInterface $form = null)
    {
        if (!($cardRecognitionResult = $this->cardRecognitionResultFactory->makeCardRecognitionResult($cardImagesByKind))) {
            return;
        }

        try {
            $parsedProperties = $cardImageParser->parseImages($cardRecognitionResult);
            $supportedProperties = ([get_class($cardImageParser), 'getSupportedProperties'])();
        } catch (\Throwable $e) {
            $this->logger->critical(
                'Card image parsing error: ' . $e->getMessage(),
                ['traces' => StackTraceUtils::flattenExceptionTraces($e)]
            );

            return;
        }

        $parsedProperties = array_filter($parsedProperties, function ($property) {
            return '' !== trim($property);
        });

        if (!$parsedProperties) {
            if (!$this->dryRun) {
                foreach ($cardImagesByKind as $cardImage) {
                    $cardImage->setParsingResult([], $supportedProperties, []);
                    $this->entityManager->flush($cardImage);
                }
            }

            return;
        }

        $propertyCodes = array_keys($parsedProperties);
        $propertyCodesNormalized = array_map('strtolower', $propertyCodes);
        $formProperties = [];

        if ($form) {
            /** @var FormInterface $formChild */
            foreach ($form as $formChild) {
                if (
                    (false !== ($propertyIdx = array_search(strtolower($formChild->getName()), $propertyCodesNormalized)))
                    && is_scalar($parsedProperties[$propertyCodes[$propertyIdx]])
                    && !$formChild->isSubmitted()
                    && !(
                        $formChild->getConfig()->getCompound()
                        || $formChild->all()
                    )
                ) {
                    $formProperties[] = $propertyCodes[$propertyIdx];
                    $formChild->submit($parsedProperties[$propertyCodes[$propertyIdx]]);
                }
            }
        }

        if (!$this->dryRun) {
            foreach ($cardImagesByKind as $cardImage) {
                $cardImage->setParsingResult($parsedProperties, $supportedProperties, $formProperties);
                $this->entityManager->flush($cardImage);
            }
        }
    }

    public function getCurrentDetectorVersion(string $providerCode): string
    {
        $parserKlass = $this->parserLoader->loadParserClass($providerCode);
        $reflectionClass = new \ReflectionClass($parserKlass);

        $reflectionMethod = $reflectionClass->getMethod('detect');
        $annotations = [];

        if (false !== ($docComment = $reflectionClass->getDocComment())) {
            $annotations = array_merge($annotations, $this->parseAnnotations($docComment));
        }

        if (false !== ($docComment = $reflectionMethod->getDocComment())) {
            $annotations = array_merge($annotations, $this->parseAnnotations($docComment));
        }

        /** @var Detector[] $annotations */
        $annotations = array_values(array_filter($annotations, function ($annotation) { return $annotation instanceof Detector; }));
        $currentDetectorVersion = self::CC_DETECTOR_VERSION_PREFIX;

        if ($annotations) {
            $currentDetectorVersion .= '_' . $annotations[0]->version;
        }

        return $currentDetectorVersion;
    }

    protected function parseAnnotations(string $docComment): array
    {
        try {
            return $this->docParser->parse($docComment);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
