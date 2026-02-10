<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\PopularityHandler;
use AwardWallet\WidgetBundle\Widget\AccountsAddMoreMenuWidget;
use AwardWallet\WidgetBundle\Widget\AccountsPersonsWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AddController extends AbstractController
{
    private EntityManagerInterface $em;
    private LocalizeService $localizer;
    private AccountsAddMoreMenuWidget $addMoreMenu;
    private AccountsPersonsWidget $accountsMenu;
    private PopularityHandler $popularityHandler;

    public function __construct(
        EntityManagerInterface $em,
        LocalizeService $localizer,
        AccountsAddMoreMenuWidget $addMoreMenu,
        AccountsPersonsWidget $accountsMenu,
        PopularityHandler $popularityHandler
    ) {
        $this->em = $em;
        $this->localizer = $localizer;
        $this->addMoreMenu = $addMoreMenu;
        $this->accountsMenu = $accountsMenu;
        $this->popularityHandler = $popularityHandler;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/select-provider", name="aw_select_provider", options={"expose"=true})
     * @Template("@AwardWalletMain/Account/Add/add.html.twig")
     * @return array
     */
    public function addAction(Request $request, AwTokenStorageInterface $tokenStorage, PageVisitLogger $pageVisitLogger)
    {
        $this->addMoreMenu->hide();

        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->em->getRepository(Account::class)->getPendingsQuery($tokenStorage->getBusinessUser(), true);
        $discoveredAcconts = array_map(function (Account $account) {
            $balance = $account->getBalance();

            return [
                'accountId' => $account->getAccountid(),
                'provider' => $account->getProviderid()->getDisplayname(),
                'login' => $account->getLogin() ? $account->getLogin() : $account->getAccountNumber(),
                'fullName' => $account->getAccountPropertyByKind(PROPERTY_KIND_NAME), // $account->getUser()->getFullName(),
                'balance' => $balance === null ? "n/a" : $this->localizer->formatNumber($account->getBalance()),
                'email' => $account->getSourceEmail(),
                'isCoupon' => false,
                'useragentid' => 'my',
            ];
        }, $query->getQuery()->getResult());
        $pageVisitLogger->log(PageVisitLogger::PAGE_ACCOUNT_LIST);

        return [
            'agents' => $this->accountsMenu->count(),
            'providers' => $this->popularityHandler->getPopularPrograms(
                $tokenStorage->getBusinessUser(),
                '',
                'ORDER BY Popularity DESC, p.Accounts DESC',
                null,
                true
            ),
            'agentId' => $request->query->get('agentId', 'my'),
            'discoveredAcconts' => $discoveredAcconts,
        ];
    }
}
