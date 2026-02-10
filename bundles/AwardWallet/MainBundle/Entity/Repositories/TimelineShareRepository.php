<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class TimelineShareRepository extends EntityRepository
{
    public const WITH_WRITE_PERMISSION = true;

    /**
     * @return TimelineShare|null
     */
    public function addTimelineShare(Useragent $userAgent, ?Useragent $familyMember = null)
    {
        $owner = $userAgent->getClientid();
        $recipientUser = $userAgent->getAgentid();

        $share = $this->findOneBy([
            'timelineOwner' => $owner,
            'familyMember' => $familyMember,
            'userAgent' => $userAgent,
            'recipientUser' => $recipientUser,
        ]);

        if (empty($share)) {
            $em = $this->getEntityManager();
            $timelineShare = new TimelineShare();
            $timelineShare->setUserAgent($userAgent);
            $timelineShare->setTimelineOwner($owner);
            $timelineShare->setFamilyMember($familyMember);
            $timelineShare->setRecipientUser($recipientUser);
            $em->persist($timelineShare);
            $em->flush();
            $em->refresh($owner); // refresh timelineshares
            $em->refresh($recipientUser); // refresh timelineshares

            return $timelineShare;
        } else {
            return null;
        }
    }

    public function removeTimelineShare(Useragent $userAgent, ?Useragent $familyMember = null)
    {
        $owner = $userAgent->getClientid();
        $recipientUser = $userAgent->getAgentid();

        $share = $this->findOneBy([
            'timelineOwner' => $owner,
            'familyMember' => $familyMember,
            'userAgent' => $userAgent,
            'recipientUser' => $recipientUser,
        ]);

        if ($share) {
            $em = $this->getEntityManager();
            $em->remove($share);
            $em->flush();
        }
    }

    /**
     * @param int $id
     */
    public function removeTimelineShareByID($id)
    {
        $share = $this->find($id);

        if ($share) {
            $em = $this->getEntityManager();
            $em->remove($share);
            $em->flush();
        }
    }

    /**
     * @param string|null $name
     * @param int $limit
     * @param bool $withWritePermission
     * @return TimelineShare[]
     */
    public function findByName(Usr $agent, string $name = '', $limit = 10, $withWritePermission = false)
    {
        $builder = $this->createQueryBuilder('share');
        $expr = $builder->expr();
        $builder
            ->leftJoin('share.userAgent', 'connection')
            ->leftJoin('share.familyMember', 'familyMember')
            ->leftJoin('share.timelineOwner', 'timelineOwner')
            ->where($expr->eq('share.recipientUser', ':agent'))
            ->andWhere($expr->eq('connection.isapproved', true))
            ->setParameter(':agent', $agent);

        if ('' !== $name) {
            $builder->andWhere($expr->orX(
                'MATCH (familyMember.firstname, familyMember.midname, familyMember.lastname) AGAINST (:nameFulltext boolean) > 0.0',
                "concat(
                    case when timelineOwner.firstname <> '' then concat(timelineOwner.firstname, ' ') else '' end,
                    case when
                        timelineOwner.midname is not null 
                        and timelineOwner.midname <> ''
                    then concat(timelineOwner.midname, ' ')
                    else '' end,
                    case when timelineOwner.lastname <> '' then timelineOwner.lastname else '' end
                ) like :name"
            ))
            ->setParameter(':nameFulltext', "$name*")
            ->setParameter(':name', "%$name%");
        }

        if ($withWritePermission) {
            $builder->andWhere("connection.tripAccessLevel = " . Useragent::TRIP_ACCESS_FULL_CONTROL);
        }

        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()->getResult();
    }
}
