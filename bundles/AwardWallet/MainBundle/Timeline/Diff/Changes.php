<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Globals\StringUtils;

class Changes
{
    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param array $rows - rows from DiffChange table
     */
    public function __construct(array $rows)
    {
        foreach ($rows as $row) {
            $this->properties[$row['Property']][] = [
                'OldVal' => $row['OldVal'],
                'NewVal' => $row['NewVal'],
                'ChangeDate' => new \DateTime($row['ChangeDate']),
            ];
        }
    }

    /**
     * @param string $propertyName
     * @return string
     */
    public function getPreviousValue($propertyName, ?\DateTime $minChangeDate = null)
    {
        $changedProperty = $this->getChangedProperty($propertyName, $minChangeDate);

        if (is_array($changedProperty)) {
            return $changedProperty['OldVal'];
        }

        return null;
    }

    public function getChangedProperty($propertyName, ?\DateTime $minChangeDate = null): ?array
    {
        if (
            !isset($this->properties[$propertyName])
            || StringUtils::isEmpty($this->properties[$propertyName])
        ) {
            return null;
        }

        if ($minChangeDate) {
            if ($minChangeDate > $this->properties[$propertyName][count($this->properties[$propertyName]) - 1]['ChangeDate']) {
                return null;
            }

            for ($i = count($this->properties[$propertyName]) - 1; $i >= 0; $i--) {
                $property = $this->properties[$propertyName][$i];

                if ($property['ChangeDate'] > $minChangeDate) {
                    return $property;
                }
            }

            return null;
        } else {
            return $this->properties[$propertyName][count($this->properties[$propertyName]) - 1];
        }
    }

    /**
     * @return array
     */
    public function getChangedProperties(?\DateTime $minChangeDate = null)
    {
        if ($minChangeDate) {
            $result = [];

            foreach ($this->properties as $name => $propertyChanges) {
                foreach ($propertyChanges as $propertyChange) {
                    if ($propertyChange['ChangeDate'] > $minChangeDate) {
                        $result[] = $name;

                        break;
                    }
                }
            }

            return $result;
        } else {
            return array_keys($this->properties);
        }
    }
}
