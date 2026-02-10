<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use Doctrine\ORM\Mapping\NamingStrategy;

class AwNamingStrategy implements NamingStrategy
{
    /**
     * Returns a column name for a property.
     *
     * @param string $propertyName a property name
     * @param string|null $className the fully-qualified class name
     * @return string a column name
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        if ($propertyName === 'id' && $className !== null) {
            return $this->classToTableName($className) . 'ID';
        }

        return ucfirst($propertyName);
    }

    /**
     * Returns a table name for an entity class.
     *
     * @param string $className the fully-qualified class name
     * @return string a table name
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * Returns a column name for an embedded property.
     *
     * @param string $propertyName
     * @param string $embeddedColumnName
     * @param string $className
     * @param string $embeddedClassName
     * @return string
     */
    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return ucfirst($propertyName . '_' . $embeddedColumnName);
    }

    /**
     * Returns a join column name for a property.
     *
     * @param string $propertyName a property name
     * @return string a join column name
     */
    public function joinColumnName($propertyName)
    {
        return ucfirst($propertyName) . '_' . $this->referenceColumnName();
    }

    /**
     * Returns the default reference column name.
     *
     * @return string a column name
     */
    public function referenceColumnName()
    {
        return 'id';
    }

    /**
     * Returns a join table name.
     *
     * @param string $sourceEntity the source entity
     * @param string $targetEntity the target entity
     * @param string|null $propertyName a property name
     * @return string a join table name
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->classToTableName($sourceEntity) . '_' .
            $this->classToTableName($targetEntity));
    }

    /**
     * Returns the foreign key column name for the given parameters.
     *
     * @param string $entityName an entity
     * @param string|null $referencedColumnName a property
     * @return string a join column name
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        return strtolower($this->classToTableName($entityName) . '_' .
            ($referencedColumnName ?: $this->referenceColumnName()));
    }
}
