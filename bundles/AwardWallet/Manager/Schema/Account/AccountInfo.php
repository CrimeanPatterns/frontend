<?php

namespace AwardWallet\Manager\Schema\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\PageRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class AccountInfo extends AbstractEnhancedSchema implements ActionInterface
{
    private const ACCOUNT_STATES = [
        ACCOUNT_ENABLED => 'Enabled',
        ACCOUNT_DISABLED => 'Disabled',
        ACCOUNT_PENDING => 'Pending',
        ACCOUNT_IGNORED => 'Ignored',
    ];

    private const SAVE_PASS = [
        SAVE_PASSWORD_DATABASE => 'Db',
        SAVE_PASSWORD_LOCALLY => 'Local',
    ];

    private const EXPIRATION_AUTO_SET = [
        EXPIRATION_USER => 'User',
        EXPIRATION_UNKNOWN => 'Unknown',
        EXPIRATION_AUTO => 'Auto',
        EXPIRATION_FROM_SUBACCOUNT => 'Subaccount',
    ];

    private const DISABLE_REASON = [
        Account::DISABLE_REASON_USER => 'User',
        Account::DISABLE_REASON_PREVENT_LOCKOUT => 'Prevent Lockout',
        Account::DISABLE_REASON_PROVIDER_ERROR => 'Provider Error',
        Account::DISABLE_REASON_ENGINE_ERROR => 'Engine Error',
        Account::DISABLE_REASON_LOCKOUT => 'Lockout',
    ];

    private EntityManagerInterface $em;

    private Environment $twig;

    public function __construct(EntityManagerInterface $em, Environment $twig)
    {
        global $arProviderKind, $arAccountErrorCode;

        parent::__construct();

        $this->em = $em;
        $this->twig = $twig;
        $this->TableName = 'Account';
        $this->ListClass = AccountInfoList::class;
        $this->Fields = [
            'AccountID' => [
                'Caption' => 'ID',
                'Type' => 'integer',
                'filterWidth' => 30,
            ],
            'UserID' => [
                'Type' => 'integer',
            ],
            'ProviderID' => [
                'Type' => 'integer',
                'Options' => SQLToArray("SELECT ProviderID, ShortName FROM Provider ORDER BY ShortName", 'ProviderID', 'ShortName'),
            ],
            'Kind' => [
                'Type' => 'integer',
                'Options' => $arProviderKind,
            ],
            'State' => [
                'Type' => 'integer',
                'Options' => self::ACCOUNT_STATES,
            ],
            'Disabled' => [
                'Type' => 'boolean',
            ],
            'ErrorCode' => [
                'Type' => 'integer',
                'Options' => $arAccountErrorCode,
            ],
            'Balance' => [
                'Type' => 'float',
            ],
            'ExpirationDate' => [
                'Type' => 'date',
            ],
            'ExpirationAutoSet' => [
                'Type' => 'string',
                'Options' => self::EXPIRATION_AUTO_SET,
            ],
            'CreationDate' => [
                'Type' => 'datetime',
            ],
            'UpdateDate' => [
                'Type' => 'datetime',
            ],
            'LastChangeDate' => [
                'Type' => 'datetime',
                'Caption' => 'Last Change Balance',
            ],
            'SuccessCheckDate' => [
                'Type' => 'datetime',
            ],
            'LastCheckItDate' => [
                'Type' => 'datetime',
                'Caption' => 'Last Check It',
            ],
            'LastCheckPastItsDate' => [
                'Type' => 'datetime',
                'Caption' => 'Last Check Past Its',
            ],
            'LastCheckHistoryDate' => [
                'Type' => 'datetime',
                'Caption' => 'Last Check History',
            ],
            'EmailParseDate' => [
                'Type' => 'datetime',
            ],
            'CheckedBy' => [
                'Type' => 'integer',
                'Options' => Account::CHECKED_BY_NAMES,
            ],
            'SubAccounts' => [
                'Type' => 'integer',
                'Caption' => 'Subs',
            ],
            'Itineraries' => [
                'Type' => 'integer',
                'Caption' => 'Its',
            ],
            'BackgroundCheck' => [
                'Type' => 'boolean',
            ],
            'SavePassword' => [
                'Type' => 'integer',
                'Options' => self::SAVE_PASS,
            ],
            'DisableDate' => [
                'Type' => 'datetime',
            ],
            'DisableReason' => [
                'Type' => 'string',
                'Options' => self::DISABLE_REASON,
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $list->PageSize = 100;
        $list->CanAdd = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = false;
        $list->ReadOnly = true;
        $list->SQL = "
            SELECT
                t.AccountID,
                t.UserID,
                t.UserAgentID,
                IF(u.AccountLevel = 3, u.Company, CONCAT(u.FirstName, ' ', u.LastName)) AS UserName,
                IF(ua.UserAgentID IS NOT NULL, CONCAT(ua.FirstName, ' ', ua.LastName), NULL) AS FamilyMemberName,
                t.ProviderID,
                t.State,
                t.ErrorCode,
                t.ErrorMessage,
                t.Balance,
                t.SavePassword,
                t.ExpirationDate,
                t.Kind,
                t.ExpirationAutoSet,
                t.CreationDate,
                t.UpdateDate,
                t.ProgramName,
                t.LastBalance,
                t.LastChangeDate,
                t.SubAccounts,
                t.SuccessCheckDate,
                t.CheckedBy,
                t.Itineraries,
                t.Disabled,
                t.DisableDate,
                t.DisableReason,
                t.BackgroundCheck,
                t.LastCheckItDate,
                t.LastCheckPastItsDate,
                t.LastCheckHistoryDate,
                t.EmailParseDate,
                p.ShortName
            FROM
                Account t
                LEFT JOIN Usr u ON u.UserID = t.UserID
                LEFT JOIN UserAgent ua ON ua.UserAgentID = t.UserAgentID
                LEFT JOIN Provider p ON p.ProviderID = t.ProviderID
            WHERE 
                1 = 1
                [Filters]
        ";
    }

    public static function getSchema(): string
    {
        return 'AccountInfo';
    }

    public static function getSchemaName(string $className): string
    {
        return 'AccountInfo';
    }

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response
    {
        global $arProviderKind, $arAccountErrorCode, $arPropertiesKinds;

        if ($actionName !== 'info') {
            throw new NotFoundHttpException();
        }

        $accountId = $request->get('id');

        if (empty($accountId) || !is_numeric($accountId)) {
            throw new BadRequestHttpException('id is required');
        }

        /** @var Account $account */
        $account = $this->em->find(Account::class, $accountId);

        if (is_null($account)) {
            throw new NotFoundHttpException();
        }

        return $renderer->render([
            'title' => sprintf('Account #%d Details', $accountId),
            'content' => $this->twig->render('@ManagerSchema/Account/accountInfo.html.twig', [
                'account' => $account,
                'states' => self::ACCOUNT_STATES,
                'kinds' => $arProviderKind,
                'errorCodes' => $arAccountErrorCode,
                'savePass' => self::SAVE_PASS,
                'expirationAutoSet' => self::EXPIRATION_AUTO_SET,
                'checkedBy' => Account::CHECKED_BY_NAMES,
                'disableReasons' => self::DISABLE_REASON,
                'propsKinds' => $arPropertiesKinds,
                'propsTypes' => Providerproperty::TYPE_NAMES,
            ]),
        ]);
    }
}
