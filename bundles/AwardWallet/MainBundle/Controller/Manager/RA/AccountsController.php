<?php

namespace AwardWallet\MainBundle\Controller\Manager\RA;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Service\CheckerFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/manager/loyalty")
 */
class AccountsController extends AbstractController
{
    private const TTL = 60 * 60 * 6; // 6 hours

    private ServiceLocator $loyaltyApiCommunicators;
    private array $clustersList;
    private CheckerFactory $checkerFactory;
    private \Memcached $memcached;

    public function __construct(ServiceLocator $loyaltyApiCommunicators, CheckerFactory $checkerFactory, \Memcached $memcached)
    {
        $this->loyaltyApiCommunicators = $loyaltyApiCommunicators;
        $this->clustersList = array_diff(array_keys($this->loyaltyApiCommunicators->getProvidedServices()), ['awardwallet']);
        $this->checkerFactory = $checkerFactory;
        $this->memcached = $memcached;
    }

    /**
     * @Route("/ra-accounts", name="aw_manager_loyalty_ra_accounts")
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raAccounts(Request $httpRequest)
    {
        $cluster = $httpRequest->get('cluster') ?? $httpRequest->request->get('form')['cluster'] ?? 'juicymiles';
        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        if (strcasecmp('POST', $httpRequest->getMethod()) === 0) {
            $ids = $httpRequest->request->get('ids');
            $method = $httpRequest->request->get('method');
            $ids = array_map('trim', explode(' ', $ids));
            $communicator->bulkRaAccountAction($ids, $method);

            return $this->redirectToRoute('aw_manager_loyalty_ra_accounts');
        }

        $data = json_decode($communicator->listRaAccount(), true);
        $data['providers'] = array_unique(array_map(function ($row) {return $row['provider']; }, $data['rows']));
        $data['errors'] = array_unique(array_map(function ($row) {return $row['code']; }, $data['rows']));
        $data['states'] = self::getRaAccountStates();
        $data['cluster'] = $cluster;
        $data['clustersList'] = $this->clustersList;

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/ra-accounts.html.twig', $data);
    }

    /**
     * @Route("/ra-accounts/{id}", name="aw_manager_loyalty_ra_accounts_edit", requirements={"id"="new|[a-z\d]{24}"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function raAccountsEdit(Request $httpRequest, $id)
    {
        $cluster = $httpRequest->get('cluster') ?? $httpRequest->request->get('form')['cluster'];

        if (!$cluster) {
            throw new BadRequestHttpException('bad request');
        }
        /** @var ApiCommunicator $communicator */
        $communicator = $this->loyaltyApiCommunicators->get($cluster);

        if (strcasecmp('POST', $httpRequest->getMethod()) === 0) {
            $query = $httpRequest->request->all();
            $request = array_intersect_key($query, [
                'provider' => '',
                'login' => '',
                'login2' => '',
                'login3' => '',
                'email' => '',
                'password' => '',
                'state' => '',
                'answers' => [],
                'reset' => '',
            ]);

            for ($i = 0; $i < 5; $i++) {
                $request['answers'][] = ['question' => $httpRequest->request->get('question' . $i),
                    'answer' => $httpRequest->request->get('answer' . $i)];
            }

            for ($i = 0; $i < 20; $i++) {
                if (null !== $httpRequest->request->get('key' . $i)) {
                    $request['registerInfo'][] = [
                        'key' => $httpRequest->request->get('key' . $i),
                        'value' => $httpRequest->request->get('value' . $i),
                    ];
                }
            }
            $response = json_decode($communicator->editRaAccount($id, $request), true);

            if (isset($response['data']) && isset($response['error'])) {
                $answers = $response['data']['answers'];
                $response['data']['answers'] = [];

                foreach ($answers as $answer) {
                    $response['data']['answers'][] = [$answer['question'], $answer['answer']];
                }
                $response['data']['id'] = $id !== 'new' ? $id : '';

                return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/ra-accounts-edit.html.twig',
                    ['account' => $response['data'],
                        'providers' => $this->getRaProviders($response['data']['provider']),
                        'states' => self::getRaAccountStates(),
                        'error' => $response['error'],
                        'cluster' => $cluster,
                    ]);
            }

            return $this->redirectToRoute('aw_manager_loyalty_ra_accounts', ['cluster' => $cluster]);
        }

        if ($id === 'new') {
            $account = json_decode(json_encode([
                'id' => '',
                'provider' => '',
                'login' => '',
                'login2' => '',
                'login3' => '',
                'email' => '',
                'password' => '',
                'answers' => [],
                'state' => '',
            ]));
        } else {
            $accs = json_decode($communicator->listRaAccount($id));

            if (count($accs->rows) !== 1) {
                return $this->redirectToRoute('aw_manager_loyalty_ra_accounts', ['cluster' => $cluster]);
            }
            $account = $accs->rows[0];
        }

        for ($i = 0; $i < 5; $i++) {
            if (!isset($account->answers[$i])) {
                $account->answers[$i] = ['', ''];
            }
        }

        if (empty($account->registerInfo)) {
            for ($i = 0; $i < 20; $i++) {
                if (!isset($account->registerInfo[$i])) {
                    $account->registerInfo[$i] = ['', ''];
                }
            }
        }
        $providers = $this->getRaProviders($account->provider);
        $data = ['providers' => $providers, 'states' => self::getRaAccountStates()];
        $data['account'] = $account;
        $data['cluster'] = $cluster;

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/ra-accounts-edit.html.twig', $data);
    }

    /**
     * @Route("/ra-accounts/links", name="aw_manager_loyalty_ra_accounts_link", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_RA_ACCOUNT')")
     */
    public function getRASearchLinks(Request $request)
    {
        $provider = $request->get('provider');

        if (empty($provider)) {
            return new Response('');
        }
        $key = 'ra_accounts_search_link_' . $provider;
        $memData = $this->memcached->get($key);

        if (empty($memData)) {
            $checker = $this->checkerFactory->getRewardAvailabilityChecker($provider);
            $memData = $checker::getRASearchLinks();

            if (!empty($memData) && is_array($memData)) {
                $this->memcached->set($key, $memData, self::TTL);
            } else {
                $memData = [];
            }
        }

        return $this->render('@AwardWalletMain/Manager/LoyaltyAdmin/ra-search-links.html.twig', ['data' => $memData]);
    }

    public static function getRaAccountStates()
    {
        return [
            -3 => 'Inactive',
            -2 => 'Locked',
            -1 => 'Debug',
            0 => 'Disabled',
            1 => 'Enabled',
            2 => 'Reserved',
        ];
    }

    private function getRaProviders($insert)
    {
        $providers = array_map(function ($s) {return basename(dirname($s)); }, glob(__DIR__ . '/../../../../../../engine/*/RewardAvailability'));

        if (!in_array($insert, $providers)) {
            $providers[] = $insert;
        }
        sort($providers);

        return $providers;
    }
}
