<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\Counter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class ListController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/account/list/", host="%business_host%", name="aw_business_account_list", options={"expose"=true})
     * @Route("/account/list{params}", name="aw_business_account_list_html5", requirements={"params" = ".+"}, options={"expose"=false})
     * @Template("@AwardWalletMain/Business/List/list.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, Counter $counter, RouterInterface $router, \Memcached $memcached)
    {
        $cnt = $counter->getTotalAccounts($this->tokenStorage->getBusinessUser()->getUserid());

        if ($cnt == 0) {
            return $this->redirect($router->generate('aw_select_provider'));
        }

        $isPartial = $cnt > 300;

        $accountsData = $this->getAccountsData($isPartial, false, false);

        $memcached->delete('account_autocomplete_user_' . $this->tokenStorage->getBusinessUser()->getUserid());

        return [
            'accountsData' => $accountsData,
            'partial' => $isPartial,
            'total' => $cnt,
        ];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/account/data", host="%business_host%", name="aw_business_account_data", options={"expose"=true})
     * @return JsonResponse
     */
    public function dataAction(Request $request, RequestStack $requestStack)
    {
        $requests = json_decode($requestStack->getCurrentRequest()->getContent(), true);
        $ret = [];
        $userObject = $this->tokenStorage->getBusinessUser();

        foreach (is_array($requests) ? $requests : [] as $id => $request) {
            if ($request['dataset'] == 'accounts') {
                $optionsOld = $request['options'];
                $options = new Options();

                // TODO: filter valid options
                foreach ($optionsOld as $optionName => $optionLevel) {
                    $options->set($optionName, $optionLevel);
                }

                $accountList = $this->accountListManager
                    ->getAccountList(
                        $this->optionsFactory
                            ->createDesktopListOptions($options)
                            ->set(Options::OPTION_USER, $userObject)
                    );
                $accounts = $accountList->getAccounts();
                $total = $accountList->getMapperContext()->loaderContext->accountsCount;

                $ret[] = [
                    'id' => $id,
                    'result' => $accounts,
                    'total' => $total,
                ];
            } elseif ($request['dataset'] == 'agents') {
                $agents = $this->accountListManager->getBusinessAgents($userObject);

                $ret[] = [
                    'id' => $id,
                    'result' => $agents,
                ];
            } elseif ($request['dataset'] == 'kinds') {
                $kinds = $this->accountListManager->getProviderKindsInfo();

                $ret[] = [
                    'id' => $id,
                    'result' => $kinds,
                ];
            } elseif ($request['dataset'] == 'user') {
                $user = $this->accountListManager->getBusinessUserInfo($userObject);

                $ret[] = [
                    'id' => $id,
                    'result' => $user,
                ];
            }
        }

        return (new JsonResponse($ret))->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function getAccountsData($isPartial = false, $withUsers = true, $withProviders = false)
    {
        $accounts = [];
        $userObject = $this->tokenStorage->getBusinessUser();

        if (!$isPartial) {
            $accounts = $this->accountListManager
                ->getAccountList(
                    $this->optionsFactory
                        ->createDesktopListOptions()
                        ->set(Options::OPTION_USER, $userObject)
                        ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                )
                ->getAccounts();
        }

        $result = [
            'accounts' => $accounts,
            'kinds' => $this->accountListManager->getProviderKindsInfo(),
            'user' => \array_merge(
                $this->accountListManager->getUserInfo($userObject),
                ['awPlus' => true]
            ),
        ];

        if ($withUsers) {
            $result['agents'] = $this->accountListManager->getBusinessAgents($userObject);
        }

        return $result;
    }
}
