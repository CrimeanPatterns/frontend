<?php

namespace AwardWallet\MainBundle\Admin\Filter;

use AwardWallet\MainBundle\Globals\FunctionalUtils;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;

class NumberRangeFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data): void
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        $type = $data['type'] ?? false;

        $operator = $this->getOperator($type);

        if ($type) {
            if (!is_numeric($data['value'])) {
                return;
            }

            if (!$operator) {
                $operator = '=';
            }
            // c.name > '1' => c.name OPERATOR :FIELDNAME
            $parameterName = $this->getNewParameterName($queryBuilder);
            $this->applyWhere($queryBuilder, sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            $parts = array_map(FunctionalUtils::composition('trim', 'intval'), explode('-', $data['value']));

            if (count($parts) !== 2) {
                return;
            }

            // c.name > '1' => c.name OPERATOR :FIELDNAME
            $parameterLower = $this->getNewParameterName($queryBuilder);
            $parameterUpper = $this->getNewParameterName($queryBuilder);

            $this->applyWhere($queryBuilder, "({$alias}.{$field} >= :{$parameterLower} AND {$alias}.{$field} <= :{$parameterUpper})");
            $queryBuilder->setParameter($parameterLower, $parts[0]);
            $queryBuilder->setParameter($parameterUpper, $parts[1]);
        }
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function getRenderSettings(): array
    {
        // NEXT_MAJOR: Remove this line when drop Symfony <2.8 support
        $type = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Sonata\AdminBundle\Form\Type\Filter\NumberType'
            : 'sonata_type_filter_number';

        return [$type, $this->getFormOptions()];
    }

    public function getFormOptions(): array
    {
        return [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ];
    }

    /**
     * @param string $type
     * @return bool
     */
    private function getOperator($type)
    {
        $choices = [
            NumberOperatorType::TYPE_EQUAL => '=',
            NumberOperatorType::TYPE_GREATER_EQUAL => '>=',
            NumberOperatorType::TYPE_GREATER_THAN => '>',
            NumberOperatorType::TYPE_LESS_EQUAL => '<=',
            NumberOperatorType::TYPE_LESS_THAN => '<',
        ];

        return $choices[$type] ?? false;
    }
}
