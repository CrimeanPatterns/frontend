<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;

class Block
{
    public const KIND_STRING = 'string';
    public const KIND_BOXED = 'boxed';
    public const KIND_TEXT = 'text'; // longread or multiline
    public const KIND_TITLE = 'title';
    public const KIND_GROUP = 'group';
    public const KIND_CONFNO = 'confno';
    public const KIND_POINT = 'point';
    public const KIND_IMPORTANT = 'important';
    public const KIND_SHOWMORE = 'showmore';
    public const KIND_MAP = 'map';
    public const KIND_TIME = 'time';
    public const KIND_OFFER = 'offer';
    public const KIND_FLIGHT_PROGRESS = 'flightProgress';
    public const KIND_FLIGHT_NUMBER = 'flightNumber';
    public const KIND_SEPARATOR = 'separator';
    public const KIND_SAVINGS = 'savings';
    public const KIND_SOURCE = 'source';
    public const KIND_NO_FOREIGN_FEES_CARDS = 'no_foreign_fees_cards';
    public const KIND_ATTACHMENTS = 'attachments';
    public const KIND_NOTES_AND_FILES = 'notes_and_files';
    public const KIND_AI_RESERVATION = 'ai_reservation';

    /**
     * @var string
     */
    public $kind;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $icon;

    public $val;

    public $old;

    /**
     * @var bool
     */
    public $bold;

    /**
     * @var string
     */
    public $background;
    public ?Link $link = null;

    /**
     * Block constructor.
     *
     * @param string $kind
     * @param string $name
     * @param string $icon
     */
    public function __construct($kind, $icon, $name, $val = null, $old = null)
    {
        $this->kind = $kind;
        $this->name = $name;
        $this->val = $val;
        $this->old = $old;
        $this->icon = $icon;
    }

    /**
     * @return Block
     */
    public static function fromValue($value, $oldValue = null)
    {
        return self::fromNameValue(null, $value, $oldValue);
    }

    /**
     * @param string $icon
     * @return Block
     * @throws \RuntimeException
     */
    public static function fromIconValue($icon, $value, $oldValue = null)
    {
        return new static(self::getKindByValue($value), $icon, null, $value, $oldValue);
    }

    /**
     * @param string $kind
     * @param string $name
     * @return Block
     */
    public static function fromKindName($kind, $name)
    {
        return new static($kind, null, $name, null);
    }

    public static function fromKind($kind): self
    {
        return new static($kind, null, null, null);
    }

    /**
     * @param string $kind
     * @return static
     */
    public static function fromKindValue($kind, $value, $oldValue = null)
    {
        return new static($kind, null, null, $value, $oldValue);
    }

    /**
     * @param string $kind
     * @param string $name
     * @return Block
     */
    public static function fromKindNameValue($kind, $name, $value, $oldValue = null)
    {
        return new static($kind, null, $name, $value, $oldValue);
    }

    public static function fromNameIconValue($name, $icon, $value, $oldValue = null)
    {
        return new static(self::getKindByValue($value), $icon, $name, $value, $oldValue);
    }

    /**
     * @param string $name
     * @return static
     * @throws \RuntimeException
     */
    public static function fromNameValue($name, $value, $oldValue = null)
    {
        return new static(self::getKindByValue($value), null, $name, $value, $oldValue);
    }

    /**
     * @return static
     */
    public static function fromName(string $name)
    {
        return new static(null, null, $name, null, null);
    }

    /**
     * @return string
     */
    private static function getKindByValue($value)
    {
        if (is_object($value)) {
            $class = get_class($value);
            $pos = strrpos($class, '\\');
            $kind = lcfirst(substr($class, ($pos !== false) ? $pos + 1 : 0));
        } elseif (is_scalar($value)) {
            $kind = self::KIND_STRING;
        } else {
            throw new \RuntimeException('Unable to resolve block kind');
        }

        return $kind;
    }
}
