<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

abstract class AbstractSource implements PropertySourceInterface
{
    /**
     * @var Statement
     */
    protected $query;

    /**
     * @var Statement
     */
    protected $itineraryQuery;

    /**
     * @var Statement
     */
    protected $update;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function recordChanges(Properties $properties, \DateTime $changeDate)
    {
        $this->update->execute([
            'changeDate' => $changeDate->format('Y-m-d H:i:s'),
            'userData' => intval($properties->userData),
        ]);
    }

    abstract public function getProperties($accountId);

    abstract public function getItineraryProperties($itineraryId);

    /**
     * returns entity properties belongs to.
     */
    public function getEntity(Properties $properies)
    {
        return $this->repository->find($properies->userData);
    }
}
