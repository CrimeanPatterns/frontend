<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;

class ClickHouseService
{
    private const DBNAME_PREFIX = 'awardwallet_v';

    /** @var ParameterRepository */
    private $parameterRepository;

    /** @var string */
    private $activeDbName;

    public function __construct(
        ParameterRepository $parameterRepository
    ) {
        $this->parameterRepository = $parameterRepository;
    }

    public function getActiveDbName(): string
    {
        if (!empty($this->activeDbName)) {
            return $this->activeDbName;
        }

        $version = $this->parameterRepository->getParam(ParameterRepository::CLICKHOUSE_DB_VERSION);

        if (empty($version)) {
            throw new \RuntimeException('Parameter "' . ParameterRepository::CLICKHOUSE_DB_VERSION . '" not defined');
        }

        return $this->activeDbName = self::DBNAME_PREFIX . $version;
    }
}
