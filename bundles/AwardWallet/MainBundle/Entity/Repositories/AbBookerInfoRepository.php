<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\AbBookerInfo;
use Doctrine\ORM\EntityRepository;

class AbBookerInfoRepository extends EntityRepository
{
    protected $awBooker = 7;
    protected $bookers;

    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $bookers = $this->findAll();

        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $booker */
        foreach ($bookers as $booker) {
            $this->bookers[$booker->getAbBookerInfoID()] = $booker;
        }

        // add AW
        if (!isset($this->bookers[$this->awBooker])) {
            $this->bookers[$this->awBooker] = $this->getAWBooker();
        }
    }

    public function getBookerByID($id)
    {
        if (is_null($result = $this->findOneBy(['UserID' => $id]))) {
            throw new \InvalidArgumentException("Booker #{$id} does not exist");
        }

        return $result;
    }

    public function getBookerByRef($ref)
    {
        if (empty($ref)) {
            return $this->getAWBooker();
        }

        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $booker */
        foreach ($this->bookers as $booker) {
            if ($booker->getSiteAdID() == $ref) {
                return $booker;
            }
        }

        return $this->getAWBooker();
    }

    public function getAWBooker()
    {
        if (isset($this->bookers[$this->awBooker])) {
            return $this->bookers[$this->awBooker];
        }
        $e = new AbBookerInfo();
        $e->setServiceShortName('AW')
            ->setUserID($this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->awBooker));

        return $e;
    }
}
