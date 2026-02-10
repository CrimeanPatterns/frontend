<?php

namespace AwardWallet\MainBundle\Manager\CardImage\HttpHandler;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\CardImageRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\ClassUtils;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionClient;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Manager\CardImage\Exception\ImageException;
use AwardWallet\MainBundle\Manager\CardImage\ParserHandler;
use AwardWallet\MainBundle\Manager\CardImage\ProviderDetector;
use AwardWallet\MainBundle\Worker\AsyncProcess\CardImageProviderDetectionTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MobileBundle\Form\Type\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileHandler
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var CardImageRepository
     */
    private $cardImageRep;
    /**
     * @var CardImageManager
     */
    private $cardImageManager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var GoogleVisionClient
     */
    private $googleVisionClient;
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var FormDehydrator
     */
    private $formDehydrator;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var Process
     */
    private $asyncTaskExecutor;
    /**
     * @var ProviderDetector
     */
    private $providerDetector;
    /**
     * @var ParserHandler
     */
    private $parserHandler;
    /**
     * @var AccountListManager
     */
    private $accountListManager;
    /**
     * @var OptionsFactory
     */
    private $optionsFactory;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        ObjectRepository $cardImageRep,
        CardImageManager $cardImageManager,
        EntityManagerInterface $entityManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        UrlGeneratorInterface $urlGenerator,
        GoogleVisionClient $googleVisionClient,
        ProviderRepository $providerRepository,
        FormDehydrator $formDehydrator,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        ApiVersioningService $apiVersioning,
        ProviderDetector $providerDetector,
        Process $asyncTaskExecutor,
        ParserHandler $parserHandler
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->cardImageRep = $cardImageRep;
        $this->cardImageManager = $cardImageManager;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->googleVisionClient = $googleVisionClient;
        $this->providerRepository = $providerRepository;
        $this->formDehydrator = $formDehydrator;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->apiVersioning = $apiVersioning;
        $this->asyncTaskExecutor = $asyncTaskExecutor;
        $this->providerDetector = $providerDetector;
        $this->parserHandler = $parserHandler;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function handleLoyaltyRequest(Request $request, LoyaltyProgramInterface $container)
    {
        if ('DELETE' === ($method = $request->getMethod())) {
            return $this->handleLoyaltyRemove($request, $container);
        } elseif ('POST' === $method) {
            return $this->handleLoyaltyUpload($request, $container);
        } elseif ('HEAD' === $method) {
            return $this->successResponse();
        } else {
            throw new \RuntimeException('Invalid request method');
        }
    }

    public function handleUploadRequest(Request $request)
    {
        $this->extractData($request);

        if (
            !($user = ($token = $this->tokenStorage->getToken()) ? $token->getUser() : null)
            || !($user instanceof Usr)
        ) {
            return $this->errorResponse("Can't handle request from logged out user");
        }

        if ($request->files->count() !== 1) {
            return $this->errorResponse("Incorrect files amount");
        }

        if (!in_array($kind = $request->get('kind'), ['Front', 'Back'])) {
            return $this->errorResponse('Missing fields from request body');
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->getIterator()->current();

        try {
            $cardImage = $this->cardImageManager->saveUploadedImage($user, $uploadedFile);
        } catch (ImageException $e) {
            return $this->errorResponse($e->getMessage());
        }

        $cardImage->setKind($kind === 'Front' ? CardImage::KIND_FRONT : CardImage::KIND_BACK);

        if (
            ($barcodeData = $request->get('barcode'))
            && isset($barcodeData['text'], $barcodeData['format'])
        ) {
            $cardImage->updateComputerVisionResult([
                'aw_barcode_detect' => [
                    'text' => $barcodeData['text'],
                    'format' => $barcodeData['format'],
                ],
            ]);
        }

        $visionResult = $this->googleVisionClient->recognize(
            $cardImage,
            @file_get_contents($uploadedFile->getPathname()),
            [
                'LOGO_DETECTION',
                'TEXT_DETECTION',
            ]
        );

        $this->entityManager->persist($cardImage);
        $this->entityManager->flush($cardImage);

        if (!StringUtils::isEmpty($uuid = $request->get('UUID'))) {
            $cardImage->setClientUUID($uuid);
            /** @var CardImage[] $cardImages */
            $cardImages = $this->cardImageRep->findBy(['userId' => $user, 'clientUUID' => $uuid], ['cardImageId' => 'asc']);
        } else {
            return $this->response([
                'CardImageId' => $cardImage->getCardImageId(),
                'Url' => $this->urlGenerator->generate('awm_card_image_download', ['cardImageId' => $cardImage->getCardImageId()], UrlGenerator::ABSOLUTE_URL),
                'FileName' => $cardImage->getFileName(),
                'Kind' => $kind,
            ]);
        }

        $provider = null;

        // Provider detected on first card image wins
        if (
            $cardImages
            && ($cardImages[0]->getCardImageId() !== $cardImage->getCardImageId())
            && ($lastDetectedProvider = $cardImages[0]->getDetectedProviderId())
        ) {
            $provider = $lastDetectedProvider;
        }

        if ($visionResult) {
            $detectedProvider = $this->providerDetector->detectByCardImage($cardImage, $visionResult);
            $this->entityManager->flush($cardImage);

            if (!$provider) {
                $provider = $detectedProvider;
            }

            if (
                !$provider
                && ($detectedProviderData = $this->providerDetector->detectByGoogleVisionResult($visionResult, null, true))
            ) {
                $provider = $this->providerRepository->find($detectedProviderData[0]['ProviderID']);
            }
        }

        $form = $this->formFactory->create(
            AccountType::class,
            (new Account())
                ->setProviderid($provider)
                ->setUserid($user),
            [
                'provider' => $provider,
                'method' => 'POST',
            ]
        );
        /** @var CardImage[] $cardImagesMap */
        $cardImagesMap = [];

        if ($cardImages) {
            $cardImagesMap[$cardImages[0]->getKind()] = $cardImages[0];
        }

        $cardImagesMap[$cardImage->getKind()] = $cardImage;
        $cardsData = array_merge(
            [
                'Front' => [
                    'Label' => $this->translator->trans('card-pictures.front.title'),
                ],
                'Back' => [
                    'Label' => $this->translator->trans('card-pictures.back.title'),
                ],
            ]
        );

        if ($provider) {
            $cardImagesMap = $this->parserHandler->handle($provider, $cardImagesMap, $form);
        }

        foreach ([
            [CardImage::KIND_BACK, 'Back'],
            [CardImage::KIND_FRONT, 'Front'], ] as [$kind, $kindName]
        ) {
            if (!isset($cardImagesMap[$kind])) {
                continue;
            }

            $cardImage = $cardImagesMap[$kind];
            $cardsData[$kindName]['Url'] = $this->urlGenerator->generate(
                'awm_card_image_download',
                ['cardImageId' => $cardImage->getCardImageId()],
                UrlGenerator::ABSOLUTE_URL
            );
            $cardsData[$kindName]['FileName'] = $cardImage->getFileName();
            $cardsData[$kindName]['CardImageId'] = $cardImage->getCardImageId();
        }

        if ($form->has('cardImages')) {
            $form->get('cardImages')->submit($cardsData);
        }

        return $this->response(
            array_merge(
                [
                    'formData' => $this->formDehydrator->dehydrateForm($form),
                    'Kind' => $provider ? $provider->getKind() : 'custom',
                    'DisplayName' => $provider ?
                        $provider->getDisplayname() :
                        $this->translator->trans(
                            'custom.account.form.title',
                            [
                                '%providerName%' => $this->translator->trans('custom.account.list.title', [], 'mobile'),
                                '%programName%' => $this->translator->trans('custom.account.list.notice', [], 'mobile'),
                            ],
                            'mobile'
                        ),
                ],
                ['ProviderId' => isset($provider) ? $provider->getProviderid() : 'custom'],
                $cardsData
            )
        );
    }

    protected function extractData(Request $request)
    {
        $previous = \json_encode($request->request->all());

        if (
            is_string($data = $request->get('data'))
            && !StringHandler::isEmpty($data)
            && is_array($decodedData = @json_decode($data, true))
        ) {
            $request->request->replace($decodedData);
        }

        $request->request->set('_previous_data', $previous);
    }

    protected function handleLoyaltyUpload(Request $request, LoyaltyProgramInterface $container)
    {
        $this->checkOrThrow(
            $this->authorizationChecker->isGranted('EDIT', $container)
        );

        if ($this->apiVersioning->supports(MobileVersions::CARD_IMAGES_ON_FORM)) {
            $this->extractData($request);
        }

        if (
            !($user = ($token = $this->tokenStorage->getToken()) ? $token->getUser() : null)
            || !($user instanceof Usr)
        ) {
            return $this->errorResponse("Can't handle request from logged out user");
        }

        if (!StringUtils::isEmpty($cardImageId = $request->get('CardImageId'))) {
            // relink image
            $this->checkOrThrow(
                /** @var CardImage $cardImage */
                ($cardImage = $this->cardImageRep->find($cardImageId))
                && $this->authorizationChecker->isGranted('EDIT', $cardImage)
            );

            $kind = $cardImage->getKind();
        } else {
            // save uploaded image
            if ($request->files->count() !== 1) {
                return $this->errorResponse("Incorrect files amount");
            }

            if (!in_array($kind = $request->get('kind'), ['Front', 'Back'])) {
                return $this->errorResponse('Missing fields from request body: ' . \substr($request->request->get('_previous_data'), 0, 100));
            }

            $kind = ('Front' === $kind) ? CardImage::KIND_FRONT : CardImage::KIND_BACK;
            $request->getSession()->save();

            try {
                $cardImage = $this->cardImageManager->saveUploadedImage($user, $uploadedFile = $request->files->getIterator()->current());
            } catch (ImageException $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        /** @var CardImage $oldCardImage */
        $oldCardImage = $this->cardImageRep->findOneBy([
            strtolower(ClassUtils::getName($container)) . 'id' => $container->getId(),
            'kind' => $kind,
        ]);

        if ($oldCardImage) {
            $this->cardImageManager->deleteImage($oldCardImage);
        }

        $cardImage
            ->setContainer($container)
            ->setKind($kind);

        $this->entityManager->persist($cardImage);
        $this->entityManager->flush($cardImage);
        $this->asyncTaskExecutor->execute(new CardImageProviderDetectionTask($cardImage));

        return $this->response([
            'CardImageId' => $cardImage->getCardImageId(),
            'account' => $this->loadLoylatyProgram($container),
        ]);
    }

    protected function handleLoyaltyRemove(Request $request, LoyaltyProgramInterface $container)
    {
        $this->checkOrThrow(
            !StringUtils::isEmpty($kind = $request->get('kind'))
            && in_array($kind, ['Front', 'Back'], true)
            /** @var CardImage $cardImage */
            && ($cardImage = $this->cardImageRep->findOneBy([
                strtolower(ClassUtils::getName($container)) . 'id' => $container->getId(),
                'kind' => ($kind === 'Front') ? CardImage::KIND_FRONT : CardImage::KIND_BACK,
            ]))
            && ($cardImage->getContainer() === $container)
            && $this->authorizationChecker->isGranted('DELETE', $cardImage)
        );

        $this->cardImageManager->deleteImage($cardImage);

        return $this->response([
            'account' => $this->loadLoylatyProgram($container),
        ]);
    }

    protected function loadLoylatyProgram(LoyaltyProgramInterface $loyaltyProgram)
    {
        $options = $this->optionsFactory->createMobileOptions(
            (new Options())
                ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
        );

        if ($loyaltyProgram instanceof Account) {
            return $this->accountListManager->getAccount($options, $loyaltyProgram->getAccountid());
        } elseif ($loyaltyProgram instanceof Subaccount) {
            return $this->accountListManager->getAccount($options, $loyaltyProgram->getAccountid()->getAccountid());
        } elseif ($loyaltyProgram instanceof Providercoupon) {
            return $this->accountListManager->getCoupon($options, $loyaltyProgram->getProvidercouponid());
        } else {
            throw new \RuntimeException('Unknown loyalty program type');
        }
    }

    protected function successResponse()
    {
        return new JsonResponse(['success' => true]);
    }

    protected function errorResponse($error)
    {
        return new JsonResponse(['error' => $error]);
    }

    protected function response($data)
    {
        return new JsonResponse($data);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function checkOrThrow($condition)
    {
        if (!$condition) {
            throw new NotFoundHttpException();
        }
    }
}
