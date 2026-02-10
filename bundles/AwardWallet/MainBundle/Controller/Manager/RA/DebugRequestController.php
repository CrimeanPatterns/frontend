<?php

namespace AwardWallet\MainBundle\Controller\Manager\RA;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/loyalty")
 */
class DebugRequestController extends AbstractController
{
    private ServiceLocator $loyaltyApiCommunicators;

    public function __construct(ServiceLocator $loyaltyApiCommunicators)
    {
        $this->loyaltyApiCommunicators = $loyaltyApiCommunicators;
    }

    /**
     * @Route("/ra-request", name="aw_manager_loyalty_ra_request")
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raDebugRequest(Request $httpRequest)
    {
        $data = [];

        if (strcasecmp('POST', $httpRequest->getMethod()) === 0) {
            $data['request'] = $httpRequest->request->all();
            $cluster = $httpRequest->request->get('cluster');
            /** @var ApiCommunicator $communicator */
            $communicator = $this->loyaltyApiCommunicators->get($cluster);
            $raRequest = [
                'provider' => $httpRequest->request->get('provider'),
                'departure' => [
                    'date' => $httpRequest->request->get('depDate'),
                    'airportCode' => $httpRequest->request->get('depCode'),
                ],
                'arrival' => $httpRequest->request->get('arrCode'),
                'cabin' => $httpRequest->request->get('cabin'),
                'passengers' => [
                    'adults' => is_numeric($adults = $httpRequest->request->get('adults')) ? intval($adults) : null,
                ],
                'currency' => $httpRequest->request->get('currency'),
                'userData' => json_encode(['accountKey' => $httpRequest->request->get('accountKey')]),
                'priority' => 5,
            ];

            try {
                $data['responseBody'] = $communicator->makeRaRequest($raRequest);
            } catch (ApiCommunicatorException $e) {
                $data['responseBody'] = sprintf('Error: %s', $e->getMessage());
            }
        }

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/ra-request.html.twig', $data);
    }
}
