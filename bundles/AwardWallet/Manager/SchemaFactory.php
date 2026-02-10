<?php

namespace AwardWallet\Manager;

use AwardWallet\MainBundle\Service\OldUI;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SchemaFactory
{
    /**
     * @var ServiceLocator
     */
    private $schemas;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(ServiceLocator $schemas, AuthorizationCheckerInterface $authorizationChecker, OldUI $oldUI)
    {
        $this->schemas = $schemas;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getSchema(string $schemaName): \TBaseSchema
    {
        // TODO: migrate to some old-loader service
        global $Interface;
        $Interface = new \NDInterface();
        $Interface->Init();

        $role = 'ROLE_MANAGE_' . strtoupper($schemaName);

        if (!$this->authorizationChecker->isGranted($role)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $result = $this->schemas->get($schemaName);
        } catch (NotFoundExceptionInterface $exception) {
            $className = $this->buildOldClassName($schemaName);
            $result = new $className();
        }

        $result->Admin = true;
        $result->CompleteFields();

        return $result;
    }

    private function buildOldClassName(string $schemaName): string
    {
        $srcPath = __DIR__ . '/../../../web';

        foreach (["$srcPath/schema", "$srcPath/lib/schema"] as $folder) {
            $file = "$folder/$schemaName.php";

            if (file_exists($file)) {
                require_once $file;

                break;
            }
        }

        return "T{$schemaName}Schema";
    }
}
