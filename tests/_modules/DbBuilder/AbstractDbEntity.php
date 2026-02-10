<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

abstract class AbstractDbEntity
{
    private const STATE_NEW = 0;

    private const STATE_MAKING = 1;

    private const STATE_MADE = 2;

    private array $fields;

    private int $state = self::STATE_NEW;

    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * @return bool false - if object is already made, true - if object is in progress
     */
    final public function startMaking(): bool
    {
        if ($this->state === self::STATE_MAKING) {
            throw new \RuntimeException('Object is already in progress, possible cycle');
        }

        if ($this->state === self::STATE_MADE) {
            return false;
        }

        $this->state = self::STATE_MAKING;

        return true;
    }

    final public function finishMaking(?callable $finishCallback = null)
    {
        if ($this->state === self::STATE_NEW) {
            throw new \RuntimeException('Object is not in progress');
        }

        if ($this->state === self::STATE_MADE) {
            throw new \RuntimeException('Object is already made');
        }

        $this->state = self::STATE_MADE;

        if ($finishCallback) {
            return $finishCallback();
        }
    }

    final public function isNew(): bool
    {
        return $this->state === self::STATE_NEW;
    }

    final public function isMaking(): bool
    {
        return $this->state === self::STATE_MAKING;
    }

    final public function isMade(): bool
    {
        return $this->state === self::STATE_MADE;
    }

    final public function isMakeable(): bool
    {
        return $this->isNew() || $this->isMade();
    }

    final public function getFields(): array
    {
        return $this->fields;
    }

    final public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    final public function extendFields(array $fields): self
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
     * @return mixed|null array - composite primary key
     */
    final public function getId()
    {
        $pk = $this->getPrimaryKey();

        if (is_array($pk)) {
            $id = [];

            foreach ($pk as $key) {
                $id[] = $this->fields[$key] ?? null;
            }

            return $id;
        }

        return $this->fields[$pk] ?? null;
    }

    public function getTableName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return string|string[] array - composite primary key
     */
    public function getPrimaryKey()
    {
        return (new \ReflectionClass($this))->getShortName() . 'ID';
    }
}
