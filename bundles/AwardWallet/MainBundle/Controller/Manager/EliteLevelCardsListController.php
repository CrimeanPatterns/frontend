<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class EliteLevelCardsListController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_CARDIMAGEPARSING')")
     * @Route("/manager/elitelevelcards/{cardImageId}", name="aw_manager_card_image", methods={"GET"})
     * @ParamConverter("cardImage", class="AwardWalletMainBundle:CardImage", options={"id":"cardImageId"})
     */
    public function cardImageAction(
        Request $request,
        CardImage $cardImage,
        CardImageManager $cardImageManager
    ): Response {
        $imageStream = $cardImageManager->getImageStream($cardImage);

        if (!isset($imageStream)) {
            throw $this->createNotFoundException();
        }

        $expireDate = clone $cardImage->getUploadDate();
        $expireDate->modify('+10 years');

        $response = (new StreamCopyResponse(
            $imageStream,
            $cardImage->getFileSize(),
            200,
            ['Content-Type' => $cardImage->getFormat()]
        ));

        $response
            ->setExpires($expireDate)
            ->setLastModified($cardImage->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $response->headers->set('Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $cardImage->getFileName()));

        return $response;
    }
}
