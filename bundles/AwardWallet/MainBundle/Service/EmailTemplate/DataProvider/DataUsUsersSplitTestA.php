<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class DataUsUsersSplitTestA extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return self::getSplitOptions(parent::getQueryOptions(), 'a');
    }

    /**
     * @param Options[] $options
     * @return Options[]
     */
    public static function getSplitOptions(array $options, string $splitName): array
    {
        \array_walk($options, function ($option) use ($splitName) {
            /** @var Options $option */
            $option->userId = \json_decode(\file_get_contents(__DIR__ . "/../../../Resources/data/EmailTemplate/DataProvider/DataUsUsersSplit/{$splitName}.json"), true);
            $option->limit = 6500;
            $option->builderTransformator = function (QueryBuilder $queryBuilder, bool $isBusiness, string $paramPrefix) use ($option) {
                $queryBuilder->addOrderBy("field(u.UserID, :{$paramPrefix}fieldOrder)");
                $queryBuilder->setParameter(":{$paramPrefix}fieldOrder", $option->userId, Connection::PARAM_INT_ARRAY);
            };
        });

        return $options;
    }

    public static function parametrizedDescription(int $chunk): string
    {
        return "This is the <b>{$chunk}/3 slice</b><br/>
            On 14 January 2019 AW users from US\unknown (detected by last logon ip (if any) or registration ip) ware shuffled and split into 3 groups (6500 users each)";
    }

    public static function parametrizedTitle(int $chunk): string
    {
        return "{$chunk}/3 Group (6500 users from US\unknown) (date: 14 Jan 2019)";
    }

    public function getDescription(): string
    {
        return self::parametrizedDescription(1);
    }

    public function getTitle(): string
    {
        return self::parametrizedTitle(1);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_3_US;
    }
}
