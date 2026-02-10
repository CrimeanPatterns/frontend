<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Manager\CardImage\HttpHandler\MobileHandler;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
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
 * Class CardImageController.
 *
 * @Route("/cardImage")
 */
class CardImageController extends AbstractController implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;

    private CardImageManager $cardImageManager;

    private MobileHandler $cardImageHttpHandlerMobile;

    private AccountListManager $accountListManager;

    private Mapper $mapper;

    private OptionsFactory $optionsFactory;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        CardImageManager $cardImageManager,
        MobileHandler $cardImageHttpHandlerMobile,
        AccountListManager $accountListManager,
        Mapper $mapper,
        OptionsFactory $optionsFactory
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->cardImageManager = $cardImageManager;
        $this->cardImageHttpHandlerMobile = $cardImageHttpHandlerMobile;
        $this->accountListManager = $accountListManager;
        $this->mapper = $mapper;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Route("/{cardImageId}", name="aw_card_image_download", methods={"GET"}, options={"expose"=true})
     * @ParamConverter("cardImage", class="AwardWalletMainBundle:CardImage", options={"id": "cardImageId"})
     */
    public function loadAction(Request $request, CardImage $cardImage)
    {
        if (!$this->isGranted('VIEW', $cardImage)) {
            throw $this->createNotFoundException();
        }

        return $this->getImageStream($request, $cardImage);
    }

    /**
     * @Route("/proxy/{cardImageUUID}",
     *     name="aw_card_image_download_staff_proxy",
     *     methods={"GET"},
     *     requirements={"cardImageUUID" = "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"},
     *     options={"expose"=false})
     * @Security("is_granted('ROLE_VIEW_CARD_IMAGES')")
     * @ParamConverter("cardImage", class="AwardWalletMainBundle:CardImage", options={"mapping": {"cardImageUUID": "UUID"}})
     */
    public function loadProxyAction(Request $request, CardImage $cardImage)
    {
        return $this->getImageStream($request, $cardImage);
    }

    /**
     * @Route("/account/{accountId}",
     *     name = "aw_card_image_account_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     options={"expose"=true},
     *     requirements = {
     *         "accountId" = "\d+"
     *     }
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function handleAccountAction(Request $request, Account $account)
    {
        if (($result = $this->preHandler($request, $account)) instanceof JsonResponse) {
            return $result;
        }

        return $this->postHandler($request, $account);
    }

    /**
     * @Route("/account/{accountId}/{subAccountId}",
     *     name = "aw_card_image_subaccount_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     options={"expose"=true},
     *     requirements = {
     *         "accountId" = "\d+",
     *         "subAccountId" = "\d+",
     *     }
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subaccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     */
    public function handleSubAccountAction(Request $request, Account $account, Subaccount $subaccount)
    {
        if (($result = $this->preHandler($request, $subaccount)) instanceof JsonResponse) {
            return $result;
        }

        if ($subaccount->getAccountid() !== $account) {
            throw $this->createNotFoundException();
        }

        return $this->postHandler($request, $subaccount);
    }

    /**
     * @Route("/coupon/{couponId}",
     *     name = "aw_card_image_coupon_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     options={"expose"=true},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @JsonDecode
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function handleCouponAction(Request $request, Providercoupon $coupon)
    {
        if (($result = $this->preHandler($request, $coupon)) instanceof JsonResponse) {
            return $result;
        }

        return $this->postHandler($request, $coupon);
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('card-pictures.disabled-security-reason'))->setDesc('Unfortunately, this feature is disabled for credit cards. For security reasons, you are not allowed  to store your credit card image in AwardWallet.'),
            (new Message('card-pictures.you-can-upload-image'))->setDesc('You can easily upload images through the AwardWallet mobile app using the camera on your smartphone.'),
            (new Message('card-pictures.loading-image'))->setDesc('Loading image...'),
            (new Message('card-pictures.error.file-type'))->setDesc('Invalid file type, expects: $0.'),
            (new Message('card-pictures.error.big-file'))->setDesc('File is too big, maximum file size: $0 MB.'),
            (new Message('card-pictures.error.crop-not-support'))->setDesc('Your browser does not support image cropping.'),
            (new Message('card-pictures.error.small-image'))->setDesc('Image is too small, minimum size is: $0 pixels.'),
            (new Message('card-pictures.error.big-content'))->setDesc('The file is probably too big'),
            (new Message('card-pictures.error.unknown'))->setDesc('An unknown error occurred'),
            (new Message('card-pictures.status.saved'))->setDesc('Saved'),
            (new Message('card-pictures.label.confirm'))->setDesc('Confirm'),
            (new Message('card-pictures.label.cancel'))->setDesc('Cancel'),
            (new Message('card-pictures.label.rotate'))->setDesc('Rotate'),
            (new Message('card-pictures.label.remove'))->setDesc('Remove'),
            (new Message('card-pictures.label.edit'))->setDesc('Edit'),
            (new Message('card-pictures.label.download'))->setDesc('Download'),
            (new Message('card-pictures.confirm-delete'))->setDesc('Are you sure you want to delete this card image?'),
            (new Message('card-pictures.label.add-image'))->setDesc('Add Image'),
        ];
    }

    protected function getImageStream(Request $request, CardImage $cardImage)
    {
        $request->getSession()->save();
        $imageStream = $this->cardImageManager->getImageStream($cardImage);

        if (!isset($imageStream)) {
            throw $this->createNotFoundException();
        }

        $expireDate = clone $cardImage->getUploadDate();
        $expireDate->modify('+10 years');

        if (!$request->get('response_streaming', false)) {
            $response = (new StreamCopyResponse(
                $imageStream,
                $cardImage->getFileSize(),
                200,
                ['Content-Type' => $cardImage->getFormat()]
            ));
        } else {
            $response = new Response($content = (string) $imageStream, 200, ['Content-Length' => strlen($content)]);
        }

        $response
            ->setExpires($expireDate)
            ->setLastModified($cardImage->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $cardImage->getFileName()));

        return $response;
    }

    private function preHandler(Request $request, $entity = null)
    {
        // bypass, to verify csrf-token (for ajax <=> slimcropper.js)
        if (!$this->isCsrfTokenValid('cardImage', $request->headers->get('X-CSRF-TOKEN'))) {
            throw $this->createNotFoundException();
        }

        if (PROVIDER_KIND_CREDITCARD === $entity->getKind()) {
            return $this->json([
                'error' => 'Invalid image format\data',
            ]);
        }

        $fileKey = false;

        if ($entity instanceof Providercoupon && $entity->isDocument()) {
            $prefix = 'slim_output';

            foreach ($request->files->all() as $id => $file) {
                if (strpos($id, $prefix) !== false) {
                    $fileKey = $id;
                }
            }
        } else {
            $allowKeys = ['slim_output_0', 'slim_output_1'];

            for ($i = 0, $iCount = count($allowKeys); $i < $iCount; $i++) {
                if ($request->files->has($allowKeys[$i])) {
                    $fileKey = $allowKeys[$i];
                }
            }
        }

        // for compatibility with older mobile applications
        if ($fileKey) {
            $file = $request->files->get($fileKey);

            if ($entity instanceof Providercoupon && $entity->isDocument()) {
                $fileName = [];
            } else {
                $fileName = ['Back' === $request->get('kind') ? 'Back' : 'Front'];
            }

            if ($entity instanceof Subaccount) {
                $fileName[] = 'a' . $entity->getAccountid()->getId();
                $fileName[] = $entity->getId();
            } elseif ($entity instanceof Account) {
                $fileName[] = 'a' . $entity->getId();
            } elseif ($entity instanceof Providercoupon) {
                $fileName[] = 'c' . $entity->getId();
            }
            $fileName[] = md5($file->getClientOriginalName() . '-' . uniqid('', true));
            $fileName = implode('-', $fileName) . '.' . str_replace('jpeg', 'jpg', strtolower($file->guessClientExtension()));

            $request->files->add(['cardimage' => new UploadedFile($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename(), $fileName, $file->getClientMimeType(), $file->getClientSize(), $file->getError())]);
            $request->files->remove($fileKey);
        }

        return true;
    }

    private function postHandler(Request $request, $entity)
    {
        $response = ['success' => false];
        $handle = $this->cardImageHttpHandlerMobile->handleLoyaltyRequest($request, $entity);
        $data = $handle instanceof JsonResponse ? json_decode($handle->getContent()) : [];

        if (array_key_exists('CardImageId', $data)) {
            $response['CardImageId'] = $data->CardImageId;
        }

        if (array_key_exists('error', $data)) {
            $response['error'] = $data->error;
        } elseif ('DELETE' === $request->getMethod()) {
            $response['success'] = true;
        } elseif ('POST' === $request->getMethod()) {
            $options = $this->optionsFactory
                ->createDefaultOptions()
                ->set(Options::OPTION_FORMATTER, $this->mapper)
                ->set(Options::OPTION_LOAD_CARD_IMAGES, true)
                ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser());

            if ($entity instanceof Account) {
                $account = $this->accountListManager->getAccount($options, $entity->getAccountid());
            } elseif ($entity instanceof Subaccount) {
                $account = $this->accountListManager->getAccount($options, $entity->getAccountid()->getAccountid());
            } elseif ($entity instanceof Providercoupon) {
                $account = $this->accountListManager->getCoupon($options, $entity->getProvidercouponid());
            } else {
                throw new \RuntimeException('Unknown loyalty program type');
            }

            if (!empty($cardImage = $this->getCardImageData($request, $account))) {
                $response['success'] = true;
                $response['CardImages'] = $cardImage;
            } else {
                $response['error'] = 'Invalid image format\data';
            }
        }

        return $this->json($response);
    }

    private function getCardImageData(Request $request, $account)
    {
        $data = [];
        $subAccountId = $request->get('subAccountId');

        if (!empty($subAccountId) && array_key_exists('SubAccountsArray', $account)) {
            foreach ($account['SubAccountsArray'] as $i => $subaccount) {
                if ($subaccount['SubAccountID'] === $subAccountId && !empty($subaccount['CardImages'])) {
                    $data = $subaccount['CardImages'];
                }
            }
        } elseif (array_key_exists('CardImages', $account) && is_array($account['CardImages'])) {
            $data = $account['CardImages'];
        }

        if (empty($data)) {
            return false;
        }

        foreach ($data as $kindImage => $image) {
            foreach ($image as $key => $item) {
                if (!in_array($key, ['CardImageID', 'FileName', 'SubAccountID'])) {
                    unset($data[$kindImage][$key]);
                }
            }
        }

        return $data;
    }
}
