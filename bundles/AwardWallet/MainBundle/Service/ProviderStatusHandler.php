<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Controller\ContactUsController;
use AwardWallet\MainBundle\Entity\Providervote;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderStatusHandler
{
    /* @var Usr */
    private $user;

    private EntityManagerInterface $em;

    private TranslatorInterface $translator;

    private Mailer $mailer;

    private LoggerInterface $logger;

    private DateTimeIntervalFormatter $intervalFormatter;

    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        Mailer $mailer,
        LoggerInterface $logger,
        DateTimeIntervalFormatter $intervalFormatter,
        RequestStack $requestStack
    ) {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->intervalFormatter = $intervalFormatter;
        $this->requestStack = $requestStack;
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getBroken()
    {
        return $this->fetchList(['p.State = ' . PROVIDER_FIXING]);
    }

    public function getConsideringAdd()
    {
        return $this->fetchList(['(p.State = ' . PROVIDER_IN_DEVELOPMENT . ' or p.CollectingRequests)']);
    }

    public function getCannotAdd()
    {
        return $this->fetchList(['(p.State IN (' . implode(',', [PROVIDER_DISABLED]) . ') and p.CollectingRequests = 0)']);
    }

    public function getWorking()
    {
        return $this->fetchList(['p.State IN (' . implode(',', [PROVIDER_ENABLED, PROVIDER_COLLECTING_ACCOUNTS, PROVIDER_CHECKING_OFF, PROVIDER_CHECKING_WITH_MAILBOX, PROVIDER_CHECKING_EXTENSION_ONLY]) . ')'], ['p.Kind ASC']);
    }

    /**
     * Determine whether the user voted.
     *
     * @param int $providerId
     * @return bool
     */
    public function isVoted($providerId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('pv')
            ->from(Providervote::class, 'pv')
            ->where('pv.providerid = :providerId')
            ->andWhere('pv.userid     = :userId')
            ->setParameter('providerId', $providerId, \PDO::PARAM_INT)
            ->setParameter('userId', $this->user->getId(), \PDO::PARAM_INT);

        $res = $qb->getQuery()->getArrayResult();

        return count($res) > 0;
    }

    /**
     * Record voted, send emails.
     *
     * @param int $providerId
     * @param string  $comment
     * @return bool
     */
    public function vote($providerId, $comment, string $additionalData)
    {
        $providerId = (int) $providerId;

        if (!$providerId) {
            return false;
        }

        $is = $this->em->getConnection()->executeQuery('INSERT INTO ProviderVote (ProviderID, UserID) VALUES (:providerId, :userId)', [
            ':providerId' => $providerId,
            ':userId' => $this->user->getUserid(),
        ], [\PDO::PARAM_INT, \PDO::PARAM_INT]);

        if ($is) {
            return $this->mailSend($providerId, $comment, $additionalData);
        }

        return false;
    }

    /**
     * Search provider account and error.
     *
     * @param int $providerId
     * @return array
     */
    public function fetchAccounts($providerId)
    {
        $columns = "a.AccountID, a.ErrorMessage, a.State, a.ErrorCount, a.UserAgentID, DATE_FORMAT(DATE(a.UpdateDate),'%e %M %Y, %H:%i') AS UpdateDate, UNIX_TIMESTAMP(a.UpdateDate) AS _UpdateDate_ts,
                    p.ProviderID, p.State, COALESCE(p.DisplayName, p.ShortName, a.ProgramName, p.Name) AS DisplayName,
                    a.UserID,
                    COALESCE(
                        CONCAT(TRIM(concat(ua.FirstName, ' ', COALESCE(ua.MidName, ''))), ' ', ua.LastName),
                        CASE
                            WHEN u.AccountLevel = 3
                            THEN u.Company
                            ELSE CONCAT(TRIM(concat(u.FirstName, ' ', COALESCE(u.MidName, ''))), ' ', u.LastName)
                        END ) AS UserName,
                    COALESCE(p.Kind, a.Kind, 1) AS Kind, p.Engine AS ProviderEngine";
        $stmt = $this->em->getConnection()->executeQuery('
            SELECT
                    ' . $columns . '
            FROM    Account a
                    JOIN Usr u
                        ON a.UserID = u.UserID
                    LEFT OUTER JOIN Provider p
                        ON a.ProviderID = p.ProviderID
                    LEFT OUTER JOIN UserAgent ua
                        ON a.UserAgentID = ua.UserAgentID
            WHERE
                        a.UserID     = :userId
                    AND p.ProviderID = :providerId
                    AND a.State      > 0
                    AND (p.State     > 0 OR p.State IS NULL OR p.State = ' . PROVIDER_IN_BETA . ' OR p.State = ' . PROVIDER_TEST . ')
                    -- AND a.ErrorMessage <> \'\'
            UNION 
            SELECT
                    ' . $columns . '
            FROM    Account a
                    JOIN Usr u 
                        ON a.UserID = u.UserID
                    JOIN AccountShare ash
                        ON a.AccountID = ash.AccountID
                    JOIN UserAgent ua 
                        ON ash.UserAgentID = ua.UserAgentID
                    JOIN UserAgent au 
                        ON au.AgentID = ua.ClientID AND ua.AgentID = au.ClientID AND au.IsApproved = 1
                    LEFT OUTER JOIN Provider p 
                        ON a.ProviderID = p.ProviderID
                    LEFT OUTER JOIN UserAgent fm 
                        ON a.UserAgentID = fm.UserAgentID
            WHERE
                        p.ProviderID = :providerId
                    AND ua.AgentID   = :userId
                    AND a.State      > 0
                    AND (p.State     > 0 OR p.State IS NULL OR p.State = ' . PROVIDER_IN_BETA . ' OR p.State = ' . PROVIDER_TEST . ')
                    -- AND a.ErrorMessage <> \'\'
        ', [
            ':userId' => $this->user->getUserid(),
            ':providerId' => $providerId,
        ], [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProviderMessage($providerId)
    {
        $result = [];
        $accounts = $this->fetchAccounts($providerId);

        if (empty($accounts)) {// User does not have any accounts
            $result['title'] = $this->translator->trans('status.error-occurred');
            $result['message'] = $this->translator->trans('status.not-detect-accounts');
            $result['disableButtons'] = true;
        } else {
            $successRate = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->getSuccessRateProvider($providerId);
            empty($successRate) ? $successRate = '0%' : $successRate .= '%';

            $result['accounts'] = [];

            for ($i = 0, $iCount = count($accounts); $i < $iCount; $i++) {
                if (!empty($accounts[$i]['ErrorMessage'])) {
                    $result['accounts'][] = [
                        'AccountID' => $accounts[$i]['AccountID'],
                        'UserName' => $accounts[$i]['UserName'],
                        'ErrorMessage' => $accounts[$i]['ErrorMessage'],
                        '_lastUpdate' => $this->intervalFormatter->shortFormatViaDateTimes(
                            new \DateTime(),
                            new \DateTime('@' . $accounts[$i]['_UpdateDate_ts'])
                        ),
                    ];
                }
            }

            $result['title'] = $this->translator->trans('status.report-error');
            $result['providerSuccessRate'] = $successRate;
            $result['showComment'] = true;

            if (empty($result['accounts'])) {// No errors found for accounts found
                $result['message'] = $this->translator->trans('status.not-seeing-accounts');
            } else {
                $result['message'] = $this->translator->trans('status.issues-program');
            }
        }

        return $result;
    }

    /**
     * @param int $providerId
     * @return string
     */
    public function getReport($providerId)
    {
        $successRate = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->getSuccessRateProvider($providerId);
        empty($successRate) ? $successRate = '0%' : $successRate .= '%';

        $accounts = $this->fetchAccounts($providerId);

        if (empty($accounts)) {
            $data[] = 'AccountID: N/A';
            $data[] = 'Error shown to user: (user does not have this program added to his or her profile)';
        } else {
            $noError = [];

            for ($i = 0, $iCount = count($accounts); $i < $iCount; $i++) {
                $accountIdData = "AccountID: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?AccountID={$accounts[$i]['AccountID']}'>{$accounts[$i]['AccountID']}</a>";

                if (!empty($accounts[$i]['ErrorMessage'])) {
                    $data[] = $accountIdData;
                    $data[] = 'Error shown to user: ' . $accounts[$i]['ErrorMessage'];
                } else {
                    $noError[] = $accountIdData;
                }
            }

            if (!empty($noError)) {
                $data[] = "<br/>AccountIDs without error: " . implode(', ', $noError) . "<br/>";
            }
        }

        $data[] = "<br/>Success Rate: " . $successRate;

        return implode("<br/>", $data);
    }

    public function setUserExtensionVersion($extensionVersion): Usr
    {
        $version = null;

        if (is_string($extensionVersion)) {
            $data = json_decode($extensionVersion, true);

            if (is_array($data) && array_key_exists('version', $data)) {
                $version = $data['version'];
            }
        }

        if (!empty($version) && $this->user->getExtensionversion() !== $version) {
            $this->user->setExtensionversion($version);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        return $this->user;
    }

    protected function getProgramType($kind)
    {
        global $arProviderKind;

        return $arProviderKind[$kind] ?? '';
    }

    /**
     * Sending messages depending on the provider status.
     *
     * @param int $providerId
     * @param string  $comment
     * @return bool
     */
    protected function mailSend($providerId, $comment, string $additionalData)
    {
        $row = $this->em->getConnection()->executeQuery(
            '
                    SELECT
                            CONCAT_WS(" ", u.FirstName, u.MidName, u.LastName) as UserName, u.Email, u.Phone1,
                            p.DisplayName, p.State, p.CollectingRequests
                    FROM
                            Usr u
                    LEFT JOIN Provider p
                            ON (p.ProviderID = :providerId)
                    WHERE
                            u.UserID = :userId
                ',
            [
                ':providerId' => $providerId,
                ':userId' => $this->user->getUserid(),
            ],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        if (empty($row)) {
            return false;
        }

        empty($comment) ?: $comment = 'Comment: ' . trim(preg_replace('/\n/', '<br/>', $comment)) . "<br/>";

        $message = $this->mailer->getMessage('status_program');
        $message
            ->setFrom(key($this->mailer->getEmail('from')), $row['UserName'])
            ->setReplyTo($row['Email'], $row['UserName'])
            ->setTo($this->mailer->getEmail('support'));

        $body = [];
        $state = $row['State'];

        switch (true) {
            case PROVIDER_FIXING == $state:
                $subject = 'Broken Program Reported';
                $body[] = 'Name: ' . $row['UserName'];
                $body[] = 'Email: ' . $row['Email'];
                $body[] = 'Phone: ' . $row['Phone1'];
                $body[] = 'Request Type: Broken Program Reported';
                $body[] = "UserID: <a target='_blank' href='https://awardwallet.com/manager/impersonate?UserID={$this->user->getUserid()}&AutoSubmit&AwPlus=1'>{$this->user->getUserid()}</a>";
                $body[] = $additionalData;
                $body[] = $comment;
                $body[] = 'Program reported to be broken: ' . $row['DisplayName'];
                $body[] = '';
                $body[] = $this->getReport($providerId);

                break;

            case PROVIDER_IN_DEVELOPMENT == $state:
            case 1 == $row['CollectingRequests']:
            case PROVIDER_COLLECTING_ACCOUNTS == $state:
                $subject = 'New Program Request';
                $body[] = 'Name: ' . $row['UserName'];
                $body[] = 'Email: ' . $row['Email'];
                $body[] = 'Phone: ' . $row['Phone1'];
                $body[] = 'Request Type: New Program Request';
                $body[] = "UserID: <a target='_blank' href='https://awardwallet.com/manager/impersonate?UserID={$this->user->getUserid()}&AutoSubmit&AwPlus=1'>{$this->user->getUserid()}</a>";
                $body[] = $additionalData;
                $body[] = $comment;
                $body[] = 'New Program Request: ' . html_entity_decode($row['DisplayName']);
                $message->setTo($this->mailer->getEmail('support'));

                break;

            case PROVIDER_ENABLED == $state:
            case PROVIDER_CHECKING_OFF == $state:
            case PROVIDER_CHECKING_WITH_MAILBOX == $state:
            case PROVIDER_CHECKING_EXTENSION_ONLY == $state:
                $browser = ContactUsController::getUserBrowser($this->requestStack->getMasterRequest()->headers->get('User-Agent'));
                $subject = 'Broken Program: ' . $row['DisplayName'];
                $body[] = $row['DisplayName'] . ' was marked as broken';
                $body[] = '';
                $body[] = 'Reported By: ' . $row['UserName'];
                $body[] = "UserID: <a target='_blank' href='https://awardwallet.com/manager/impersonate?UserID={$this->user->getUserid()}&AutoSubmit&AwPlus=1'>{$this->user->getUserid()}</a>";
                $body[] = 'Browser: ' . trim($browser . ', ext ' . ($this->user->getExtensionversion() ?? 'n/a')) . ', last used: ' . ($this->user->getExtensionlastusedate() ? $this->user->getExtensionlastusedate()->format('j F, Y H:i') : 'n/a');
                $body[] = 'User-Agent: ' . $this->requestStack->getMasterRequest()->headers->get('User-Agent');
                $body[] = 'IP: ' . $this->requestStack->getMasterRequest()->getClientIp();
                $body[] = $additionalData;
                $body[] = '';
                $body[] = $comment;
                $body[] = $this->getReport($providerId);

                break;

            default:
                $subject = 'Vote mailer error';
                $body[] = 'Unknown state - ' . $row['State'];
                $body[] = 'ProviderID: ' . $providerId;
                $body[] = "UserID: <a target='_blank' href='https://awardwallet.com/manager/impersonate?UserID={$this->user->getUserid()}&AutoSubmit&AwPlus=1'>{$this->user->getUserid()}</a>";
                $body[] = $comment;
                $body[] = $comment;
                $message
                    ->setTo($this->mailer->getEmail('error'))
                    ->setFrom($this->mailer->getEmail('from'))
                    ->setReplyTo($this->mailer->getEmail('from'));

                break;
        }

        $message->setSubject($subject);
        $message->setBody(implode("<br/>", $body), 'text/html');

        return $this->mailer->send($message, [Mailer::OPTION_FIX_BODY => false]);
    }

    /**
     * Determine whether the user voted.
     *
     * @param array $where List of conditions for sql "WHERE"
     * @param array $order List of fields for sorting
     * @return array
     */
    private function fetchList(array $where, array $order = [])
    {
        $order[] = 'Name ASC';
        $rows = $this->em->getConnection()->executeQuery(
            '
                SELECT
                        p.ProviderID, p.DisplayName, p.ShortName, p.Note, p.Site, p.Kind, p.EnableDate,
                        COUNT(pv.UserID) as Votes,
                        pv2.UserID as IsVote
                FROM
                        Provider p
                LEFT JOIN ProviderVote pv
                        ON (pv.ProviderID  = p.ProviderID)
                LEFT JOIN ProviderVote pv2
                        ON (pv2.ProviderID = p.ProviderID AND pv2.UserID = :userId)
                WHERE
                        ' . implode(' AND ', $where) . '
                GROUP BY
                        p.ProviderID, p.DisplayName, p.ShortName, p.Note, p.Site, p.Kind, p.EnableDate, pv2.UserID, p.Name
                ORDER BY
                        ' . implode(',', $order) . '
            ',
            [
                ':userId' => $this->user->getUserid(),
            ],
            [\PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);

        for ($i = 0, $iCount = count($rows); $i < $iCount; $i++) {
            $rows[$i]['date'] = new \DateTime($rows[$i]['EnableDate']);
            $rows[$i]['_kind'] = $this->getProgramType($rows[$i]['Kind']);

            preg_match("/^([^\(]+)(\(.+)$/ims", $rows[$i]['DisplayName'], $matches);
            $rows[$i]['Name'] = $matches[1] ?? $rows[$i]['DisplayName'];
            $rows[$i]['ProgramName'] = $matches[2] ?? '';
        }

        return $rows;
    }
}
