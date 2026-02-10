<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class QueryBuilderWithMetaGroupBy extends \Doctrine\DBAL\Query\QueryBuilder
{
    /**
     * @var string[]
     */
    private $metaGroupBy = [];

    /**
     * @var string[]
     */
    private $metaSelect = [];

    /**
     * @var list<string>
     */
    private $with = [];

    /**
     * @return string[]
     */
    public function getMetaGroupBy(): array
    {
        return $this->metaGroupBy;
    }

    /**
     * @param string[] $metaGroupBy
     */
    public function setMetaGroupBy(array $metaGroupBy): QueryBuilderWithMetaGroupBy
    {
        $this->metaGroupBy = $metaGroupBy;

        return $this;
    }

    public function setMetaSelect(array $metaSelect): QueryBuilderWithMetaGroupBy
    {
        $this->metaSelect = $metaSelect;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getMetaSelect(): array
    {
        return $this->metaSelect;
    }

    public function addWith(string $with): self
    {
        $this->with[] = $with;

        return $this;
    }

    public function getWith(): array
    {
        return $this->with;
    }
}
