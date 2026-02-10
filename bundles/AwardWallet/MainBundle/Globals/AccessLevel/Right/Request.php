<?php
/**
 * Created by norman.
 * Date: 04.10.13
 * Time: 14:20.
 */

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

use AwardWallet\MainBundle\Entity\AbRequest;

class Request extends AbstractRight
{
    public function fetchFields($ids, $filter)
    {
        $this->fields = $this->em->createQueryBuilder()->select('r')->from(AbRequest::class, 'r')->where('r.AbRequestID = ' . $filter)->getQuery()->getResult();
    }

    public function view()
    {
        if (!$this->user) {
            return [
                $this->filter => false,
            ];
        }

        $access = false;

        if ($this->fields[0]->getUser()->getUserid() == $this->user->getUserid() || $this->fields[0]->getBooker()->getUserid() == $this->user->getUserid()) {
            $access = true;
        }

        return [
            $this->filter => $access,
        ];
    }

    public function getAllPermissions()
    {
        return ['view'];
    }
}
