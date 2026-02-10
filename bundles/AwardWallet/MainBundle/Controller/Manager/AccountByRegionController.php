<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\Builder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/manager/account-by-region")
 */
class AccountByRegionController extends AbstractController
{
    // TODO class and url should be renamed. Search is not only by provider with regions now
    public const TTL = 60 * 5; // 5 minutes

    protected Connection $connection;
    private Builder $builder;
    private EntityManagerInterface $em;
    private \Memcached $memcached;
    private TokenStorageInterface $tokenStorage;

    private $currentUserId;

    public function __construct(
        Connection $connection,
        Builder $builder,
        EntityManagerInterface $em,
        \Memcached $memcached,
        TokenStorageInterface $tokenStorage
    ) {
        $this->connection = $connection;
        $this->builder = $builder;
        $this->em = $em;
        $this->memcached = $memcached;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ACCOUNTBYREGION')")
     * @Route("", name="aw_manager_accountByRegion")
     */
    public function indexAction(Request $request)
    {
        $params = $this->parseQuery($request);

        $providers = $this->getProviders();
        $errors = $this->getErrorType();

        if (!empty($params['search'])) {
            $data = $this->getData($params);
        } else {
            $data = [];
        }

        if (isset($params['providerID']) && isset($providers['State'][$params['providerID']])) {
            $params['HideCheck'] = $providers['State'][$params['providerID']] === PROVIDER_CHECKING_EXTENSION_ONLY;
        }

        $response = $this->render('@AwardWalletMain/Manager/Support/Account/accountByRegion.html.twig', [
            "providerCode" => $providers['Code'],
            "providerName" => $providers['Name'],
            "errorCode" => $errors,
            "inputValue" => $params,
            "data" => $data['data'] ?? [],
            "dataCount" => $data['count'] ?? 0,
        ]);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_ACCOUNTBYREGION')")
     * @Route("/login", name="aw_manager_accountByRegion_login", methods={"POST"})
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        $providerId = $request->get('id');
        $renderTwigParam = $request->get('renderTwig');

        switch ($renderTwigParam) {
            case 'findItinerariesProvider':
                $renderTwig = '@AwardWalletMain/Manager/Support/Itinerary/findItinerariesProviderLogin.html.twig';

                break;

            default:
                $renderTwig = '@AwardWalletMain/Manager/Support/Account/accountByRegionLogin.html.twig';

                break;
        }

        if (!isset($providerId)) {
            return new JsonResponse(['data' => '', 'javascript' => null, 'message' => 'something went wrong']);
        }

        $this->currentUserId = $this->tokenStorage->getToken()->getUser()->getUserid();
        $usr = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['userid' => $this->currentUserId]);

        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['providerid' => $providerId]);

        $memData = $this->getTune($usr, $provider);

        if (is_bool($memData)) {
            return new JsonResponse(['data' => '', 'javascript' => null, 'message' => 'no Login2/Login3']);
        }
        $array = [
            'Login2' => [
                'Login2Caption' => $memData['Login2']['label'] ?? $provider->getLogin2caption(),
                'Login2choices' => $memData['Login2']['choices'] ?? null,
            ],
            'Login3' => [
                'Login3Caption' => $memData['Login3']['label'] ?? $provider->getLogin3caption(),
                'Login3choices' => $memData['Login3']['choices'] ?? null,
                'required' => $provider->isLogin3Required(),
            ],
        ];

        $data = [
            'data' => $this->render($renderTwig, $array)->getContent(),
            'javascript' => $memData['javascript'],
            'message' => 'ok',
        ];

        return new JsonResponse($data);
    }

    private function getErrorType()
    {
        global $arAccountErrorCode;

        $array = [];

        foreach ($arAccountErrorCode as $key => $value) {
            $array[] = ['ID' => $key, 0 => $key, 'Description' => $value, 1 => $value];
        }

        return $array;
    }

    private function getProviders()
    {
        $qb = $this->em->createQueryBuilder();

        $q = $qb->select(['p'])
            ->from(Provider::class, 'p')
            ->andWhere('p.state >= :stateLow')
            ->andWhere('p.state <> :stateExcl')
//            ->andWhere('p.login2Required = TRUE OR p.login3Required  = TRUE')
            ->setParameter('stateLow', PROVIDER_ENABLED)
            ->setParameter('stateExcl', PROVIDER_COLLECTING_ACCOUNTS)
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        $rows = $q->getResult();
        //        $this->currentUserId = $this->tokenStorage->getToken()->getUser()->getUserid();
        //        $usr = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['userid' => $this->currentUserId]);

        $array = [];

        foreach ($rows as $value) {
            /* uncomment if only with Regions
            $memData = $this->getTune($usr, $value);

            if (is_bool($memData)) {
                continue;
            }*/

            $array['Code'][] = ['ID' => $value->getProviderid(), 'Description' => $value->getCode()];
            $array['Name'][] = ['ID' => $value->getProviderid(), 'Description' => $value->getDisplayname()];
            $array['State'][$value->getProviderid()] = $value->getState();
        }

        return $array;
    }

    private function getTune(Usr $usr, Provider $provider)
    {
        //        $key = 'account_by_region' . md5($provider->getCode() . $usr->getId());
        $key = 'account_by_region_tune' . $provider->getCode();
        $memData = $this->memcached->get($key);

        if (empty($memData)) {
            $memData = [];
            $data = $this->builder->getFormTemplate($usr, $provider);

            if (!(isset($data->fields) && (isset($data->fields['Login2']) || isset($data->fields['Login3'])))) {
                return false;
            }

            $memData['Login2'] = $data->fields['Login2']['options'] ?? null;
            $memData['Login3'] = $data->fields['Login3']['options'] ?? null;
            $memData['javascript'] = $data->javaScripts['provider'] ?? null;
            $this->memcached->set($key, $memData, self::TTL);
        }

        return $memData;
    }

    private function parseQuery($request)
    {
        $params = [];
        $filters = ['providerID', 'errorCode', 'search', 'ignoreLogin'];

        if ($request->query->get('ignoreLogin') !== 'on') {
            array_push($filters, 'login2', 'login3');
        }

        foreach ($filters as $filter) {
            $params[$filter] = $request->query->get($filter, '');
        }

        // default 'errorCode'
        if ($params['errorCode'] === '') {
            $params['errorCode'] = ACCOUNT_CHECKED;
        }

        return $params;
    }

    /**
     * @return bool|array
     */
    private function getData($params)
    {
        $qb = $this->em->createQueryBuilder();
        $qbAgg = $this->em->createQueryBuilder();

        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['providerid' => $params['providerID']]);

        if ($provider->getPasswordrequired()) {
            $ext = '1<>1';
        } else {
            $ext = '1=1';
        }

        $qb->select([
            'a.successcheckdate as SuccessCheckDate',
            'a.updatedate as UpdateDate',
            'a.accountid as AccountID',
            'u.userid as UserID',
            'a.login as Login',
            'a.login2 as Login2',
            'a.login3 as Login3',
            'a.balance as Balance',
            'a.errorcode as ErrorCode',
            'a.errormessage as ErrorMessage',
        ])
            ->from(Account::class, 'a')
            ->innerJoin('a.user', 'u')
            ->where('a.providerid = :providerID')
            ->andWhere("(a.savepassword = :savepassword AND a.pass <> '') OR a.authInfo IS NOT NULL OR {$ext}")
            ->andWhere('a.state = :state')
            ->andWhere('a.disabled = :disabled')
            ->setParameter('providerID', $params['providerID'], \PDO::PARAM_INT)
            ->setParameter('savepassword', SAVE_PASSWORD_DATABASE, \PDO::PARAM_INT)
            ->setParameter('state', ACCOUNT_ENABLED, \PDO::PARAM_INT)
            ->setParameter('disabled', 0, \PDO::PARAM_INT)
            ->addOrderBy('a.successcheckdate', 'DESC')
            ->addOrderBy('a.balance', 'DESC')
            ->setMaxResults(100);
        $qbAgg
            ->select('count(a.accountid)')
            ->from(Account::class, 'a')
            ->innerJoin('a.user', 'u')
            ->where('a.providerid = :providerID')
            ->andWhere("(a.savepassword = :savepassword AND a.pass <> '') OR a.authInfo IS NOT NULL OR {$ext}")
            ->andWhere('a.state = :state')
            ->andWhere('a.disabled = :disabled')
            ->setParameter('providerID', $params['providerID'], \PDO::PARAM_INT)
            ->setParameter('savepassword', SAVE_PASSWORD_DATABASE, \PDO::PARAM_INT)
            ->setParameter('state', ACCOUNT_ENABLED, \PDO::PARAM_INT)
            ->setParameter('disabled', 0, \PDO::PARAM_INT);

        if (isset($params['login2']) && !empty($params['login2'])) {
            $qb
                ->andWhere('a.login2 = :login2')
                ->setParameter('login2', $params['login2'], \PDO::PARAM_STR);
            $qbAgg
                ->andWhere('a.login2 = :login2')
                ->setParameter('login2', $params['login2'], \PDO::PARAM_STR);
        }

        if (isset($params['login3']) && !empty($params['login3'])) {
            $qb
                ->andWhere('a.login3 = :login3')
                ->setParameter('login3', $params['login3'], \PDO::PARAM_STR);
            $qbAgg
                ->andWhere('a.login3 = :login3')
                ->setParameter('login3', $params['login3'], \PDO::PARAM_STR);
        }

        if (isset($params['errorCode']) && is_numeric($params['errorCode'])) {
            $qb
                ->andWhere('a.errorcode = :errorCode')
                ->setParameter('errorCode', $params['errorCode'], \PDO::PARAM_INT);
            $qbAgg
                ->andWhere('a.errorcode = :errorCode')
                ->setParameter('errorCode', $params['errorCode'], \PDO::PARAM_INT);
        }
        $count = $qbAgg->getQuery()->getSingleScalarResult();
        $q = $qb->getQuery();

        return ['data' => $q->getResult(), 'count' => $count];
    }
}
