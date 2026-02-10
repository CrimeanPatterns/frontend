<?php

namespace AwardWallet\MainBundle\Entity;

use JMS\Serializer\Annotation as Serializer;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Vehicle implements TranslationContainerInterface
{
    public const TRANSLATION_DOMAIN = 'trips';

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $model;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $length;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $height;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $width;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getLength(): ?string
    {
        return $this->length;
    }

    public function setLength(?string $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getHeight(): ?string
    {
        return $this->height;
    }

    public function setHeight(?string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(?string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public static function getPropertyMessagesArray(): array
    {
        return [
            'type' => 'vehicle.type',
            'model' => 'vehicle.model',
            'length' => 'vehicle.length',
            'height' => 'vehicle.height',
            'width' => 'vehicle.width',
        ];
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        $domain = self::TRANSLATION_DOMAIN;

        return [
            (new Message('vehicle.type', $domain))->setDesc('Type'),
            (new Message('vehicle.model', $domain))->setDesc('Model'),
            (new Message('vehicle.length', $domain))->setDesc('Length'),
            (new Message('vehicle.height', $domain))->setDesc('Height'),
            (new Message('vehicle.width', $domain))->setDesc('Width'),
        ];
    }
}
