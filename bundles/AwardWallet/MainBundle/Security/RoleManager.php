<?php

namespace AwardWallet\MainBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleManager
{
    /**
     * @var RoleHierarchyInterface
     */
    protected $rolerHierarchy;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, RoleHierarchyInterface $rolerHierarchy)
    {
        $this->rolerHierarchy = $rolerHierarchy;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return array
     */
    public function getAllowedSchemas()
    {
        $rolesNamesList = $this->rolerHierarchy->getReachableRoleNames($this->tokenStorage->getToken()->getRoleNames());
        $allowedSchemas = [];

        foreach ($rolesNamesList as $roleName) {
            if (strpos($roleName, 'ROLE_MANAGE_') === 0) {
                $allowedSchemas[] = strtolower(substr($roleName, strlen('ROLE_MANAGE_')));
            }
        }

        return $allowedSchemas;
    }
}
