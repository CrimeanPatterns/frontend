<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of object
 * @template-extends ServiceEntityRepository<T>
 */
class Repository extends ServiceEntityRepository
{
    use RepositoryTrait;
}
