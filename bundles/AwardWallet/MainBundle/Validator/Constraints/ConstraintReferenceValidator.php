<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\CollectionUtils;
use AwardWallet\MainBundle\Validator\Constraints\Transformer\TransformerManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

class ConstraintReferenceValidator extends ConstraintValidator
{
    /**
     * @var MetadataFactoryInterface
     */
    private $factory;
    /**
     * @var TransformerManager
     */
    private $transformerManager;

    public function __construct(MetadataFactoryInterface $factory, TransformerManager $transformerManager)
    {
        $this->factory = $factory;
        $this->transformerManager = $transformerManager;
    }

    /**
     * @param ConstraintReference $constraint
     */
    public function validate($model, Constraint $constraint)
    {
        /** @var ClassMetadata $sourceClass */
        $sourceClass = $this->factory->getMetadataFor($constraint->sourceClass);
        /** @var ClassMetadata $targetClass */
        $targetClass = $this->factory->getMetadataFor(get_class($model));

        if (!$constraint->skipSourceClassConstraints) {
            $sourceClassConstraints = $this->filterExcludedConstraints($sourceClass->getConstraints(), $constraint->excludedConstraints);
            $sourceClassConstraints = $this->transformConstraints($sourceClassConstraints, $targetClass);

            $classConstraints = $this->filterByGroup(
                $sourceClassConstraints,
                $constraint->groupFilter,
                $constraint->clone
            );

            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($model, $classConstraints);
        }

        $targetReflectionPropertiesMap = self::getReflectionPropertiesMap($targetClass->getReflectionClass());

        if (isset($constraint->sourceProperty)) {
            foreach ($constraint->sourceProperty as $sourcePropertyIndex => $sourcePropertyName) {
                // assure that property exists
                $targetReflectionProperty = $targetReflectionPropertiesMap[$constraint->targetProperty[$sourcePropertyIndex]] ?? null;

                if ($targetReflectionProperty) {
                    $this->validateProperty($model, $sourcePropertyName, $targetReflectionProperty->getName(), $sourceClass, $targetClass, $constraint);
                }
            }
        } else {
            foreach ($targetReflectionPropertiesMap as $targetReflectionProperty) {
                $propertyName = $targetReflectionProperty->getName();

                $this->validateProperty($model, $propertyName, $propertyName, $sourceClass, $targetClass, $constraint);
            }
        }
    }

    /**
     * @param object $model
     * @param string $sourceName
     * @param string $targetName
     * @internal param ConstraintReference $constraintReference
     */
    protected function validateProperty($model, $sourceName, $targetName, ClassMetadata $sourceClass, ClassMetadata $targetClass, ConstraintReference $constraint)
    {
        if (
            ['*'] === $constraint->excludedProperties
            || in_array($targetName, $constraint->excludedProperties, true)
        ) {
            return;
        }

        // get properties with constraints from both source and target classes
        $targetClassConstrainedProperties = $targetClass->getConstrainedProperties();
        $sourceClassConstrainedProperties = $sourceClass->getConstrainedProperties();
        // normalize for case-insensitive match
        $sourceClassConstrainedPropertiesNormalized = array_map('strtolower', $sourceClassConstrainedProperties);

        $targetReflectionProperty = self::getReflectionPropertiesMap($targetClass->getReflectionClass())[$targetName];

        $propertyConstraints = [];

        // extract constraints from target class property
        if (in_array($targetName, $targetClassConstrainedProperties, true)) {
            /** @var PropertyMetadata $propertyMetadata */
            foreach ($targetClass->getPropertyMetadata($targetName) as $propertyMetadata) {
                $propertyConstraints = array_merge($propertyConstraints, $propertyMetadata->getConstraints());
            }
        }

        // extract constraints from source class property
        if (false !== ($foundKey = array_search(
            $sourceName,
            $constraint->caseInsensitive ?
                    $sourceClassConstrainedPropertiesNormalized :
                    $sourceClassConstrainedProperties,
            true
        ))
        ) {
            /** @var PropertyMetadata $propertyMetadata */
            foreach ($sourceClass->getPropertyMetadata($sourceClassConstrainedProperties[$foundKey]) as $propertyMetadata) {
                $propertyConstraints = array_merge($propertyConstraints, $propertyMetadata->getConstraints());
            }
        }

        if (!$propertyConstraints) {
            return;
        }

        $propertyConstraints = $this->filterExcludedConstraints($propertyConstraints, $constraint->excludedConstraints);
        $propertyConstraints = $this->filterByGroup($propertyConstraints, $constraint->groupFilter, $constraint->clone);

        $targetReflectionProperty->setAccessible(true);
        $propertyValue = $targetReflectionProperty->getValue($model);
        $targetReflectionProperty->setAccessible(false);

        $this->context
            ->getValidator()
            ->inContext($this->context)
            ->atPath($targetName)
            ->validate($propertyValue, $propertyConstraints);
    }

    /**
     * @param Constraint[] $constraints
     * @param string[] $exludedClasses
     * @return Constraint[]
     */
    protected function filterExcludedConstraints(array $constraints, $exludedClasses)
    {
        return array_filter(
            $constraints,
            function (Constraint $constraint) use ($exludedClasses) {
                $class = get_class($constraint);

                // filter constraint by class exclusions list
                return CollectionUtils::any(
                    $exludedClasses,
                    function ($excludedClass) use ($class) {
                        return strpos($class, $excludedClass) === 0;
                    }
                ) ? null : $constraint;
            }
        );
    }

    /**
     * @param Constraint[] $constraints
     * @return Constraint[]
     */
    protected function transformConstraints(array $constraints, ClassMetadata $classMetadata)
    {
        return array_filter(
            array_map(
                function (Constraint $constraint) use ($classMetadata) {
                    return $this->transformerManager->transform($constraint, $classMetadata);
                },
                $constraints
            )
        );
    }

    /**
     * @param Constraint[] $constraints
     * @param string[] $groupFilter
     * @param bool $needClone
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function filterByGroup(array $constraints, array $groupFilter, $needClone)
    {
        return array_filter(
            array_map(
                function (Constraint $constraint) use ($groupFilter, $needClone) {
                    if (!$groupFilter) {
                        if ($needClone) {
                            $constraint = clone $constraint;
                        }

                        $constraint->groups = [Constraint::DEFAULT_GROUP];

                        return $constraint;
                    } else {
                        return array_intersect($groupFilter, $constraint->groups) ? $constraint : null;
                    }
                },
                $constraints
            )
        );
    }

    /**
     * @return array<string, \ReflectionProperty>
     */
    private static function getReflectionPropertiesMap(\ReflectionClass $class): array
    {
        /** @var array<string, \ReflectionProperty> $propertiesMap */
        $propertiesMap = [];

        $go = function (\ReflectionClass $class) use (&$go, &$propertiesMap) {
            $parent = $class->getParentClass();

            if ($parent) {
                $go($parent);
            }

            // store current class properties after the recursion call, so properties from descendants will
            // overwrite parent properties if any

            foreach ($class->getProperties() as $reflectionProperty) {
                $propertiesMap[$reflectionProperty->getName()] = $reflectionProperty;
            }
        };
        $go($class);

        return $propertiesMap;
    }
}
