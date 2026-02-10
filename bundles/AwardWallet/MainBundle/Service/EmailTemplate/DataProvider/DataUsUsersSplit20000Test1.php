<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class DataUsUsersSplit20000Test1 extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return self::getSplitOptions(parent::getQueryOptions(), 1);
    }

    /**
     * @param Options[] $options
     * @return Options[]
     */
    public static function getSplitOptions(array $options, int $chunkNumber): array
    {
        \array_walk($options, function ($option) use ($chunkNumber) {
            /** @var Options $option */
            $option->userId = \json_decode(\file_get_contents($fileName = __DIR__ . "/../../../Resources/data/EmailTemplate/DataProvider/DataUsUsersSplit20000/chunk_{$chunkNumber}.json"), true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException("Invalid data in chunk: {$fileName}");
            }

            $option->limit = 20000;
            $option->builderTransformator = function (QueryBuilder $queryBuilder, bool $isBusiness, string $paramPrefix) use ($option) {
                $queryBuilder->addOrderBy("field(u.UserID, :{$paramPrefix}fieldOrder)");
                $queryBuilder->setParameter(":{$paramPrefix}fieldOrder", $option->userId, Connection::PARAM_INT_ARRAY);
            };
        });

        return $options;
    }

    public static function parametrizedDescription(int $chunk): string
    {
        return "This is the <b>{$chunk}/14 slice</b><br/>
            On 25 May 2019 AW users from US\unknown (detected by last logon ip (if any) or registration ip) were shuffled and split into 14 groups (20000 users each)";
    }

    public static function parametrizedTitle(int $chunk): string
    {
        return "{$chunk}/14 Group (20000 users from US\unknown) (date: 25 May 2019)";
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
        return Group::GROUPS_14_US;
    }
}
