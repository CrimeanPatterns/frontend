<?php

declare(strict_types=1);

namespace AwardWallet\Tests\Unit\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use AwardWallet\MainBundle\Service\EmailTemplate\Query;
use AwardWallet\MainBundle\Service\EmailTemplate\QueryBuilder;
use AwardWallet\Tests\Unit\BaseContainerTest;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class QueryBuilderTest extends BaseContainerTest
{
    public function testBusinessAdminShouldReceiveOneEmail(): void
    {
        $template =
            (new EmailTemplate())
            ->setCode(StringUtils::getRandomCode(20))
            ->setDataProvider('data_all_users')
            ->setRenderEngine(1)
            ->setEnabled(true);

        $this->em->persist($template);
        $this->em->flush();

        $businessUserId = $this->aw->createBusinessUserWithBookerInfo('testdp' . StringUtils::getRandomCode(10));
        $businessAdminId = $this->aw->createStaffUserForBusinessUser($businessUserId);
        $options = new Options();
        $options->userId = [$businessAdminId];

        $queryBuilder = new QueryBuilder($this->em, $this->container);
        /** @var Query $query */
        $query = $queryBuilder->getQuery([$options]);
        $stmt = $query->getStatement();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        assertCount(1, $users);
        assertEquals('1', $users['0']['isBusiness']);
    }
}
