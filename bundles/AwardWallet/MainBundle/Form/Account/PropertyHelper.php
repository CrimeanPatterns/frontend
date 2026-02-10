<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Providerproperty;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class PropertyHelper
{
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var EntityRepository
     */
    protected $providerPropertyRepo;

    public function __construct(Connection $connection, EntityRepository $providerPropertyRepo)
    {
        $this->connection = $connection;
        $this->providerPropertyRepo = $providerPropertyRepo;
    }

    public function getField(array $accountFields, $kind)
    {
        $value = '';
        $alias = null;
        $fieldTitle = $this->connection->fetchColumn("
			select Name from ProviderProperty where ProviderID = ? and Kind = " . $kind, [$accountFields['ProviderID']]
        );

        if (!empty($accountFields['AccountID'])) {
            $value = $this->connection->fetchColumn("
				select ap.Val from
				ProviderProperty pp
				join AccountProperty ap on pp.ProviderPropertyID = ap.ProviderPropertyID
				where ap.AccountID = ? and pp.Kind = " . $kind, [$accountFields['AccountID']]
            );
        }

        return [
            "Type" => "string",
            "Caption" => $fieldTitle,
            "Required" => false,
            "Database" => false,
            "Value" => $value,
        ];
    }

    public function saveField($value, Account $account, $kind)
    {
        /** @var Providerproperty $providerProperty */
        $providerProperty = $this->providerPropertyRepo->findOneBy(['kind' => $kind, 'providerid' => $account->getProviderid()]);
        $existing = [];

        foreach ($account->getProperties() as $property) {
            if ($property->getProviderpropertyid()->getProviderpropertyid() == $providerProperty->getProviderpropertyid()) {
                $existing[] = $property;
            }
        }

        if (!empty($value)) {
            if (!empty($existing)) {
                array_pop($existing)->setVal($value);
            } else {
                $property = new Accountproperty();
                $property->setProviderpropertyid($providerProperty);
                $property->setAccountid($account);
                $property->setVal($value);
                $account->getProperties()->add($property);
            }
        }

        foreach ($existing as $property) {
            $account->getProperties()->removeElement($property);
        }
    }
}
