<?php

declare(strict_types=1);

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\DocumentImage;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\CardImage\DocumentImageManager;
use AwardWallet\MainBundle\Manager\CardImage\Exception\ImageException;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/documentImage")
 */
class DocumentImageController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private AwTokenStorageInterface $awTokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        LocalizeService $localizeService,
        AwTokenStorageInterface $awTokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $localizeService->setRegionalSettings();
        $this->awTokenStorage = $awTokenStorage;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Route("/{documentImageId}", name="awm_document_image_download", methods={"GET"})
     * @ParamConverter("documentImage", class="AwardWalletMainBundle:DocumentImage", options={"id": "documentImageId"})
     */
    public function loadAction(
        DocumentImage $documentImage,
        bool $responseStreaming,
        DocumentImageManager $documentImageManager
    ): Response {
        if (!$this->isGranted('VIEW', $documentImage)) {
            throw $this->createNotFoundException();
        }

        $imageStream = $documentImageManager->getImageStream($documentImage);

        if (!isset($imageStream)) {
            throw $this->createNotFoundException();
        }

        $expireDate = clone $documentImage->getUploadDate();
        $expireDate->modify('+10 years');

        if ($responseStreaming) {
            $response = new StreamCopyResponse(
                $imageStream,
                $documentImage->getFileSize(),
                200,
                ['Content-Type' => $documentImage->getFormat()]
            );
        } else {
            $response = new Response($content = (string) $imageStream, 200, ['Content-Length' => strlen($content)]);
        }

        $response
            ->setExpires($expireDate)
            ->setLastModified($documentImage->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $documentImage->getFileName()));
        $response->headers->set('Pragma', '');

        return $response;
    }

    /**
     * @Route("/document/{couponId}",
     *     name="aww_document_image_upload",
     *     methods={"POST"},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function uploadAction(
        Request $request,
        Providercoupon $coupon,
        DocumentImageManager $documentImageManager,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $invalidImageResultProvider = function ($error = null) {
            return $this->errorJsonResponse($error ?? 'Invalid image format\data');
        };

        if (
            (PROVIDER_KIND_DOCUMENT !== $coupon->getKind())
            || !\in_array(
                $coupon->getTypeid(),
                [
                    Providercoupon::TYPE_VISA,
                    Providercoupon::TYPE_PASSPORT,
                    Providercoupon::TYPE_VACCINE_CARD,
                ])
        ) {
            return $invalidImageResultProvider();
        }

        if (!$this->isGranted('EDIT', $coupon)) {
            throw $this->createNotFoundException();
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->getIterator()->current();

        if (!$uploadedFile) {
            return $invalidImageResultProvider();
        }

        try {
            $documentImage = $documentImageManager->saveUploadedImage($this->getCurrentUser(), $uploadedFile);
        } catch (ImageException $e) {
            return $invalidImageResultProvider($e->getMessage());
        }

        $documentImage->setProviderCoupon($coupon);
        $entityManager->flush();

        return $this->jsonResponse([
            'coupon' => $this->loadCoupon($coupon),
            'id' => $documentImage->getDocumentImageId(),
        ]);
    }

    /**
     * @Route("/{documentImageId}",
     *     name="awm_document_image_delete",
     *     methods={"DELETE"},
     *     requirements = {
     *         "documentImageId" = "\d+"
     *     }
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("documentImage", class="AwardWalletMainBundle:DocumentImage", options={"id": "documentImageId"})
     */
    public function deleteAction(DocumentImage $documentImage, DocumentImageManager $documentImageManager): JsonResponse
    {
        if (!$this->isGranted('EDIT', $documentImage)) {
            throw $this->createNotFoundException();
        }

        $coupon = $documentImage->getProviderCoupon();
        $documentImageManager->deleteImage($documentImage);

        return $this->jsonResponse(['coupon' => $coupon ? $this->loadCoupon($coupon) : null]);
    }

    /**
     * @Route("", name="awm_document_image_multi_delete", methods={"DELETE"})
     * @JsonDecode
     */
    public function multiDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentImageManager $documentImageManager
    ): JsonResponse {
        $documentRepository = $entityManager->getRepository(DocumentImage::class);
        $coupon = null;

        $removedItemsIdsList =
            it($request->request->all() ?: [])
            ->take(50)
            ->filter('\\is_integer')
            ->mapToInt()
            ->flatMap(function (int $id) use ($documentRepository, $documentImageManager, &$coupon) {
                if (
                    /** @var DocumentImage $documentImage */
                    ($documentImage = $documentRepository->find($id))
                    && $this->isGranted('EDIT', $documentImage)
                ) {
                    $coupon = $documentImage->getProviderCoupon();
                    $documentImageManager->deleteImage($documentImage);

                    yield $id;
                }
            })
            ->toArray();

        return new JsonResponse([
            'success' => (bool) $removedItemsIdsList,
            'removed' => $removedItemsIdsList,
            'coupon' => $coupon ? $this->loadCoupon($coupon) : null,
        ]);
    }

    protected function loadCoupon(Providercoupon $providercoupon)
    {
        return $this->accountListManager->getCoupon(
            $this->optionsFactory
                ->createMobileOptions()
                ->set(Options::OPTION_USER, $this->awTokenStorage->getBusinessUser()),
            $providercoupon->getProvidercouponid()
        );
    }
}
