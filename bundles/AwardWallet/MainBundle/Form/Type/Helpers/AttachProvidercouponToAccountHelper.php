<?php

namespace AwardWallet\MainBundle\Form\Type\Helpers;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class AttachProvidercouponToAccountHelper
{
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    /**
     * @var AwTwigExtension
     */
    private $awTwigExtension;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    private EntityManagerInterface $entityManager;

    public function __construct(
        ProviderRepository $providerRepository,
        AccountRepository $accountRepository,
        AwTwigExtension $awTwigExtension,
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager
    ) {
        $this->providerRepository = $providerRepository;
        $this->accountRepository = $accountRepository;
        $this->awTwigExtension = $awTwigExtension;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public function getAccountLabel(Account $account): string
    {
        $formattedBalance = $this->awTwigExtension->formatBalance($account,
            $this->tokenStorage->getBusinessUser()->getLocale(), 'n/a');

        if ('n/a' === $formattedBalance) {
            return $account->getLogin();
        } else {
            $login = $account->getLogin();

            if (empty($login) && null !== $account->getOwner()) {
                $login = $account->getOwner()->getFullName();
            }

            return sprintf("%s (%s)", $login, $formattedBalance);
        }
    }

    public function getAccounts(?Provider $provider = null, ?Owner $owner = null, ?string $programName = null)
    {
        if (null === $owner || (null === $provider && empty($programName))) {
            return new ArrayCollection();
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(Account::class, 'a')
            ->where('a.user = :user')->setParameter('user', $owner->getUser())
            ->andWhere("a.state >= 0");
        null === $owner->getFamilyMember()
            ? $qb->andWhere('a.userAgent is null')
            : $qb->andWhere('a.userAgent = :ua')->setParameter('ua', $owner->getFamilyMember());

        if (null === $provider) {
            $qb->andWhere('a.programname like :name')->setParameter('name', $programName . '%');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('a.providerid', $provider->getId()),
                    $qb->expr()->like('a.programname', $qb->expr()->literal($programName . '%'))
                )
            );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Provider|null
     */
    public function getProviderByProgramName(string $programName)
    {
        /** @var Provider[] $providers */
        $providers = $this->providerRepository->searchProviderByText(
            $programName,
            null,
            null,
            1,
            array_diff(ProviderRepository::PROVIDER_SEARCH_ALLOWED_STATES, [PROVIDER_DISABLED])
        );

        if (empty($providers)) {
            return null;
        } else {
            /** @var Provider $provider */
            $provider = $this->providerRepository->find(reset($providers)['ProviderID']);

            return $provider;
        }
    }
}
