<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

/**
 * @author Kochergin Nickolay, 2012
 */
abstract class AbstractRight
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \AwardWallet\MainBundle\Entity\Usr
     */
    protected $user;

    protected $ids;
    protected $filter = '';
    protected $fields = [];

    public function __construct($user, $em)
    {
        $this->user = $user;
        $this->em = $em;
    }

    /**
     * @param $ids array|string Array - Ids, String - SQL
     */
    public function setIds($ids, $fromCache = false)
    {
        if (!sizeof($this->fields)) {
            $fromCache = false;
        }

        if ($fromCache) {
            return;
        }

        if (!is_array($ids) && !is_string($ids)) {
            throw new \Exception('Incorrect input id');
        }
        $this->ids = $ids;

        if (is_array($ids)) {
            foreach ($ids as $k => $id) {
                $ids[$k] = "'" . $id . "'";
            }

            if (count($ids) == 0) {
                $this->fields = [];

                return;
            }
            $this->filter = implode(", ", $ids);
        } else {
            $this->filter = $ids;
        }
        $this->fetchFields($this->ids, $this->filter);
    }

    public function getDefaultValues()
    {
        if (!sizeof($this->ids)) {
            return [];
        }

        return array_fill_keys($this->ids, false);
    }

    public function fetchFields($ids, $filter)
    {
        throw new \RuntimeException('The method "fetchFields" should be overridden');
    }

    public function getAllPermissions()
    {
        throw new \RuntimeException('The method "getAllPermissions" should be overridden');
    }
}
