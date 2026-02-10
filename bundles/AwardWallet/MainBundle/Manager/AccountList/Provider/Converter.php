<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Manager\AccountList\Classes\AbstractConverter;

/**
 * Class Converter.
 *
 * @property Provider $entity
 * @method Provider getEntity()
 */
class Converter extends AbstractConverter
{
    /**
     * @var string
     */
    public $type = 'Provider';

    /**
     * @var int
     */
    public $kind;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $displayName;

    /**
     * @var StatsProperty
     */
    public $stats;

    /**
     * @var int
     */
    public $accounts;

    /**
     * @var AllianceProperty
     */
    public $alliance;

    /**
     * @var EliteLevelProperty[]
     */
    public $eliteLevels;

    public function __construct(Provider $provider, ?Builder $builder = null)
    {
        $this->entity = $provider;
        $this->builder = $builder;

        $this->id = $this->entity->getProviderid();
        $this->kind = $this->entity->getKind();
        $this->name = $this->entity->getName();
        $this->displayName = $this->entity->getDisplayname();

        if ($this->builder) {
            if ($this->entity->getAllianceid()) {
                $builder->getResolver('alliance')->add($this);
            }
        }
    }

    /**
     * @param int|null $accounts
     */
    public function setAccounts($accounts = null)
    {
        $this->accounts = $accounts;
    }

    public function setStats(?StatsProperty $stats = null)
    {
        $this->stats = $stats;
    }

    public function setAlliance(?AllianceProperty $alliance = null)
    {
        $this->alliance = $alliance;
    }

    /**
     * @param EliteLevelProperty[] $levels
     */
    public function setEliteLevels(?array $levels = null)
    {
        $this->eliteLevels = $levels;
    }
}
