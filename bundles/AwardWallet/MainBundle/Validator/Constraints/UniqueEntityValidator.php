<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MobileBundle\Form\Model\EntityContainerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

class UniqueEntityValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $registry;
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(ManagerRegistry $registry, MetadataFactoryInterface $metadataFactory)
    {
        $this->registry = $registry;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param EntityContainerInterface $value
     * @throws UnexpectedTypeException
     * @throws ConstraintDefinitionException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueEntity) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\UniqueEntity');
        }

        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (null !== $constraint->errorPath && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        $fields = (array) $constraint->fields;

        $entity = $value->getEntity();

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        if ($constraint->em) {
            $em = $this->registry->getManager($constraint->em);

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Object manager "%s" does not exist.', $constraint->em));
            }
        } else {
            $em = $this->registry->getManagerForClass(get_class($entity));

            if (!$em) {
                throw new ConstraintDefinitionException(sprintf('Unable to find the object manager associated with an entity of entityClass "%s".', get_class($entity)));
            }
        }
        /** @var ClassMetadata $valueClass */
        $valueClass = $this->metadataFactory->getMetadataFor(get_class($value));

        /* @var $entityClass \Doctrine\Persistence\Mapping\ClassMetadata */
        $entityClass = $em->getClassMetadata(get_class($entity));

        $criteria = [];

        foreach ($fields as $fieldName) {
            if (!$entityClass->hasField($fieldName) && !$entityClass->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf("The field '%s' is not mapped by Doctrine, so it cannot be validated for uniqueness.", $fieldName));
            }

            $entityClass->reflFields[$fieldName]->getValue($entity);

            $reflProperty = $valueClass->getReflectionClass()->getProperty($fieldName);
            $reflProperty->setAccessible(true);
            $criteria[$fieldName] = $reflProperty->getValue($value);
            $reflProperty->setAccessible(false);

            if ($constraint->ignoreNull && null === $criteria[$fieldName]) {
                return;
            }

            if (null !== $criteria[$fieldName] && $entityClass->hasAssociation($fieldName)) {
                /* Ensure the Proxy is initialized before using reflection to
                 * read its identifiers. This is necessary because the wrapped
                 * getter methods in the Proxy are being bypassed.
                 */
                $em->initializeObject($criteria[$fieldName]);

                $relatedClass = $em->getClassMetadata($entityClass->getAssociationTargetClass($fieldName));
                $relatedId = $relatedClass->getIdentifierValues($criteria[$fieldName]);

                if (count($relatedId) > 1) {
                    throw new ConstraintDefinitionException("Associated entities are not allowed to have more than one identifier field to be part of a unique constraint in: " . $entityClass->getName() . "#" . $fieldName);
                }
                $criteria[$fieldName] = array_pop($relatedId);
            }
        }

        $repository = $em->getRepository(get_class($entity));
        $result = $repository->{$constraint->repositoryMethod}($criteria);

        /* If the result is a MongoCursor, it must be advanced to the first
         * element. Rewinding should have no ill effect if $result is another
         * iterator implementation.
         */
        if ($result instanceof \Iterator) {
            $result->rewind();
        } elseif (is_array($result)) {
            reset($result);
        }

        /* If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (0 === count($result) || (1 === count($result) && $entity === ($result instanceof \Iterator ? $result->current() : current($result)))) {
            return;
        }

        $errorPath = null !== $constraint->errorPath ? $constraint->errorPath : $fields[0];
        $invalidValue = $criteria[$errorPath] ?? $criteria[$fields[0]];

        $this->context
            ->buildViolation($constraint->message)
            ->atPath($errorPath)
            ->setInvalidValue($invalidValue)
            ->addViolation();
    }
}
