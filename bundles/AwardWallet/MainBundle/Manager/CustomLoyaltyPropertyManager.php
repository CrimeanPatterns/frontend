<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\CustomLoyaltyProperty;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Globals\ClassUtils;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\StoreLocationFinderTask;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CustomLoyaltyPropertyManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var Process
     */
    private $asyncProcessExecutor;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Process $asyncProcessExecutor,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->asyncProcessExecutor = $asyncProcessExecutor;
        $this->logger = $logger;
    }

    public function update(LoyaltyProgramInterface $loyaltyProgram, $data)
    {
        $this->checkOrThrow($this->authorizationChecker->isGranted('EDIT', $loyaltyProgram));
        $validValuesCount = count(array_filter(
            $data,
            function ($val, $key) {
                return
                    is_scalar($val)
                    && (strlen($key) <= CustomLoyaltyProperty::MAX_KEY_LENGTH)
                    && (strlen($val) <= CustomLoyaltyProperty::MAX_VALUE_LENGTH)
                    && in_array($key, CustomLoyaltyProperty::NAMES);
            },
            ARRAY_FILTER_USE_BOTH
        ));

        if (
            !is_array($data)
            || ($validValuesCount !== count($data))
        ) {
            throw new \InvalidArgumentException('Invalid data');
        }

        foreach ($data as $propertyName => $propertyValue) {
            $property = $this->entityManager
                ->getRepository(\AwardWallet\MainBundle\Entity\CustomLoyaltyProperty::class)
                ->findOneBy([
                    strtolower(ClassUtils::getName($loyaltyProgram)) . 'id' => $loyaltyProgram->getId(),
                    'name' => $propertyName,
                ]);

            if (!$property) {
                $property = new CustomLoyaltyProperty($propertyName, $propertyValue);
            } else {
                $property->setValue($propertyValue);
            }

            $loyaltyProgram->addCustomLoyaltyProperty($property);
            $property->setContainer($loyaltyProgram);
            $this->entityManager->persist($property);
        }

        $this->entityManager->flush();

        $propertiesNames = array_keys($data);

        if (
            (
                in_array('BarCodeData', $propertiesNames, true)
                || in_array('BarCode', $propertiesNames, true)
            )
            && $task = StoreLocationFinderTask::createFromLoyalty($loyaltyProgram)
        ) {
            $this->asyncProcessExecutor->execute($task);
        }
    }

    public function delete(LoyaltyProgramInterface $loyaltyProgram, $data)
    {
        $this->checkOrThrow($this->authorizationChecker->isGranted('DELETE', $loyaltyProgram));

        if (!is_int(key($data))) {
            $data = array_keys($data);
        }

        foreach ($data as $propertyName) {
            $loyaltyProgram->removeCustomLoyaltyPropertyByName($propertyName);
        }

        $this->entityManager->flush();
    }

    protected function checkOrThrow($condition)
    {
        if (!$condition) {
            throw new AccessDeniedException();
        }
    }
}
