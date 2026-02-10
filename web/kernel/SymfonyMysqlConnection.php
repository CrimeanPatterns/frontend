<?php

class SymfonyMysqlConnection extends TMySQLConnection
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $LastError;

    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $lastStatement;

    public function Open($arParameters = null, $newLink = false)
    {
        $container = getSymfonyContainer();
        $this->em = $container->get("doctrine.orm.default_entity_manager");
        $this->connection = $container->get("database_connection");
        $this->Active = true;
    }

    public function Delete($tableName, $id)
    {
        if (!$this->Active) {
            $this->Open();
        }

        if (!empty($entity = $this->loadEntity($tableName, $id))) {
            $this->em->initializeObject($entity);
            $this->em->remove($entity);
            $this->em->flush();
        } else {
            parent::Delete($tableName, $id);
        }
    }

    public function Execute($sSQL, $bDieOnError = true)
    {
        $this->LastError = null;

        if (!$this->Active) {
            $this->Open();
        }

        try {
            $statement = $this->connection->prepare($sSQL);
            $statement->execute();
            $this->lastStatement = $statement;
            $this->AffectedRows = $statement->rowCount();

            return $statement;
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->LastError = $e->getMessage();

            if ($bDieOnError) {
                throw $e;
            } else {
                return false;
            }
        }
    }

    public function OpenQuery($sSQL)
    {
        if (!$this->Active) {
            $this->Open();
        }

        return $this->connection->executeQuery($sSQL);
    }

    /**
     * @param \Doctrine\DBAL\Statement $statement
     */
    public function CloseQuery($statement)
    {
        $statement->closeCursor();
    }

    /**
     * @param \Doctrine\DBAL\Statement $statement
     */
    public function Fetch($statement)
    {
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function InsertID()
    {
        return $this->connection->lastInsertId();
    }

    public function getEntityState($tableName, $id)
    {
        $entity = $this->loadEntity($tableName, $id);

        if (empty($entity)) {
            return null;
        }

        $serializer = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\EntitySerializer::class);

        return ['entity' => $entity, 'before' => $serializer->getProperties($entity)];
    }

    public function sendUpdateEvent(array $state = null)
    {
        if (empty($state)) {
            return;
        }

        $this->em->refresh($state['entity']);
        $serializer = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\EntitySerializer::class);
        $after = $serializer->getProperties($state['entity']);

        $changeSet = [];

        foreach ($state['before'] as $key => $value) {
            if ($value !== $after[$key]) {
                $changeSet[$key] = [$value, $after[$key]];
            }
        }

        if (!empty($changeSet)) {
            $this->em->getEventManager()->dispatchEvent('onFlush', new \Doctrine\ORM\Event\LifecycleEventArgs($state['entity'], $this->em));
            $this->em->getEventManager()->dispatchEvent('preUpdate', new \Doctrine\ORM\Event\PreUpdateEventArgs($state['entity'], $this->em, $changeSet));
            $this->em->getEventManager()->dispatchEvent('postFlush', new \Doctrine\ORM\Event\LifecycleEventArgs($state['entity'], $this->em));
        }
    }

    public function sendInsertEvent($tableName)
    {
        if ($entity = $this->loadEntity($tableName, $this->InsertID())) {
            $args = new \Doctrine\ORM\Event\LifecycleEventArgs($entity, $this->em);
            $this->em->getEventManager()->dispatchEvent('onFlush', $args);
            $this->em->getEventManager()->dispatchEvent('postPersist', $args);
            $this->em->getEventManager()->dispatchEvent('postFlush', $args);
        }
    }

    public function Close()
    {
        $this->Active = false;
        $this->em = null;
        $this->connection = null;
    }

    public function GetLastError()
    {
        return $this->LastError;
    }

    public function GetAffectedRows()
    {
        return $this->lastStatement->rowCount();
    }

    //	public function Execute($sql, $dieOnError = true ){
    //		// update
    //		if(preg_match('#^\s*update\s+(\w+).+where.*\s+(\w+)ID\s*=\s*(\d+)\s*$#ims', $sql, $matches)
    //		&& strtolower($matches[1]) == strtolower($matches[2])
    //		&& mysql_affected_rows() == 1
    //		&& $entity = $this->loadEntity($matches[1], $matches[3])){
    //			$serializer = getSymfonyContainer()->get("aw.entity_serializer");
    //			$before = $serializer->getProperties($entity);
    //			parent::Execute($sql, $dieOnError);
    //			$this->em->refresh($entity);
    //			$after = $serializer->getProperties($entity);
    //
    //			$changeSet = [];
    //			foreach($before as $key => $value)
    //				if($value != $after[$key])
    //					$changeSet[$key] = [$value, $after[$key]];
    //
    //			if(!empty($changeSet)) {
    //				$args = new \Doctrine\ORM\Event\LifecycleEventArgs($entity, $this->em);
    //				$this->em->getEventManager()->dispatchEvent('onFlush', $args);
    //				$this->em->getEventManager()->dispatchEvent('preUpdate', new \Doctrine\ORM\Event\PreUpdateEventArgs($entity, $this->em, $changeSet));
    //				$this->em->getEventManager()->dispatchEvent('postFlush', $args);
    //			}
    //		}
    //		// insert
    //		elseif(preg_match('#^\s*insert\s+into\s+(\w+)\s*\([^\)]+\)\s*values\s*\(#ims', $sql, $matches) && $entity = $this->loadEntity($matches[1], mysql_insert_id())){
    //			$args = new \Doctrine\ORM\Event\LifecycleEventArgs($entity, $this->em);
    //			parent::Execute($sql, $dieOnError);
    //			$this->em->getEventManager()->dispatchEvent('onFlush', $args);
    //			$this->em->getEventManager()->dispatchEvent('postPersist', $args);
    //			$this->em->getEventManager()->dispatchEvent('postFlush', $args);
    //		}
    //		// delete
    //		elseif(preg_match('#^\s*delete\s+from\s+(\w+)\s+where.*\s+(\w+)ID\s*=\s*(\d+)\s*$#ims', $sql, $matches)
    //		&& strtolower($matches[1]) == strtolower($matches[2])
    //		&& mysql_affected_rows() == 1
    //		&& $entity = $this->loadEntity($matches[1], $matches[3])){
    //			$args = new \Doctrine\ORM\Event\LifecycleEventArgs($entity, $this->em);
    //			parent::Execute($sql, $dieOnError);
    //			$this->em->getEventManager()->dispatchEvent('onFlush', $args);
    //			$this->em->getEventManager()->dispatchEvent('preRemove', $args);
    //			$this->em->getEventManager()->dispatchEvent('postRemove', $args);
    //			$this->em->getEventManager()->dispatchEvent('postFlush', $args);
    //			$this->em->flush();
    //		}
    //		else
    //			parent::Execute($sql, $dieOnError);
    //
    //	}
    //
    private function loadEntity($tableName, $id)
    {
        $tableName = ucfirst(strtolower($tableName));

        if (!in_array($tableName, [
            'Account', 'Providercoupon', 'Accountshare', 'Providercouponshare', 'Provider', 'Trip', 'Rental',
            'Reservation', 'Restaurant', 'Travelplan', 'Elitelevel', 'Useragent', 'Providerphone',
            'Redirect',
        ])) {
            return null;
        }

        $className = 'AwardWallet\MainBundle\Entity\\' . $tableName;

        if (!class_exists($className)) {
            return null;
        }

        return $this->em->getRepository($className)->find($id);
    }
}
