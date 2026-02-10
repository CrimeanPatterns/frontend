<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall;

use Symfony\Component\PropertyAccess\PropertyPath;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ToggleButton
{
    private string $icon;
    private string $label;
    private string $toggledLabel;
    private array $fields;
    private bool $allFieldsRequiredForToggle;
    private array $controlFields;
    private string $separatorName;
    private bool $toggled = false;

    /**
     * @param string[] $fields
     */
    public function __construct(
        string $icon,
        string $label,
        string $toggledLabel,
        array $fields,
        string $separatorName,
        array $controlFields = [],
        bool $allFieldsRequiredForToggle = false
    ) {
        $this->icon = $icon;
        $this->label = $label;
        $this->fields = $fields;
        $this->allFieldsRequiredForToggle = $allFieldsRequiredForToggle;

        if (!$controlFields) {
            $controlFields = $fields;
        }

        $this->controlFields =
                it($controlFields)
                ->map(static fn (string $field) => new PropertyPath($field))
                ->toArray();
        $this->separatorName = $separatorName;
        $this->toggledLabel = $toggledLabel;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getToggledLabel(): string
    {
        return $this->toggledLabel;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function isAllFieldsRequiredForToggle(): bool
    {
        return $this->allFieldsRequiredForToggle;
    }

    /**
     * @return PropertyPath[]
     */
    public function getControlFields(): array
    {
        return $this->controlFields;
    }

    public function getSeparatorName(): string
    {
        return $this->separatorName;
    }

    /**
     * @param string[] $exisingFields
     */
    public function toggle(array $exisingFields, bool $toggled): self
    {
        $new = new self(
            $this->icon,
            $this->label,
            $this->toggledLabel,
            $exisingFields,
            $this->separatorName,
            $this->controlFields,
            $this->allFieldsRequiredForToggle
        );
        $new->toggled = $toggled;

        return $new;
    }

    public function isToggled(): bool
    {
        return $this->toggled;
    }
}
