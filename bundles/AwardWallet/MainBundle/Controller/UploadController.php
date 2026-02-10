<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/upload")
 */
class UploadController extends AbstractController
{
    /**
     * @Route("/image/{resource}/{resourceId}",
     *     name="aw_common_upload_image",
     *     methods={"POST"},
     *     requirements={"resourceId"="\d+", "resource"=".*"}
     * )
     */
    public function uploadImageAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        ValidatorInterface $validator
    ) {
        if (!$authorizationChecker->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException();
        }
        /** @var Usr $user */
        $user = $this->getUser();

        if (!$authorizationChecker->isGranted('USER_BOOKING_PARTNER') && !$user->hasRole('ROLE_STAFF')) {
            throw $this->createAccessDeniedException();
        }
        $notBlankValidator = new Assert\NotBlank();
        $imageValidator = new Assert\Image();
        $errors = $validator->validate($request->files->get('upload'), [$notBlankValidator, $imageValidator]);

        if (!count($errors)) {
            [$url, $filename] = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\File::class)->saveFile($request);

            return new JsonResponse([
                'uploaded' => 1,
                'fileName' => $filename,
                'url' => $url,
            ]);
        }
        $result = '';

        foreach ($errors as $error) {
            $result .= $error->getMessage() . '<br />';
        }

        return new Response($result);
    }

    /**
     * @Route("/from-gravatar/{email}",
     *     name="aw_common_upload_gravatar",
     *     methods={"GET", "POST"},
     *     requirements={"email"=".+"},
     *     options={"expose"=true}
     * )
     * @Security("is_granted('ROLE_USER')")
     */
    public function getGravatarLinkAction(Request $request, $email)
    {
        $scheme = $request->getScheme();

        return new JsonResponse([
            'url' => $scheme . '://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '.jpeg?d=404&s=800',
        ]);
    }
}
