<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\DocumentImage;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\CardImage\DocumentImageManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/documentImage")
 */
class DocumentImageController extends AbstractController
{
    /**
     * @Security("is_granted('VIEW', documentImage)")
     * @Route("/{documentImageId}", name="aw_document_image_download", options={"expose"=true})
     * @ParamConverter("documentImage", class="AwardWalletMainBundle:DocumentImage", options={"id": "documentImageId"})
     */
    public function loadAction(
        Request $request,
        DocumentImage $documentImage,
        DocumentImageManager $documentImageManager
    ): StreamCopyResponse {
        $request->getSession()->save();
        $imageStream = $documentImageManager->getImageStream($documentImage);

        if (!isset($imageStream)) {
            throw $this->createNotFoundException();
        }

        $expireDate = clone $documentImage->getUploadDate();
        $expireDate->modify('+10 years');

        if (!$request->get('response_streaming', false)) {
            $response = (new StreamCopyResponse(
                $imageStream,
                $documentImage->getFileSize(),
                200,
                ['Content-Type' => $documentImage->getFormat()]
            ));
        } else {
            $response = new Response($content = (string) $imageStream, 200, ['Content-Length' => strlen($content)]);
        }

        $response
            ->setExpires($expireDate)
            ->setLastModified($documentImage->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $documentImage->getFileName())
        );

        return $response;
    }

    /**
     * @Route("/coupon/{couponId}",
     *     name = "aw_document_image_coupon_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     options={"expose"=true},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Security("is_granted('EDIT', coupon) and is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     * @JsonDecode
     */
    public function handleCouponAction(
        Request $request,
        Providercoupon $coupon,
        EntityRepository $documentImageRepository,
        DocumentImageManager $documentImageManager,
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        OptionsFactory $optionsFactory,
        AccountListManager $accountListManager
    ) {
        $this->preHandler($request, $coupon);

        $response = ['success' => false];
        $imageId = $request->get('id');
        $imageId = is_numeric($imageId) ? (int) $imageId : null;

        if ('DELETE' === $request->getMethod()) {
            if (!$this->isGranted('DELETE', $coupon)) {
                throw $this->createAccessDeniedException();
            }

            /** @var DocumentImage $image */
            if (!$imageId || !($image = $documentImageRepository->find($imageId))) {
                throw $this->createNotFoundException();
            }

            $documentImageManager->deleteImage($image);
            $response['success'] = true;
        } elseif ('POST' === $request->getMethod()) {
            $file = $request->files->get('documentimage');

            if (!$file) {
                throw $this->createNotFoundException();
            }

            $documentImage = $documentImageManager->saveUploadedImage($tokenStorage->getUser(), $file, $imageId);
            $documentImage->setProviderCoupon($coupon);
            $entityManager->flush();

            $listOptions = $optionsFactory->createDesktopInfoOptions()
                ->set(Options::OPTION_USER, $tokenStorage->getBusinessUser())
                ->set(Options::OPTION_LOAD_CARD_IMAGES, true);

            $providerCoupon = $accountListManager->getCoupon($listOptions, $coupon->getProvidercouponid());

            if (!empty($images = $this->getDocumentImageData($providerCoupon))) {
                $response['success'] = true;
                $response['CardImages'] = $images;
            } else {
                $response['error'] = 'Invalid image format\data';
            }
        }

        return new JsonResponse($response);
    }

    private function preHandler(Request $request, $entity = null): void
    {
        $fileKey = false;

        if ($entity instanceof Providercoupon && $entity->isDocument()) {
            $prefix = 'slim_output';

            foreach ($request->files->all() as $id => $file) {
                if (strpos($id, $prefix) !== false) {
                    $fileKey = $id;
                }
            }
        } else {
            throw $this->createNotFoundException();
        }

        // for compatibility with older mobile applications
        if ($fileKey) {
            $file = $request->files->get($fileKey);

            if ($entity instanceof Providercoupon && $entity->isDocument()) {
                $fileName = [];
            }

            $fileName[] = 'c' . $entity->getId();
            $fileName[] = md5($file->getClientOriginalName() . '-' . uniqid('', true));
            $fileName = implode('-', $fileName) . '.' . str_replace('jpeg', 'jpg', strtolower($file->guessClientExtension()));

            $request->files->add(['documentimage' => new UploadedFile($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(), $fileName, $file->getClientMimeType(), $file->getClientSize(), $file->getError())]);
            $request->files->remove($fileKey);
        }
    }

    private function getDocumentImageData(array $providerCoupon)
    {
        $data = [];

        if (array_key_exists('DocumentImages', $providerCoupon) && is_array($providerCoupon['DocumentImages'])) {
            $data = $providerCoupon['DocumentImages'];
        }

        if (empty($data)) {
            return false;
        }

        return $data;
    }
}
