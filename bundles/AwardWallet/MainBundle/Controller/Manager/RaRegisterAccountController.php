<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Controller\Manager\RA\AccountsController;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use JsonSchema\Validator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/loyalty/ra-accounts")
 */
class RaRegisterAccountController extends AbstractController
{
    public const TIMEOUT = 30;

    private ServiceLocator $loyaltyApiCommunicators;
    private array $clustersList;

    public function __construct(
        ServiceLocator $loyaltyApiCommunicators
    ) {
        $this->loyaltyApiCommunicators = $loyaltyApiCommunicators;
        $this->clustersList = array_diff(array_keys($this->loyaltyApiCommunicators->getProvidedServices()), ['awardwallet']);
    }

    /**
     * @Route("/register", name="aw_manager_loyalty_ra_accounts_register")
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     */
    public function raRegisterAccountAction(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster') ?? 'juicymiles';
        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $providers = json_decode($communicator->getRaRegProvidersList(), true);

        return $this->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/ra-register.html.twig',
            [
                'providerList' => $providers['providers_list'],
                'cluster' => $cluster,
                'clustersList' => $this->clustersList,
            ]
        );
    }

    /**
     * @Route("/register/{provider}/request", name="aw_manager_loyalty_ra_accounts_register_provider_request", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     * @return JsonResponse
     */
    public function raRegisterAccountGetRequestAction(Request $httpRequest, $provider)
    {
        $cluster = $httpRequest->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $getData = json_decode($communicator->getRaRegProviderFields($provider), true)['request_data'] ?? ['fields' => []];

        $fields = [];

        if (isset($getData['fields'])) {
            foreach ($getData['fields'] as $key => $data) {
                $fields[$key] = in_array($data['Type'], ['string', 'date']) ? ($data['Value'] ?? "") : null;
            }
        }

        $data = [
            'provider' => $provider,
            'callbackUrl' => '',
            'userData' => '',
            'fields' => $fields,
        ];
        $response = new JsonResponse($data);
        $response->setEncodingOptions(JSON_FORCE_OBJECT);

        return $response;
    }

    /**
     * @Route("/register/{provider}/fields", name="aw_manager_loyalty_ra_accounts_register_provider_fields", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     * @return JsonResponse
     */
    public function raRegisterAccountGetFieldsAction(Request $httpRequest, $provider)
    {
        $cluster = $httpRequest->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $getData = json_decode($communicator->getRaRegProviderFields($provider), true)['request_data'] ?? ['fields' => []];
        $response = new JsonResponse($getData);
        $response->setEncodingOptions(JSON_FORCE_OBJECT);

        return $response;
    }

    /**
     * @Route("/register/request/{id}", name="aw_manager_loyalty_ra_accounts_register_get", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     * @return JsonResponse
     */
    public function raRegisterAccountGetAction(Request $httpRequest, $id)
    {
        $cluster = $httpRequest->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $getData = json_decode($communicator->getRaRegisterResult($id), true);

        return new JsonResponse($getData);
    }

    /**
     * @Route("/register/send", name="aw_manager_loyalty_ra_accounts_register_post", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     * @return JsonResponse
     */
    public function raRegisterAccountPostAction(Request $httpRequest)
    {
        $data = $httpRequest->request->get('data');
        $cluster = $httpRequest->request->get('cluster');

        if (!isset($cluster)) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $result = json_decode($communicator->sendRaRegister($data), true);

        return new JsonResponse($result);
    }

    /**
     * @Route("/register/auto", name="aw_manager_loyalty_ra_accounts_register_auto", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     */
    public function raRegisterAccountAutoAction(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster') ?? 'juicymiles';

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $resultMessage = $httpRequest->query->get('resultMessage', '');
        $providers = json_decode($communicator->getRaRegProvidersList(), true);
        $failRegistrations = json_decode($communicator->getReportOfFailRegistrations(), true);

        return $this->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/ra-register-auto.html.twig',
            [
                'providerList' => $providers['providers_list'],
                'report' => $failRegistrations['fail'] ?? $failRegistrations,
                'queue' => $failRegistrations['queue'] ?? [],
                'log' => $resultMessage,
                'clustersList' => $this->clustersList,
                'cluster' => $cluster,
                'states' => array_diff(AccountsController::getRaAccountStates(), [1 => 'Enabled']),
            ]
        );
    }

    /**
     * @Route("/register/auto-post", name="aw_manager_loyalty_ra_accounts_register_auto_post", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raRegisterAccountAutoPostAction(Request $httpRequest)
    {
        $data = $httpRequest->request->all();

        if (!isset($data['cluster'])) {
            throw new BadRequestHttpException('bad request');
        }

        $cluster = $data['cluster'];
        unset($data['cluster']);

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        if (isset($data['checked'])) {
            $response = $communicator->sendRaRegisterCheck($data['requestId']);
        } elseif (isset($data['retry'])) {
            $response = $communicator->sendRaRegisterRetry($data['requestId']);
        } else {
            $response = $communicator->sendRaRegisterAuto($data);
        }

        return $this->redirectToRoute('aw_manager_loyalty_ra_accounts_register_auto', ['resultMessage' => $response, 'cluster' => $cluster]);
    }

    /**
     * @Route("/register/auto-add-account", name="aw_manager_loyalty_ra_accounts_register_auto_add_account", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     * @return JsonResponse
     */
    public function raRegisterAccountAutoAddAccountAction(Request $httpRequest)
    {
        $data = $httpRequest->request->all();

        if (!isset($data['cluster'])) {
            throw new BadRequestHttpException('bad request');
        }

        $cluster = $data['cluster'];
        unset($data['cluster']);

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);
        $statesAcc = AccountsController::getRaAccountStates();
        $response = json_decode($communicator->sendRaRegisterAccountToDB($data['requestId'], $statesAcc[$data['state']]), true);

        if (isset($response['accountId'])) {
            $response['link'] = $this->generateUrl('aw_manager_loyalty_ra_accounts_edit', ['id' => $response['accountId'], 'cluster' => $cluster]);
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/register/config", name="aw_manager_loyalty_ra_accounts_register_config", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     */
    public function raRegisterAccountConfigAction(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster') ?? 'juicymiles';
        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $resultMessage = $httpRequest->query->get('resultMessage', '');
        $providers = json_decode($communicator->getRaRegProvidersList(), true);
        $configs = json_decode($communicator->getRaRegisterConfigList(), true);

        return $this->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/ra-register-config.html.twig',
            [
                'providerList' => $providers['providers_list'],
                'configs' => $configs,
                'log' => $resultMessage,
                'clustersList' => $this->clustersList,
                'cluster' => $cluster,
            ]
        );
    }

    /**
     * @Route("/register/config-post", name="aw_manager_loyalty_ra_accounts_register_config_post", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raRegisterAccountConfigPostAction(Request $httpRequest)
    {
        $data = $httpRequest->request->all();
        $request = (object) $data;

        $schemaObject = <<<'JSON'
        {
            "type": "object",
            "properties": {
                "action": { "type": "string" },
                "cluster": { "type": "string" },
                "id": { "type": "string" },
                "provider": { "type": "string" },
                "defaultEmail": { "type": "string" },
                "ruleForEmail": { "type": "string" },
                "minCountEnabled": { "type": "integer" },
                "minCountReserved": { "type": "integer" },
                "delay": { "type": "integer" },
                "isActive": { "type": "boolean" },
                "is2Fa": { "type": "boolean" },
            },
            "required": ["action", "cluster", "defaultEmail", "ruleForEmail", "minCountEnabled", "minCountReserved", "delay", "isActive", "is2Fa"],
            "definitions": {
                "minCountEnabled" : {
                    "type": "integer",
                    "minimum" : 0
                },
                "minCountReserved" : {
                    "type": "integer",
                    "minimum" : 0
                },
                "delay" : {
                    "type": "integer",
                    "minimum" : 0
                }
            }
        }
        JSON;
        $validator = new Validator();
        $validator->validate(
            $request,
            $schemaObject
        );

        $cluster = $data['cluster'];
        unset($data['cluster']);

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        if ($validator->isValid()) {
            if ($data['action'] === 'delete') {
                $response = $communicator->sendRaRegisterConfigDelete($data['id']);
            } elseif ($data['action'] === 'update') {
                $response = $communicator->sendRaRegisterConfigEdit($data['id'], $data);
            } elseif ($data['action'] === 'create') {
                $response = $communicator->sendRaRegisterConfigCreate($data);
            } else {
                throw new \Exception("unknown action: " . $data['action']);
            }
        }

        return $this->redirectToRoute('aw_manager_loyalty_ra_accounts_register_config', ['resultMessage' => $response ?? $data, 'cluster' => $cluster]);
    }

    /**
     * @Route("/queue/list", name="aw_manager_loyalty_ra_accounts_register_queue", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT_REGISTER')")
     */
    public function raQueueAction(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $data = $httpRequest->query->all();
        $queue = json_decode($communicator->getRaRegisterQueueList($data['provider']), true);

        foreach ($queue as $i => $row) {
            $queue[$i]['queueDate'] = date('d-m-Y H:i', (isset($row['queueDate'])) ? $row['queueDate']['sec'] : time());
            $queue[$i]['registerNotEarlierDate'] = date('d-m-Y H:i', (isset($row['registerNotEarlierDate'])) ? $row['registerNotEarlierDate']['sec'] : time());
        }

        return $this->render(
            '@AwardWalletMain/Manager/LoyaltyAdmin/ra-register-queue.html.twig',
            [
                'cluster' => $cluster,
                'queue' => $queue,
                'provider' => $data['provider'],
                'log' => $data['resultMessage'] ?? '',
            ]
        );
    }

    /**
     * @Route("/queue/delete", name="aw_manager_loyalty_ra_accounts_register_queue_delete", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raQueueDeleteAction(Request $httpRequest)
    {
        $cluster = $httpRequest->request->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $data = $httpRequest->request->all();
        $response = $communicator->sendRaRegisterQueueDelete($data['id']);

        return $this->redirectToRoute(
            'aw_manager_loyalty_ra_accounts_register_queue',
            [
                'provider' => $data['provider'],
                'resultMessage' => $response,
                'cluster' => $cluster,
            ]
        );
    }

    /**
     * @Route("/queue/clear", name="aw_manager_loyalty_ra_accounts_register_queue_clear", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raQueueClearAction(Request $httpRequest)
    {
        $cluster = $httpRequest->request->get('cluster');

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $data = $httpRequest->request->all();
        $response = $communicator->sendRaRegisterQueueClear($data['provider']);

        return $this->redirectToRoute(
            'aw_manager_loyalty_ra_accounts_register_queue',
            [
                'provider' => $data['provider'],
                'resultMessage' => $response,
                'cluster' => $cluster,
            ]
        );
    }
}
