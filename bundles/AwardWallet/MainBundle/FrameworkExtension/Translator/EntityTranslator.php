<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityTranslator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EntityManager
     */
    protected $em;

    private $metadata = [];

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(TranslatorInterface $translator, EntityManager $em, PropertyAccessorInterface $propertyAccessor)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function trans($entity, $property, array $parameters = [], $domain = null, $locale = null)
    {
        if ($locale == 'en' || (empty($locale) && $this->translator->getLocale() == 'en')) {
            return strtr($this->propertyAccessor->getValue($entity, $property), $parameters);
        }

        $meta = $this->getMetadata(get_class($entity));
        $id = $this->getTranslationId($entity, $property);
        $domain = (isset($domain)) ? $domain : strtolower($meta->getTableName());
        $result = $this->translator->trans(/** @Ignore */ $id, $parameters, $domain, $locale);

        if ($id !== $result) {
            return $result;
        }

        return strtr($this->propertyAccessor->getValue($entity, $property), $parameters);
    }

    public function transChoice($entity, $property, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $meta = $this->getMetadata(get_class($entity));
        $id = $this->getTranslationId($entity, $property);
        $domain = (isset($domain)) ? $domain : strtolower($meta->getTableName());

        return $this->translator->trans(/** @Ignore */ $id, array_merge($parameters, [
            '%count%' => $number,
        ]), $domain, $locale);
    }

    private function getTranslationId($entity, $property)
    {
        $meta = $this->getMetadata(get_class($entity));
        $identifier = $meta->getSingleIdentifierFieldName();

        return strtolower($meta->getFieldMapping($property)['columnName']) . "." .
            call_user_func([$entity, 'get' . ucwords(strtolower($identifier))]);
    }

    private function getMetadata($className)
    {
        if (isset($this->metadata[$className])) {
            return $this->metadata[$className];
        }

        return $this->metadata[$className] = $this->em->getClassMetadata($className);
    }
}
