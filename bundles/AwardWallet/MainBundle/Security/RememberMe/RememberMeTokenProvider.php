<?php

namespace AwardWallet\MainBundle\Security\RememberMe;

use AwardWallet\MainBundle\Security\SessionListener;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class RememberMeTokenProvider implements TokenProviderInterface
{
    private $entityManager;
    private $connection;
    private $logger;
    /**
     * @var SessionListener
     */
    private $sessionListener;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, SessionListener $sessionListener)
    {
        $this->entityManager = $entityManager;
        $this->connection = $this->entityManager->getConnection();
        $this->logger = $logger;
        $this->sessionListener = $sessionListener;
    }

    /**
     * Loads the active token for the given series.
     *
     * @param string $series
     * @return AwPersistentToken
     * @throws TokenNotFoundException if the token is not found
     */
    public function loadTokenBySeries($series)
    {
        $stmt = $this->connection->executeQuery('
            SELECT
                r.RememberMeTokenID,
                r.UserID,
                r.Token,
                r.LastUsed,
                u.Login,
                r.IP,
                r.UserAgent
            FROM RememberMeToken r
            JOIN Usr u ON
                u.UserID = r.UserID
            WHERE Series = ?',
            [$series],
            [\PDO::PARAM_STR]);
        $tokens = $stmt->fetchAll();

        if (empty($tokens)) {
            return null;
        }

        if (count($tokens) > 1) {
            return null;
        }

        $token = array_shift($tokens);

        return new AwPersistentToken(-1, $token['Login'], $series, $token['Token'], new \DateTime($token['LastUsed']), $token['IP'], $token['UserAgent'], $token['RememberMeTokenID']);
    }

    /**
     * Deletes all tokens belonging to series.
     *
     * @param string $series
     */
    public function deleteTokenBySeries($series)
    {
        $this->connection->executeUpdate('DELETE FROM RememberMeToken WHERE Series = ?', [$series], [\PDO::PARAM_STR]);
    }

    /**
     * Updates the token according to this data.
     *
     * @param string $series
     * @param string $tokenValue
     * @throws TokenNotFoundException if the token is not found
     */
    public function updateToken($series, $tokenValue, \DateTime $lastUsed)
    {
        $this->connection->executeUpdate('
            UPDATE RememberMeToken
            SET
                Token = ?,
                LastUsed = ?
            WHERE Series = ?',
            [$tokenValue, $lastUsed, $series],
            [\PDO::PARAM_STR, 'datetime', \PDO::PARAM_STR]
        );
    }

    public function updateIpUseragent(int $rememberTokenId, string $ip, string $ua)
    {
        return $this->connection->executeUpdate('
            UPDATE RememberMeToken
            SET
                LastUsed  = NOW(),
                IP        = ?,
                UserAgent = ?
            WHERE RememberMeTokenID = ?',
            [$ip, $ua, $rememberTokenId],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
        );
    }

    /**
     * Creates a new token.
     *
     * @param AwPersistentToken $token
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createNewToken(PersistentTokenInterface $token)
    {
        $this->connection->executeUpdate('
            INSERT INTO RememberMeToken (
                UserID,
                Series,
                Token,
                LastUsed,
                IP,
                UserAgent 
            )
            SELECT UserID, ?, ?, ?, ?, ? FROM Usr WHERE Login = ?',
            [$token->getSeries(), $token->getTokenValue(), $token->getLastUsed(), $token->getIp(), $token->getUserAgent(), $token->getUsername()],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, 'datetime', \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]);

        $this->sessionListener->saveRememberMeId($this->connection->lastInsertId());
    }

    public function deleteTokenByUserId($userId)
    {
        $this->connection->executeUpdate('DELETE FROM RememberMeToken WHERE UserID = ?', [$userId], [\PDO::PARAM_INT]);
    }

    public function deleteTokenByUserIdExceptCurrentSession(int $userId, string $currentSessionId): void
    {
        $this->connection->executeStatement('
            delete from RememberMeToken 
            where 
                UserID = :userId
                and RememberMeTokenID <> ifnull(
                    (
                        select s.RememberMeTokenID 
                        from Session s
                        where
                            s.SessionID = :sessionId
                            and s.UserID = :userId
                    ),
                    -1    
                )',
            [
                "userId" => $userId,
                "sessionId" => $currentSessionId,
            ]);
    }

    /**
     * @param array|string $ip
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteUserTokenByIp(int $userId, $ip): int
    {
        is_array($ip) ?: ($ip = is_string($ip) ? $ip = [$ip] : null);

        if (empty($ip)) {
            throw new \BadMethodCallException('Unsupported type for IP, need array or string');
        }

        return $this->connection->executeUpdate(
            'DELETE FROM RememberMeToken WHERE UserID = ? AND IP IN (?)',
            [$userId, $ip],
            [\PDO::PARAM_INT, $this->connection::PARAM_STR_ARRAY]
        );
    }

    public function fetchIdentificationByUserId(int $userId): array
    {
        $session = $this->connection->fetchAll('
            SELECT 
               s.SessionID, 
               s.RememberMeTokenID, 
               s.IP, 
               s.UserAgent, 
               s.LastActivityDate AS LastUsed, 
               UNIX_TIMESTAMP(s.LastActivityDate) as _ts,
               md.MobileDeviceID IS NOT NULL as IsMobile,
               md.AppVersion
            FROM `Session` s
            LEFT JOIN MobileDevice md on s.RememberMeTokenID = md.RememberMeTokenID
            WHERE s.UserID = ? AND s.Valid = 1
        ', [$userId], [\PDO::PARAM_INT]);

        $rememberTokensId = array_column($session, 'RememberMeTokenID');
        $rememberTokensId = array_filter($rememberTokensId, function ($item) {
            return !empty($item);
        });
        !empty($rememberTokensId) ?: $rememberTokensId = [-1];

        $tokens = $this->connection->fetchAll('
            SELECT RememberMeTokenID, LastUsed, IP, UserAgent, UNIX_TIMESTAMP(LastUsed) as _ts
            FROM `RememberMeToken`
            WHERE UserID = ? AND RememberMeTokenID NOT IN (?)
        ', [$userId, $rememberTokensId], [\PDO::PARAM_INT, $this->connection::PARAM_INT_ARRAY]);

        return array_merge($session, $tokens);
    }
}
