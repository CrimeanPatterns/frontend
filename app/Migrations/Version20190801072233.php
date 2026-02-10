<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\RewardsTransfer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20190801072233 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $assign = [
            'WithAmex' => 84,
            'WithChase' => 87,
            'WithCiti' => 364,
            'WithCapitalOne' => 104,
            'WithMarriott' => 17,
        ];

        $rewardsTransfer = [];

        foreach ($assign as $essentialKey => $sourceProviderId) {
            $targetProviders = $entityManager->getConnection()->fetchAll('select ProviderID from PointEssentialProgram where ' . $essentialKey . ' = 1');
            $targetProviders = array_column($targetProviders, 'ProviderID');

            for ($i = 0, $iCount = count($targetProviders); $i < $iCount; $i++) {
                $rewardsTransfer[] = [
                    'RewardsType' => RewardsTransfer::TYPE_MILEVALUE,
                    'SourceProviderID' => $sourceProviderId,
                    'TargetProviderID' => $targetProviders[$i],
                    'SourceRate' => 1,
                    'TargetRate' => 1,
                    'Enabled' => 1,
                ];
            }
        }

        foreach ($rewardsTransfer as $data) {
            $entityManager->getConnection()->insert('RewardsTransfer', $data);
        }
    }

    public function down(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->getConnection()->delete('RewardsTransfer', ['RewardsType' => RewardsTransfer::TYPE_MILEVALUE]);
    }
}
