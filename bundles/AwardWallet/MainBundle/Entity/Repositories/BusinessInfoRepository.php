<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\BusinessInfo;
use Doctrine\ORM\EntityRepository;

/**
 * BusinessInfoRepository.
 */
class BusinessInfoRepository extends EntityRepository
{
    public function generateNewKey(BusinessInfo $businessInfo)
    {
        $key = sha1($businessInfo->getUser()->getUserid() . microtime());
        $businessInfo->setApiKey($key);
        $this->_em->flush($businessInfo);
    }
}
