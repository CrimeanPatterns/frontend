<?php

namespace AwardWallet\MainBundle\Manager\Files;

use AwardWallet\MainBundle\Entity\Files\PlanFile;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Service\Storage\PlanFilesStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlanFileManager extends FileManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        PlanFilesStorage $planFilesStorage,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($entityManager, $planFilesStorage, $translator, $logger, $authorizationChecker);
    }

    /** @param array{id: int, description: string} $files */
    public function updatePlanFilesDescriptions(array $files, Plan $plan): void
    {
        foreach ($files as $file) {
            /** @var PlanFile $planFile */
            $planFile = $this->entityManager->getRepository(PlanFile::class)->find($file['id']);

            if ($planFile && $planFile->getPlan()->getId() === $plan->getId()) {
                // plan owner affiliation
                $planFile->setDescription($file['description']);
                $this->entityManager->persist($planFile);
            }
        }
        $this->entityManager->flush();
    }
}
