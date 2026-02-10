<?php

namespace AwardWallet\MainBundle\Controller\Manager;

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
 * @Route ("/manager/loyalty/hot-session")
 */
class HotSessionStateController extends AbstractController
{
    private ServiceLocator $loyaltyApiCommunicators;
    private array $clustersList;

    public function __construct(ServiceLocator $loyaltyApiCommunicators)
    {
        $this->loyaltyApiCommunicators = $loyaltyApiCommunicators;
        $this->clustersList = array_diff(array_keys($this->loyaltyApiCommunicators->getProvidedServices()), ['awardwallet']);
    }

    /**
     * @Route("/", name="aw_manager_loyalty_hot_session")
     * @Security("is_granted('ROLE_MANAGE_RA_HOT_SESSION')")
     */
    public function indexAction(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster') ?? 'juicymiles';

        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $list = json_decode($communicator->getListHotSession(), true);
        $list = array_map(function ($s) {
            if (!isset($s['accountKey'])) {
                $s['accountKey'] = '';
            }

            return $s;
        }, $list);
        $resultMessage = $httpRequest->query->get('resultMessage', '');
        $providers = array_unique(array_map(function ($s) {
            return $s['provider'];
        }, $list));
        sort($providers);

        return $this->render(
            '@AwardWalletMain/Manager/Support/RewardAvailability/hotSessionStateManager.html.twig',
            [
                'clustersList' => $this->clustersList,
                'cluster' => $cluster,
                'res' => $resultMessage,
                'list' => $list,
                'providers' => $providers,
            ]
        );
    }

    /**
     * @Route("/stop", name="aw_manager_loyalty_hot_session_stop", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_HOT_SESSION')")
     */
    public function stopSessionAction(Request $httpRequest)
    {
        $data = $httpRequest->request->all();

        if (!isset($data['cluster'])) {
            throw new BadRequestHttpException('bad request');
        }

        $cluster = $data['cluster'];
        unset($data['cluster']);

        $request = (object) $data;

        $schemaObject = <<<'JSON'
        {
            "id": "string"
        }
        JSON;
        $validator = new Validator();
        $validator->validate(
            $request,
            $schemaObject
        );

        if (!$validator->isValid()) {
            throw new BadRequestHttpException("check format request");
        }
        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        $response = $communicator->sendListToStopHotSessions($data);

        return new JsonResponse($response);
    }
}
