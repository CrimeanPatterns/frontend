<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

abstract class AbstractField implements FieldInterface
{
    protected string $property;

    protected string $label;

    protected bool $custom = false;

    protected bool $primary = false;

    protected bool $autoFormat = true;

    /**
     * @var callable|null
     */
    protected $mapper;

    protected ?array $map = null;

    protected bool $isMappedHtml = false;

    protected array $columnAttrs = [];

    protected ?string $autocompleteId = null;

    protected bool $sortable = true;

    protected string $sortProperty;

    protected bool $filterable = true;

    public function __construct(string $property, ?string $label = null, bool $custom = false)
    {
        $this->property = $property;
        $this->sortProperty = $property;

        if (is_null($label)) {
            $this->label = $this->labelize($property);
        } else {
            $this->label = $label;
        }

        $this->custom = $custom;
    }

    public function setProperty(string $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setCustom(bool $custom = true): self
    {
        $this->custom = $custom;

        return $this;
    }

    public function isCustom(): bool
    {
        return $this->custom;
    }

    public function setPrimary(bool $primary = true): self
    {
        $this->primary = $primary;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function setAutoFormat(bool $autoFormat = true): self
    {
        $this->autoFormat = $autoFormat;

        return $this;
    }

    public function isAutoFormat(): bool
    {
        return $this->autoFormat;
    }

    public function setMapper(callable $mapper, bool $isHtml = false): self
    {
        $this->mapper = $mapper;
        $this->isMappedHtml = $isHtml;

        return $this;
    }

    public function setMapperViaMap(array $map, bool $isHtml = false): self
    {
        $this->mapper = function ($value) use ($map) {
            return $map[$value] ?? $value;
        };
        $this->map = $map;
        $this->isMappedHtml = $isHtml;

        return $this;
    }

    public function getMap(): ?array
    {
        return $this->map;
    }

    public function getMapper(): ?callable
    {
        return $this->mapper;
    }

    public function isMappedHtml(): bool
    {
        return $this->isMappedHtml;
    }

    public function getColumnAttrs(): array
    {
        return $this->columnAttrs;
    }

    public function addColumnAttr(string $attr, string $value): self
    {
        $this->columnAttrs[$attr] = $value;

        return $this;
    }

    public function appendColumnAttr(string $attr, string $value): self
    {
        $this->columnAttrs[$attr] .= $value;

        return $this;
    }

    public function setColumnAttrs(array $attrs): self
    {
        $this->columnAttrs = $attrs;

        return $this;
    }

    public function autocomplete(string $id): self
    {
        $this->autocompleteId = $id;

        return $this;
    }

    public function getAutocompleteId(): ?string
    {
        return $this->autocompleteId;
    }

    public function setSortable(bool $sortable = true, ?string $sortProperty = null): self
    {
        $this->sortable = $sortable;

        if (!is_null($sortProperty)) {
            $this->sortProperty = $sortProperty;
        }

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getSortProperty(): string
    {
        return $this->sortProperty;
    }

    public function setFilterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    private function labelize(string $property): string
    {
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $property);
        $label = str_replace('_', ' ', $label);

        return ucfirst($label);
    }
}
