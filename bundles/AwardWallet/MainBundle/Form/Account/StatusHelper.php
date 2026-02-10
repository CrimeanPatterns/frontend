<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class StatusHelper extends PropertyHelper
{
    /**
     * @var ElitelevelRepository
     */
    protected $elRepo;

    public function __construct(Connection $connection, ElitelevelRepository $elRepo, EntityRepository $providerPropertyRepo)
    {
        parent::__construct($connection, $providerPropertyRepo);
        $this->elRepo = $elRepo;
    }

    public function getField(array $accountFields, $kind)
    {
        $result = parent::getField($accountFields, PROPERTY_KIND_STATUS);

        if (!empty($accountFields['AccountID'])) {
            $eliteLevel = $this->elRepo->getEliteLevelFields($accountFields['ProviderID'], $result['Value']);
            $result['Value'] = $eliteLevel['ValueText'] ?? null;
        }

        $eliteLevels = $this->elRepo->getEliteLevelFields($accountFields['ProviderID']);
        $ranks = [];

        foreach ($eliteLevels as $level) {
            if (!in_array($level['Name'], $ranks)) {
                $ranks[$level['ValueText']] = $level['Name'];
            }
        }
        $result['Options'] = $ranks;
        $result['Required'] = true;

        return $result;
    }

    public function saveField($value, Account $account, $kind)
    {
        parent::saveField($value, $account, PROPERTY_KIND_STATUS);
    }
}
