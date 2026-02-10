<?php

namespace AwardWallet\MainBundle\Globals\Utils
{
    class Criteria extends \Doctrine\Common\Collections\Criteria
    {
    }
}

namespace AwardWallet\MainBundle\Globals\Utils\Criteria\Expression
{
    use Doctrine\Common\Collections\Expr\Comparison;
    use Doctrine\Common\Collections\Expr\CompositeExpression;
    use Doctrine\Common\Collections\Expr\Value;

    /**
     * @return CompositeExpression
     */
    function andX($x = null)
    {
        return new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
    }

    /**
     * @return CompositeExpression
     */
    function orX($x = null)
    {
        return new CompositeExpression(CompositeExpression::TYPE_OR, func_get_args());
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function eq($field, $value)
    {
        return new Comparison($field, Comparison::EQ, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function gt($field, $value)
    {
        return new Comparison($field, Comparison::GT, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function lt($field, $value)
    {
        return new Comparison($field, Comparison::LT, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function gte($field, $value)
    {
        return new Comparison($field, Comparison::GTE, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function lte($field, $value)
    {
        return new Comparison($field, Comparison::LTE, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function neq($field, $value)
    {
        return new Comparison($field, Comparison::NEQ, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function isNull($field)
    {
        return new Comparison($field, Comparison::EQ, new Value(null));
    }

    /**
     * @param string $field
     * @param mixed  $values
     * @return Comparison
     */
    function in($field, array $values)
    {
        return new Comparison($field, Comparison::IN, new Value($values));
    }

    /**
     * @param string $field
     * @param mixed  $values
     * @return Comparison
     */
    function notIn($field, array $values)
    {
        return new Comparison($field, Comparison::NIN, new Value($values));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function contains($field, $value)
    {
        return new Comparison($field, Comparison::CONTAINS, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function memberOf($field, $value)
    {
        return new Comparison($field, Comparison::MEMBER_OF, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function startsWith($field, $value)
    {
        return new Comparison($field, Comparison::STARTS_WITH, new Value($value));
    }

    /**
     * @param string $field
     * @return Comparison
     */
    function endsWith($field, $value)
    {
        return new Comparison($field, Comparison::ENDS_WITH, new Value($value));
    }
}
