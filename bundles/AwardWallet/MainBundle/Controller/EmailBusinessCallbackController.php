<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\UtilBusiness;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailBusinessCallbackController extends AbstractController
{
    /**
     * @Route("/api/awardwallet/emailbusiness", name="aw_emailbusinesscallback_save")
     */
    public function saveAction(
        Request $request,
        UtilBusiness $utilBusiness,
        LoggerInterface $emailLogger,
        SerializerInterface $serializer,
        string $emailCallbackPassword
    ) {
        $businessId = (int) $request->getUser();

        if (!$utilBusiness->isBusinessById($businessId) || $request->getPassword() != $emailCallbackPassword) {
            $utilBusiness->log("access denied for " . $request->getUser() . ", business id: " . $businessId . ", pass: " . substr($request->getPassword(), 0, 1), $businessId);

            return new Response('access denied', 403);
        }

        /** @var ParseEmailResponse $data */
        $data = $serializer->deserialize($request->getContent(), ParseEmailResponse::class, 'json');

        if ($data->status && $data->email) {
            $options = new EmailOptions($data, false);
            $data->email = null;
            $emailLogger->info('request', ['worker' => 'businessEmail', 'requestData' => json_encode($data), 'businessId' => $businessId]);
            $result = $utilBusiness->processBusinessMessage($data, $options, $businessId);
            $utilBusiness->log("result: " . $result, $businessId, $data->userData);

            if ($result == UtilBusiness::SAVE_MESSAGE_SUCCESS) {
                $response = new Response('OK');
            } else {
                $response = new JsonResponse(['warnings' => [$result]]);
            }
        } else {
            $response = new Response('Missing data', 400);
        }

        return $response;
    }
}
