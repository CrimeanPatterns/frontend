<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

interface FieldInterface
{
    public function setProperty(string $property): self;

    public function getProperty(): string;

    public function setLabel(string $label): self;

    public function getLabel(): string;

    public function setCustom(bool $custom): self;

    public function isCustom(): bool;

    public function setPrimary(bool $primary = true): self;

    public function isPrimary(): bool;

    public function setAutoFormat(bool $autoFormat): self;

    public function isAutoFormat(): bool;

    public function setMapper(callable $mapper, bool $isHtml = false): self;

    public function setMapperViaMap(array $map, bool $isHtml = false): self;

    public function getMap(): ?array;

    public function getMapper(): ?callable;

    public function isMappedHtml(): bool;

    public function getColumnAttrs(): array;

    public function addColumnAttr(string $attr, string $value): self;

    public function appendColumnAttr(string $attr, string $value): self;

    public function setColumnAttrs(array $attrs): self;

    public function autocomplete(string $id): self;

    public function getAutocompleteId(): ?string;

    public function setSortable(bool $sortable = true, ?string $sortProperty = null): self;

    public function isSortable(): bool;

    public function getSortProperty(): string;

    public function setFilterable(bool $filterable): self;

    public function isFilterable(): bool;
}
