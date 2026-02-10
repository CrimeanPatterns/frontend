<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** @Route("/manager/extension-parsing") */
class RaExtensionParsingController extends AbstractController
{
    private SocksClient $messaging;
    private \Memcached $memcached;
    private LoggerInterface $logger;

    public function __construct(
        SocksClient $messaging,
        \Memcached $memcached,
        LoggerInterface $logger
    ) {
        $this->messaging = $messaging;
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    /**
     * @Route("", name="aw_manager_ra_extension_parsing", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     */
    public function managerPageAction(Request $httpRequest): Response
    {
        return $this->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/ra-extension-parsing.html.twig',
            [
                'log' => json_encode($httpRequest->query->get('log', '')),
                'centrifuge_config' => json_encode($this->messaging->getClientData()),
            ]
        );
    }

    /**
     * @Route("/request", name="aw_manager_ra_extension_parsing_request", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     */
    public function parsingRequestAction(Request $httpRequest): Response
    {
        $data = $this->getDataForWS(
            $httpRequest->request->all()
        );

        $this->logger->info("sending request:\n " . var_export($data, true));
        $this->messaging->publish('ra_extension_parsing', $data);

        $json = $this->getResponseFromCache($data['message']['cacheKey']);

        return $this->redirectToRoute(
            'aw_manager_ra_extension_parsing',
            [
                'log' => $json,
            ]
        );
    }

    /**
     * @Route("/response", name="aw_manager_ra_extension_parsing_response", methods={"POST"})
     */
    public function parsingResponseAction(Request $httpRequest): Response
    {
        $data = json_decode($httpRequest->getContent(), true);
        $this->logger->info('Check response from extension: ' . var_export($data, true));

        $cacheKey = $data['cacheKey'];
        unset($data['cacheKey']);

        $isStored = $this->memcached->add($cacheKey, json_encode($data), 30);

        if (!$isStored) {
            throw new \Exception('Duplicated cache key, data loss possible');
        }

        return new Response(json_encode(['data' => $data, 'cacheKey' => $cacheKey]), 201);
    }

    private function getDataForWS(array $fields): array
    {
        $request = $fields['request'];
        unset($fields['request']);

        $data = [
            'type' => $request,
            'message' => $fields,
        ];

        $data['message']['cacheKey'] = $request . '_' . hash('sha256', $request . time(), false);

        return $data;
    }

    private function getResponseFromCache(string $cacheKey, int $timeOut = 10): ?string
    {
        $this->logger->info("waiting response");

        $result = null;

        while ($timeOut) {
            $result = $this->memcached->get($cacheKey);

            if ($result) {
                break;
            }

            sleep(1);
            $timeOut--;
        }

        return $result;
    }
}
