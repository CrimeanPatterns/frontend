<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20191008111213 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        $passports = $connection->fetchAll("
            SELECT
                    ProviderCouponID, UserID, CustomFields
            FROM
                    ProviderCoupon
            WHERE
                    Kind = 11
                AND ProgramName = 'Passport'
                AND CustomFields NOT LIKE '%country\":null%'
        ");

        if (empty($passports)) {
            return;
        }

        $assign = [
            230 => ['USA', 'United States', 'United States of America', 'NY', 'US Department of State', 'New York, USA', 'U.S.', 'USA (Green Card)'],
            229 => ['London', 'UK', 'GB'],
            98 => ['India'],
            46 => ['China', 'CHN'],
            47 => ['Taiwan'],
            40 => ['Canada'],
            18 => ['AZ'],
            82 => ['Deutschland'],
            16 => ['Australia'],
            200 => ['South Africa'],
            179 => ['Ru', 'Russian Federation'],
            172 => ['PHILIPPINES'],
        ];

        $success = [];
        $failure = [];

        foreach ($passports as $passport) {
            $setCountryId = null;
            $customFields = json_decode($passport['CustomFields'], true);

            if (empty($customFields) || (!empty($customFields['passport']['country']) && is_int($customFields['passport']['country']))) {
                continue;
            }

            $countryName = urldecode($customFields['passport']['country']);

            if (filter_var($countryName, FILTER_SANITIZE_NUMBER_INT) == $countryName) {
                continue;
            }

            $foundCountryId = $connection->fetchColumn('SELECT CountryID FROM Country WHERE ' . $connection->quoteIdentifier('Name') . ' LIKE ' . $connection->quote($countryName));

            if (empty($foundCountryId)) {
                foreach ($assign as $countryId => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (false !== stripos($keyword, $countryName)) {
                            $setCountryId = $countryId;

                            break 2;
                        }
                    }
                }
            } else {
                $setCountryId = $foundCountryId;
            }

            if (!empty($setCountryId)) {
                $customFields['passport']['country'] = $setCountryId;
                $connection->update('ProviderCoupon', ['CustomFields' => json_encode($customFields)], ['ProviderCouponID' => $passport['ProviderCouponID']]);
                $success[] = '[UserID: ' . $passport['UserID'] . ', ProviderCouponID: ' . $passport['ProviderCouponID'] . '] - "' . $countryName . '" => CountryID: ' . $setCountryId;
            } else {
                $failure[] = '[UserID: ' . $passport['UserID'] . ', ProviderCouponID: ' . $passport['ProviderCouponID'] . '] - "' . $countryName . '" ';
            }
        }

        echo 'SUCCESS:' . PHP_EOL, implode("\r\n", $success),
            PHP_EOL . str_repeat('-', 8) . PHP_EOL,
            'FAILURE: ' . PHP_EOL, implode("\r\n", $failure) . PHP_EOL;
    }

    public function down(Schema $schema): void
    {
    }
}
