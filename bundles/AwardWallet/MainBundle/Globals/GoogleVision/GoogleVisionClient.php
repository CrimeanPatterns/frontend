<?php

namespace AwardWallet\MainBundle\Globals\GoogleVision;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Globals\ClassUtils;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

class GoogleVisionClient
{
    public const GOOGLE_API = "https://vision.googleapis.com/v1/images:annotate?key=%s";
    public const MAX_IMAGE_SIZE = 4 * 1024 * 1024;
    public const BASE64_FACTOR = 1.5;

    /**
     * @var ClientInterface
     */
    private $guzzleClient;
    /**
     * @var string
     */
    private $googleApiKey;
    /**
     * @var AntiBruteforceLockerService
     */
    private $throttler;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var GoogleVisionResponseConverter
     */
    private $googleVisionResponseConverter;

    public function __construct(
        Client $guzzleClient,
        LoggerInterface $logger,
        AntiBruteforceLockerService $throttler,
        GoogleVisionResponseConverter $googleVisionResponseConverter,
        $googleApiKey
    ) {
        $this->guzzleClient = $guzzleClient;
        $this->throttler = $throttler;
        $this->googleApiKey = $googleApiKey;
        $this->logger = $logger;
        $this->googleVisionResponseConverter = $googleVisionResponseConverter;
    }

    /**
     * @param string $imageContent image content
     * @param string[] $features recognition features
     * @return GoogleVisionResult|null
     */
    public function recognize(CardImage $cardImage, $imageContent, array $features)
    {
        if (strlen($imageContent) * self::BASE64_FACTOR > self::MAX_IMAGE_SIZE) {
            $this->logger->warning('image too big', [
                'module' => 'google_vision_client',
                'image_size' => strlen($imageContent),
            ]);

            return null;
        }

        $logContext = [
            'module' => 'google_vision_client',
            'container_id' => $cardImage->hasContainer() ?
                $cardImage->getContainer()->getId() :
                $cardImage->getUser()->getUserid(),
            'container_type' => ClassUtils::getName(
                $cardImage->hasContainer() ?
                    $cardImage->getContainer() :
                    $cardImage->getUser()
            ),
            'cardimage_id' => $cardImage->getCardImageId(),
        ];

        if (null !== $this->throttler->checkForLockout('_google_vision ')) {
            $this->logger->critical('google vision request throttled', [
                'module' => 'google_vision_client',
            ]);

            return null;
        }

        $requestFeatures = [];

        foreach ($features as $feature) {
            $requestFeatures[] = ['type' => $feature];
        }

        try {
            $startTimer = microtime(true);
            $response = $this->guzzleClient->post(
                sprintf(self::GOOGLE_API, $this->googleApiKey),
                [
                    'json' => [
                        'requests' => [
                            [
                                'features' => $requestFeatures,
                                'image' => [
                                    'content' => $base64Image = base64_encode($imageContent),
                                ],
                            ],
                        ],
                    ],
                ]
            );
            $this->logger->warning('google vision request', array_merge(
                [
                    'base64_image_size' => strlen($base64Image),
                    'time' => round(microtime(true) - $startTimer, 1),
                ],
                $logContext
            ));
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->logger->critical('unknown response format', array_merge(
                [
                    'response' => (string) $response->getBody(),
                    'headers' => json_encode($response->getHeaders()),
                ],
                $logContext
            ));

            return null;
        }

        if (
            (200 === $response->getStatusCode())
            && is_array($responseJson = @json_decode((string) $response->getBody(), true))
            && isset($responseJson['responses'])
        ) {
            foreach ($responseJson['responses'] as $idx => $visionResponse) {
                if (
                    !isset($visionResponse['logoAnnotations'])
                    && !isset($visionResponse['textAnnotations'])
                ) {
                    return null;
                }
            }
        } else {
            $this->logger->critical('unknown response format', array_merge(
                [
                    'response' => (string) $response->getBody(),
                    'headers' => json_encode($response->getHeaders()),
                ],
                $logContext
            ));

            return null;
        }

        $response = $responseJson['responses'][0];
        $cardImage->updateComputerVisionResult(['googleVision' => $response]);

        return $this->googleVisionResponseConverter->convert($response);
    }
}
