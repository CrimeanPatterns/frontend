<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail;

use AwardWallet\MainBundle\Entity\OneTimeCode;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class StorageOperations
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ObjectRepository
     */
    private $oneTimeCodeRepo;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObjectRepository $oneTimeCodeRepo
    ) {
        $this->entityManager = $entityManager;
        $this->oneTimeCodeRepo = $oneTimeCodeRepo;
    }

    public function saveCode(OneTimeCode $oneTimeCode): void
    {
        $this->entityManager->persist($oneTimeCode);
        $this->entityManager->flush();
    }

    public function findUserCodes(Usr $user): array
    {
        return $this->oneTimeCodeRepo->findBy(['user' => $user], ['creationDate' => 'DESC']);
    }

    public function findUserCode(Usr $user, string $presentedCode): ?OneTimeCode
    {
        return $this->oneTimeCodeRepo->findOneBy(['user' => $user, 'code' => $presentedCode]);
    }

    public function saveLastCodes(array $codes, int $n): void
    {
        $needFlush = false;

        foreach (
            it($codes)
            ->usort(function (OneTimeCode $a, OneTimeCode $b) {
                return $b->getCreationDate() <=> $a->getCreationDate();
            })
            ->drop($n) as $code
        ) {
            $needFlush = true;
            $this->entityManager->remove($code);
        }

        if ($needFlush) {
            $this->entityManager->flush();
        }
    }

    public function deleteUserCodes(Usr $user): void
    {
        foreach ($this->findUserCodes($user) as $code) {
            $this->entityManager->remove($code);
        }

        $this->entityManager->flush();
    }
}
