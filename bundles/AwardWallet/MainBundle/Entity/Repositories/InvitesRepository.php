<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class InvitesRepository extends EntityRepository
{
    public function getCountInvitedByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT COUNT(InvitesID) AS invited 
		FROM   Invites 
		WHERE  InviterID = ? AND InviteeID <> InviterID
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $r['invited'];
    }

    public function getCountAcceptedByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT COUNT(InvitesID) AS accepted 
		FROM   Invites 
		WHERE  InviterID = ? 
		       AND InviteeID IS NOT NULL
			   AND InviteeID <> InviterID
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $r['accepted'];
    }

    public function getUserInvitesData(Usr $user)
    {
        $userID = $user->getUserid();
        $connection = $this->getEntityManager()->getConnection();

        $qInvites = "
                    SELECT i.*,
                           ua.useragentid,
                           br.isapproved,
                           u.firstname,
                           u.lastname
                    FROM   Invites i
                           LEFT OUTER JOIN UserAgent ua
                                        ON ua.clientid = {$userID}
                                           AND ua.agentid = i.inviteeid
                           LEFT OUTER JOIN UserAgent br
                                        ON br.clientid = i.inviteeid
                                           AND br.agentid = {$userID}
                           LEFT OUTER JOIN Usr u
                                        ON i.inviteeid = u.userid
                    WHERE  i.inviterid = {$userID}
                           AND i.inviteeid <> {$userID}
                           AND NOT ( i.inviteeid IS NULL
                                     AND ( i.approved = 1
                                            OR i.code IS NULL ) )
                           AND i.email LIKE '%@%.%'
                    ORDER  BY i.invitedate";

        $stmt = $connection->executeQuery($qInvites);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
