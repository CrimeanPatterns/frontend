<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class Entity2IdTransformer implements DataTransformerInterface
{
    protected EntityManagerInterface $em;

    protected string $entityClass;

    protected string $idField;

    protected bool $nullable = true;

    protected string $idType = 'numeric';

    protected bool $required = true;

    public function __construct(
        EntityManager $em,
        string $entityClass,
        string $idField,
        bool $nullable = true,
        string $idType = 'numeric',
        bool $required = true
    ) {
        $this->em = $em;
        $this->entityClass = $entityClass;
        $this->idField = $idField;
        $this->nullable = $nullable;
        $this->idType = $idType;
        $this->required = $required;
    }

    public function transform($entity)
    {
        if (null === $entity) {
            return '';
        }

        $class = $this->entityClass;

        if (!$entity instanceof $class) {
            throw new TransformationFailedException('Expected ' . $class);
        }

        return call_user_func([$entity, 'get' . $this->idField]);
    }

    public function reverseTransform($id)
    {
        if ($this->nullable && $id === null) {
            return null;
        }

        $function = 'is_' . $this->idType;

        if (!function_exists($function)) {
            throw new \InvalidArgumentException('Function "' . $function . '" does not exist');
        }

        if (!call_user_func($function, $id)) {
            if ($this->required) {
                throw new TransformationFailedException('Expected a ' . $this->idType);
            } else {
                return null;
            }
        }

        $rep = $this->em->getRepository($this->entityClass);
        $entity = call_user_func_array([$rep, 'findOneBy' . $this->idField], [$id]);

        if (!$entity) {
            if ($this->required) {
                throw new TransformationFailedException('Invalid id');
            } else {
                return null;
            }
        }

        return $entity;
    }
}
