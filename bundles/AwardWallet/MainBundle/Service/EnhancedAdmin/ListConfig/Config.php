<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Symfony\Component\HttpFoundation\Request;

/**
 * @NoDI
 */
class Config
{
    /**
     * @var callable|null
     */
    protected $callbackQueryBuilder;
    private string $entity;

    private string $alias;

    private ?string $title = null;

    private ?string $beforeListHtml = null;

    private ?string $afterListHtml = null;

    private array $tableAttrs = [
        'class' => 'table table-striped table-bordered table-hover',
    ];

    private array $tableHeaderAttrs = [
        'class' => 'thead-dark',
    ];

    private int $page = 1;

    private array $pagesSizes = [
        50,
        100,
        200,
        500,
    ];

    private int $pageSize = 100;

    private ?Sortable $sort1 = null;

    /**
     * @var array FieldInterface[]
     */
    private array $fields = [];

    public function __construct(string $entity, string $alias = 'e')
    {
        $this->entity = $entity;
        $this->alias = $alias;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBeforeListHtml(): ?string
    {
        return $this->beforeListHtml;
    }

    public function setBeforeListHtml(?string $beforeListHtml): self
    {
        $this->beforeListHtml = $beforeListHtml;

        return $this;
    }

    public function getAfterListHtml(): ?string
    {
        return $this->afterListHtml;
    }

    public function setAfterListHtml(?string $afterListHtml): self
    {
        $this->afterListHtml = $afterListHtml;

        return $this;
    }

    public function getTableAttrs(): array
    {
        return $this->tableAttrs;
    }

    public function setTableAttrs(array $tableAttrs): self
    {
        $this->tableAttrs = $tableAttrs;

        return $this;
    }

    public function addTableAttr(string $attr, string $value): self
    {
        $this->tableAttrs[$attr] = $value;

        return $this;
    }

    public function appendTableAttr(string $attr, string $value): self
    {
        $this->tableAttrs[$attr] .= $value;

        return $this;
    }

    public function getTableHeaderAttrs(): array
    {
        return $this->tableHeaderAttrs;
    }

    public function setTableHeaderAttrs(array $tableHeaderAttrs): self
    {
        $this->tableHeaderAttrs = $tableHeaderAttrs;

        return $this;
    }

    public function addTableHeaderAttr(string $attr, string $value): self
    {
        $this->tableHeaderAttrs[$attr] = $value;

        return $this;
    }

    public function appendTableHeaderAttr(string $attr, string $value): self
    {
        $this->tableHeaderAttrs[$attr] .= $value;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getPagesSizes(): array
    {
        return $this->pagesSizes;
    }

    public function setPagesSizes(array $pagesSizes): self
    {
        $this->pagesSizes = $pagesSizes;

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function getSort1(): ?Sortable
    {
        return $this->sort1;
    }

    public function setSort1(?Sortable $sort1): self
    {
        $this->sort1 = $sort1;

        return $this;
    }

    public function addField(FieldInterface $field): self
    {
        $this->fields[] = $field;

        if ($field->isSortable() && $this->sort1 === null && $field->isPrimary()) {
            $this->sort1 = new Sortable($field, false);
        }

        return $this;
    }

    /**
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function setCallbackQueryBuilder(?callable $callbackQueryBuilder): self
    {
        $this->callbackQueryBuilder = $callbackQueryBuilder;

        return $this;
    }

    public function getCallbackQueryBuilder(): ?callable
    {
        return $this->callbackQueryBuilder;
    }

    public function getPrimaryField(): ?FieldInterface
    {
        foreach ($this->fields as $field) {
            if ($field->isPrimary()) {
                return $field;
            }
        }

        return null;
    }

    public function handleRequest(Request $request): self
    {
        // set page
        $page = $request->query->get('page', 1);

        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }

        $this->setPage((int) $page);

        // set page size
        $pageSize = $request->query->get('pageSize');

        if (!is_numeric($pageSize) || $pageSize < 1) {
            $pageSize = $this->getPagesSizes()[0];
        }

        if (!in_array($pageSize, $this->getPagesSizes())) {
            if ($pageSize < \min($this->getPagesSizes())) {
                $pageSize = \min($this->getPagesSizes());
            } elseif ($pageSize > \max($this->getPagesSizes())) {
                $pageSize = \max($this->getPagesSizes());
            } else {
                $closest = null;

                foreach ($this->getPagesSizes() as $size) {
                    if ($closest === null || abs($pageSize - $size) < abs($pageSize - $closest)) {
                        $closest = $size;
                    }
                }

                $pageSize = $closest;
            }
        }

        $this->setPageSize((int) $pageSize);

        // set sort1
        $sort1 = $request->query->get('sort1');
        $sort1Dir = $request->query->get('sort1Direction');

        if (!empty($sort1) && is_string($sort1) && !empty($sort1Dir) && is_string($sort1Dir)) {
            foreach ($this->getFields() as $field) {
                if ($field->isSortable() && $field->getProperty() === $sort1) {
                    $this->setSort1(new Sortable($field, strtolower($sort1Dir) === 'asc'));

                    break;
                }
            }
        }

        return $this;
    }

    public static function create(string $entity, string $alias = 'e'): self
    {
        return new self($entity, $alias);
    }
}
