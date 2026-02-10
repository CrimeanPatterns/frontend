<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel;

/**
 * @author Kochergin Nickolay, 2012
 * @method AccessControlList toAccount()
 * @method AccessControlList toCoupon()
 * @method AccessControlList toTravelplan()
 */
class AccessControlList
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \AwardWallet\MainBundle\Entity\Usr
     */
    protected $user;

    /**
     * @var array
     */
    protected $goals = [];

    /**
     * @var object current goal
     */
    protected $goal;

    public function __construct()
    {
    }

    public function __call($method, $params)
    {
        if (!method_exists($this, $method) && strpos($method, "to") === 0) {
            $suffix = 'AsTable';
            $returnAllow = null;

            if (strpos($method, $suffix) !== false) {
                $returnAllow = false;
                $method = substr_replace($method, '', -strlen($suffix));
            } else {
                if (sizeof($params)) {
                    $returnAllow = true;
                }
            }
            $goal = ucfirst(strtolower(substr($method, 2)));
            array_unshift($params, $goal, $returnAllow);
            $result = call_user_func_array([$this, 'to'], $params);

            if (is_null($returnAllow)) {
                return $this;
            }

            return $result;
        }
    }

    public function setEntityManager(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    public function setUser($user)
    {
        if (!$user instanceof \AwardWallet\MainBundle\Entity\Usr) {
            $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($user);

            if (!$user) {
                throw new \Exception('User not found');
            }
        }
        $this->user = $user;

        return $this;
    }

    /**
     * @param $id int
     * @param $permission string
     * @param bool
     */
    public function allow($id, $permission, $fromCache = true)
    {
        $id = intval($id);
        $permission = strval($permission);
        $result = $this->rights($id, $permission, $fromCache);

        foreach ($result[$permission] as $id => $allow) {
            return $allow;
        }
    }

    /**
     * @param $ids int|array|string Int - id, array - Ids, string - sql
     * @param $permissions null|string|array null - all, string - one, array - many
     * @param bool
     */
    public function rights($ids, $permissions = null, $fromCache = false)
    {
        if (is_numeric($ids)) {
            $ids = [$ids];
        }
        $allPermissions = $this->goal->getAllPermissions();

        if (!isset($permissions)) {
            $permissions = $allPermissions;
        } elseif (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        $this->goal->setIds($ids, $fromCache);
        $result = [];

        foreach ($permissions as $permission) {
            if (!method_exists($this->goal, $permission) || !in_array($permission, $allPermissions)) {
                throw new \Exception('Permission "' . $permission . '" not found');
            }

            $result[$permission] = call_user_func_array([$this->goal, $permission], []);
        }

        return $result;
    }

    protected function to($goal, $returnAllow = null)
    {
        $arg_list = func_get_args();

        if (!isset($this->goals[$goal])) {
            $goal = "AwardWallet\\MainBundle\\Globals\\AccessLevel\\Right\\" . $goal;
            $this->goals[$goal] = new $goal($this->user, $this->em);
        }
        $this->goal = $this->goals[$goal];

        if (!is_null($returnAllow)) {
            unset($arg_list[0], $arg_list[1]);

            if ($returnAllow) {
                return call_user_func_array([$this, 'allow'], $arg_list);
            } else {
                return call_user_func_array([$this, 'rights'], $arg_list);
            }
        }
    }
}
