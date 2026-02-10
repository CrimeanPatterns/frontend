<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class ConstraintReference.
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION", "PROPERTY"})
 */
class ConstraintReference extends Constraint
{
    /**
     * @var string
     */
    public $sourceClass;
    /**
     * @var bool
     */
    public $skipSourceClassConstraints = false;
    /**
     * @var string|string[]
     */
    public $sourceProperty;
    /**
     * @var string
     */
    public $targetProperty;
    /**
     * @var string[]
     */
    public $excludedConstraints = [];
    /**
     * @var bool case-sensitiveness of property matching
     */
    public $caseInsensitive = false;
    /**
     * @var string[]
     */
    public $excludedProperties = [];
    /**
     * @var string[]
     */
    public $groupFilter = [];
    /**
     * @var bool
     */
    public $clone = false;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (!isset($this->sourceProperty)) {
            return;
        }

        $newSource = [];
        $newTarget = [];

        if (!is_array($this->sourceProperty)) {
            if (isset($this->targetProperty)) {
                $this->sourceProperty = [$this->sourceProperty => $this->targetProperty];
            } else {
                $this->sourceProperty = [$this->sourceProperty => $this->sourceProperty];
            }
        }

        $this->targetProperty = [];

        foreach ($this->sourceProperty as $source => $target) {
            if (is_int($source)) {
                $source = $target;
            }

            $newSource[] = $source;
            $newTarget[] = $target;
        }

        $this->sourceProperty = $newSource;
        $this->targetProperty = $newTarget;
    }

    public function getTargets()
    {
        return [parent::CLASS_CONSTRAINT, parent::PROPERTY_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'constraint_reference';
    }
}
