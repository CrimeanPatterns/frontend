<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusVIP1YearUpgrade;
use AwardWallet\MainBundle\Entity\QsTransaction;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\BalanceWatch\TransferOptions;
use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;
use AwardWallet\MainBundle\Service\User\StateNotification;
use AwardWallet\MainBundle\Validator\Constraints\AccountValidator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/manager/user")
 */
class AccountController extends AbstractController
{
    private TranslatorInterface $translator;
    private AccountValidator $accountValidator;

    public function __construct(
        TranslatorInterface $translator,
        AccountValidator $accountValidator
    ) {
        $this->translator = $translator;
        $this->accountValidator = $accountValidator;
    }

    /**
     * @Route("/account", name="aw_manager_user_account_index", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_ACCOUNT') or is_granted('ROLE_MANAGE_BALANCEWATCH')")
     * @Template("@AwardWalletMain/Manager/User/Account/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request)
    {
        $result = [];
        $page = (int) $request->get('page', 1);
        $page > 0 ?: $page = 1;
        $links = 5;
        $limit = 100;

        // $providerRepository = $this->getDoctrine()->getRepository(Provider::class);
        // $result['providers'] = $providerRepository->getSupportedProviders();

        $result['providerId'] = (int) $request->get('providerId', 0);
        $extEnabled = (int) $request->get('enabled', 0);
        $extDisabled = (int) $request->get('disabled', 0);

        $result['userId'] = $request->get('userId', 0);
        $result['accountId'] = $request->get('accountId', 0);

        $accountFields = 'AccountID, UserID, Login, Login2, Login3, Balance, UpdateLimitDisabledUntil, DisableExtension';

        if (!empty($result['userId'])) {
            $userId = array_map('trim', explode(',', $result['userId']));
            $userId = array_map('intval', $userId);
            $userId = array_unique($userId);

            empty($userId) ?:
                $result['account'] = $this->getDoctrine()->getConnection()->executeQuery('
                    SELECT ' . $accountFields . '
                    FROM Account a
                    WHERE UserID IN (' . implode(',', $userId) . ')
                ')->fetchAll();
        } elseif (!empty($result['accountId'])) {
            $accountId = array_map('trim', explode(',', $result['accountId']));
            $accountId = array_map('intval', $accountId);
            $accountId = array_unique($accountId);

            empty($accountId) ?:
                $result['account'] = $this->getDoctrine()->getConnection()->executeQuery('
                    SELECT ' . $accountFields . '
                    FROM Account a
                    WHERE AccountID IN (' . implode(',', $accountId) . ')
                ')->fetchAll();
        } else {
            $where = 'a.ProviderID = :providerId';

            if (empty($result['providerId'])) {
                $result['providerId'] = 0;
                $where = 'a.ProviderID <> :providerId';
                $extDisabled = 1;
            }

            if ($extEnabled || $extDisabled) {
                $where .= ' AND (';
                !$extEnabled ?: $where .= 'a.DisableExtension = 0';
                !$extDisabled ?: $where .= ($extEnabled ? ' OR ' : '') . 'a.DisableExtension = 1';
                $where .= ')';
            }

            $result['count'] = $this->getDoctrine()->getConnection()
                ->executeQuery('SELECT COUNT(*) FROM Account a WHERE ' . $where,
                    [':providerId' => $result['providerId']],
                    [\PDO::PARAM_INT])->fetchColumn();
            $result['account'] = $this->getDoctrine()->getConnection()->executeQuery('
                    SELECT ' . $accountFields . '
                    FROM Account a
                    WHERE ' . $where . '
                    LIMIT ' . ($limit * ($page - 1)) . ', ' . $limit,
                [':providerId' => $result['providerId']],
                [\PDO::PARAM_INT]
            )->fetchAll();

            $last = ceil($result['count'] / $limit);
            $start = (($page - $links) > 0) ? $page - $links : 1;
            $end = (($page + $links) < $last) ? $page + $links : $last;
            $result['pagination'] = ['start' => $start, 'end' => $end, 'last' => $last];
        }

        return $result;
    }

    /**
     * @Route("/account/disableextension", name="aw_manager_user_account_disableextension", methods={"POST"})
     * @Security("is_granted('ROLE_MANAGE_ACCOUNT')")
     * @return JsonResponse
     */
    public function disableExtension(Request $request)
    {
        $result = ['success' => false];
        $disable = $request->get('disable');
        $accountId = $request->get('accountId');

        if (null === $disable || null === $accountId) {
            return new JsonResponse($result);
        }

        $this->getDoctrine()->getConnection()
            ->executeQuery('UPDATE Account SET DisableExtension = ' . ((int) $disable ? 1 : 0) . ' WHERE AccountID = ' . (int) $accountId . ' LIMIT 1');

        $result['success'] = true;

        return new JsonResponse($result);
    }

    /**
     * @Route("/account/edit/{accountId}", name="aw_manager_user_account_edit", methods={"GET", "POST"}, requirements={"accountId"="\d+"})
     * @Security("is_granted('ROLE_MANAGE_BALANCEWATCH')")
     * @Template("@AwardWalletMain/Manager/User/Account/edit.html.twig")
     * @return array|RedirectResponse
     * @throws
     */
    public function editAction(
        Request $request,
        $accountId,
        TransferOptions $bwTransferOptions,
        BalanceWatchManager $balanceWatchManager,
        AuthorizationCheckerInterface $authorizationChecker,
        AccountRepository $accountRepository,
        ProviderRepository $providerRepository
    ) {
        /** @var Account $account */
        $account = $accountRepository->find($accountId);
        $isAllowBalanceWatch = $account->isAllowBalanceWatch() && !$account->isBalanceWatchDisabled() && $authorizationChecker->isGranted('ROLE_MANAGE_BALANCEWATCH');
        $balanceWatchRequired = $isAllowBalanceWatch ? $this->getBalanceWatchRequired($account) : [];

        if ($request->isMethod('POST')) {
            $limitDisabledUntil = $request->request->get('UpdateLimitDisabledUntil');
            $account->setUpdateLimitDisabledUntil(empty($limitDisabledUntil) ? null : new \DateTime($limitDisabledUntil));

            if (empty($balanceWatchRequired) && $request->request->get('balanceWatch') && $request->request->get('PointsSource') && $isAllowBalanceWatch) {
                $providerId = (int) $request->request->get('TransferFromProvider');
                $provider = empty($providerId) ? null : $providerRepository->find($providerId);

                $accountModel = (new AccountModel())
                    ->setUserid($account->getUser())
                    ->setEntity($account)
                    ->setPointsSource($request->request->get('PointsSource'))
                    ->setTransferFromProvider($provider)
                    ->setExpectedPoints(intval($request->request->get('ExpectedPoints')) ?? null)
                    ->setTransferRequestDate(new \DateTime('-' . ((int) $request->request->get('TransferRequestDate')) . ' hour'));

                $context = new ExecutionContext(Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator(),
                    $account,
                    $this->translator);

                if (true === $this->accountValidator->validateBalanceWatch($accountModel, $context)) {
                    $balanceWatchManager->startBalanceWatch($account, $accountModel, true);
                }
            }

            $this->getDoctrine()->getManager()->flush($account);

            return new RedirectResponse($this->generateUrl('aw_manager_user_account_index'));
        }

        return [
            'account' => $account,
            'isAllowBalanceWatch' => $isAllowBalanceWatch,
            'balanceWatch' => [
                'requestDateOptions' => $bwTransferOptions->get(),
            ],
            'balanceWatchRequired' => $balanceWatchRequired ?? [],
        ];
    }

    /**
     * Test Weekly Email Digest.
     *
     * @Route("/email/weekly", name="aw_manager_user_email_weekly", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function emailWeeklyAction(
        Request $request,
        EntityManagerInterface $entityManager,
        EmailNotificationNewPost $emailNotificationNewPost
    ) {
        $userId = $request->request->get('userId');

        if (!empty($userId)) {
            $staffId = $entityManager->getConnection()->fetchFirstColumn("
                SELECT u.UserID FROM Usr u
                JOIN GroupUserLink gul ON (u.UserID = gul.UserID)
                JOIN SiteGroup sg ON (gul.SiteGroupID = sg.SiteGroupID)
                WHERE sg.GroupName = 'staff'
            ");
            $userIds = explode(',', $userId);
            $userIds = array_map('trim', $userIds);
            $userIds = array_map('intval', $userIds);
            $userIds = array_filter(array_unique($userIds));

            $allowedId = array_intersect($userIds, $staffId);
            $result = false;

            if (!empty($allowedId)) {
                $date = new \DateTimeImmutable(date('Y-m-d'));
                $period = $request->request->get('period');
                $result = $emailNotificationNewPost->execute($period, $date, [
                    'userId' => implode(',', $allowedId),
                    'ignoreResend' => true,
                ]);
            }

            if (0 === $result) {
                echo 'Sent successfully: ' . implode(', ', $allowedId);
            } elseif (is_string($result)) {
                echo $result;
            } else {
                echo 'An error occurred';
            }

            echo '<hr>';
        }

        $html = '
        <form method="post">
            <input name="userId" type="text" value="" style="width:100%;" placeholder="UserID - separated comma (staff only)">
            <label><input type="radio" name="period" value="day"> day</label> <label><input type="radio" name="period" value="week" checked> week</label>
            <hr><button type="submit">Send Email</button>
        </form>
        ';

        exit($html);
    }

    /**
     * Test.
     *
     * @Route("/send-state", name="aw_manager_user_send_state")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function sendStateAction(
        Request $request,
        StateNotification $stateNotification
    ) {
        $stateNotification->sendState(Slack::CHANNEL_LOG_DEV);

        exit(date('r'));
    }

    /**
     * @Route("/vip-cards", name="aw_manager_users_vip_cards")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function usersVipCardsAction(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        /*
         SELECT u.UserID
            FROM Usr u
            INNER JOIN UserCreditCard ucc ON (
                    u.UserID = ucc.UserID
                AND ucc.DetectedViaQS = 1
                AND ucc.EarliestSeenDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
            )
         */

        $fileName = 'users-vip-early';

        if ($request->query->has('byCart')) {
            $fileName .= '--byCart';
            $users = $entityManager->getConnection()->fetchFirstColumn('
                SELECT c.UserID
                FROM Cart c
                INNER JOIN CartItem ci ON (ci.CartID = c.CartID AND ci.TypeID = ' . AwPlusVIP1YearUpgrade::TYPE . ')
            ');
        } elseif ($request->query->has('onlyUsersInGroup')) {
            $fileName .= '--onlyGroup';
            $users = $entityManager->getConnection()->fetchFirstColumn('
                SELECT qt.UserID
                FROM QsTransaction qt
                INNER JOIN GroupUserLink gul ON (gul.UserID = qt.UserID AND gul.SiteGroupID = ' . Sitegroup::GROUP_VIP_EARLY_SUPPORTER_ID . ') 
                WHERE
                        (Approvals > 0 AND Earnings > 0)
                    AND qt.ClickDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                    AND qt.Version = ' . QsTransaction::ACTUAL_VERSION . '
            ');
        } else {
            $users = $entityManager->getConnection()->fetchFirstColumn('
                SELECT UserID
                FROM QsTransaction qt
                WHERE
                        (Approvals > 0 AND Earnings > 0)
                    AND qt.ClickDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                    AND qt.Version = ' . QsTransaction::ACTUAL_VERSION . '
    
                UNION
                
                SELECT gul.UserID
                FROM GroupUserLink gul
                WHERE gul.SiteGroupID = ' . Sitegroup::GROUP_VIP_EARLY_SUPPORTER_ID . '
            ');
        }

        $cards = [];

        foreach ($users as $userId) {
            if (empty($userId)) {
                continue;
            }

            $count = (int) $entityManager->getConnection()->fetchOne('
                SELECT COUNT(*)
                FROM QsTransaction qt
                WHERE
                        qt.UserID = ' . $userId . '
                    AND (qt.Approvals > 0 AND qt.Earnings > 0)
                    AND qt.ClickDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                    AND qt.Version = ' . QsTransaction::ACTUAL_VERSION . '
            ');

            if (!array_key_exists($count, $cards)) {
                $cards[$count] = [];
            }

            $cards[$count][$userId] = 0;

            $sum = (float) $entityManager->getConnection()->fetchOne('
                SELECT SUM(Earnings)
                FROM QsTransaction qt
                WHERE
                        qt.UserID = ' . $userId . '
                    AND (qt.Approvals > 0 AND qt.Earnings > 0)
                    AND qt.ClickDate >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                    AND qt.Version = ' . QsTransaction::ACTUAL_VERSION . '
            ');

            $cards[$count][$userId] = $sum;
        }

        ksort($cards);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fileName . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Cards Opened',
            'Number of Users',
            'Value',
            '',
            'UserID',
        ]);

        $fulSum = 0;

        foreach ($cards as $count => $users) {
            $sum = array_sum($users);
            fputcsv($out, [
                $count,
                count($users),
                '$' . $sum,
                '',
                implode(', ', array_keys($users)),
            ]);

            $fulSum += $sum;
        }

        fputcsv($out, [
            'Total:',
            '',
            '$' . $fulSum,
            '',
            '',
        ]);

        fclose($out);

        exit;
    }

    private function getBalanceWatchRequired(Account $account): array
    {
        $balanceWatchRequired = [];

        if (empty($account->getProviderid()->getCancheck())
            || false === $account->getProviderid()->getCancheckbalance()
            || !in_array($account->getProviderid()->getState(), BalanceWatchManager::ALLOW_PROVIDER_STATE)
        ) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.not-available-not-cancheck');
        }

        if (0 >= $account->getUser()->getBalanceWatchCredits()) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.credits-no-available-notice');
        }

        if (!$account->getUser()->isAwPlus() && 0 >= $account->getUser()->getBalanceWatchCredits()) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.awplus-upgrade');
        }

        if ($account->isDisabled()) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.not-available-account-disabled');
        }

        if (ACCOUNT_CHECKED !== $account->getErrorcode()) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.not-available-account-error');
        }

        if (SAVE_PASSWORD_LOCALLY == $account->getSavePassword()
            && !in_array($account->getProviderid()->getProviderid(),
                BalanceWatchManager::EXCLUDED_PROVIDER_LOCAL_PASSWORD)) {
            $balanceWatchRequired[] = $this->translator->trans('account.balancewatch.not-available-password-local');
        }

        return $balanceWatchRequired;
    }
}
