<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Repositories\CardImageRepository;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionClient;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionResponseConverter;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Manager\CardImage\ParserHandler;
use AwardWallet\MainBundle\Manager\CardImage\ProviderDetector;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactoryInterface;

class CardImageProviderDetectionTaskExecutor implements ExecutorInterface
{
    /**
     * @var CardImageRepository
     */
    private $cardImageRepository;
    /**
     * @var CardImageManager
     */
    private $cardImageManager;
    /**
     * @var GoogleVisionClient
     */
    private $googleVisionClient;
    /**
     * @var GoogleVisionResponseConverter
     */
    private $googleVisionResponseConverter;
    /**
     * @var ProviderDetector
     */
    private $providerDetector;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var ParserHandler
     */
    private $parserHandler;

    public function __construct(
        CardImageRepository $cardImageRepository,
        CardImageManager $cardImageManager,
        GoogleVisionClient $googleVisionClient,
        GoogleVisionResponseConverter $googleVisionResponseConverter,
        ProviderDetector $providerDetector,
        EntityManager $entityManager,
        FormFactoryInterface $formFactory,
        ParserHandler $parserHandler
    ) {
        $this->cardImageRepository = $cardImageRepository;
        $this->cardImageManager = $cardImageManager;
        $this->googleVisionClient = $googleVisionClient;
        $this->googleVisionResponseConverter = $googleVisionResponseConverter;
        $this->providerDetector = $providerDetector;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->parserHandler = $parserHandler;
    }

    /**
     * @param CardImageProviderDetectionTask|Task $task
     * @param null $delay
     */
    public function execute(Task $task, $delay = null): Response
    {
        /** @var CardImage $cardImage */
        if (!($cardImage = $this->cardImageRepository->find($task->cardImageId))) {
            return new Response();
        }

        if ($cardImage->hasGoogleVisionResposne()) {
            $visionResult = $this->googleVisionResponseConverter->convert($cardImage->getGoogleVisionResponse());
        } else {
            if (StringUtils::isNotEmpty($cardImageContent = $this->cardImageManager->getImageContent($cardImage))) {
                $visionResult = $this->googleVisionClient->recognize(
                    $cardImage,
                    $cardImageContent,
                    [
                        'LOGO_DETECTION',
                        'TEXT_DETECTION',
                    ]
                );
                $this->entityManager->flush($cardImage);
            } else {
                $visionResult = null;
            }
        }

        if (
            (
                ($account = $cardImage->getAccount())
                || (
                    ($subaccount = $cardImage->getSubAccount())
                    && ($account = $subaccount->getAccountid())
                )
            )
            && ($provider = $account->getProviderid())
        ) {
            $container = $subaccount ?? $account;
            $cardImagesMap = [];

            foreach ($container->getCardImages() as $cardImage) {
                $cardImagesMap[$cardImage->getKind()] = $cardImage;
            }

            if ($cardImagesMap) {
                $this->parserHandler->handle($provider, $cardImagesMap);
            }
        }

        $this->saveProviderKeywords($cardImage);

        if ($cardImage->getDetectedProviderId()) {
            return new Response();
        }

        if ($visionResult) {
            if ($provider = $this->providerDetector->detectByCardImage($cardImage, $visionResult)) {
                $cardImage->setDetectedProviderId($provider);
            }

            $this->saveProviderKeywords($cardImage);
            $this->entityManager->flush($cardImage);
        }

        return new Response();
    }

    protected function saveProviderKeywords(CardImage $cardImage)
    {
        if (
            ($account = $cardImage->getAccount())
            && ($provider = $account->getProviderid())
        ) {
            $cardImage->updateAwProviderDetect([
                'savedProviderKeywords' => $provider->getKeywords(),
                'savedProviderStopKeywords' => $provider->getStopKeywords(),
            ]);
            $this->entityManager->flush($cardImage);
        }
    }
}
