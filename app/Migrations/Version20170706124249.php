<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170706124249 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ProviderRepository
     */
    private $provRep;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->provRep = $container->get('aw.repository.provider');
    }

    public function up(Schema $schema): void
    {
        $providersStmt = $this->connection->executeQuery("
            select
                p.ProviderID,
                p.ShortName,
                p.Name,
                p.Site,
                p.LoginURL,
                p.KeyWords,
                GROUP_CONCAT(pc.Site separator ',') as `Sites`,
                GROUP_CONCAT(pc.LoginURL separator ',') as `LoginURLs`
            from Provider p
            join ProviderCountry pc on p.ProviderID = pc.ProviderID
            group by p.ProviderID
        ");

        while ($provider = $providersStmt->fetch(\PDO::FETCH_ASSOC)) {
            $keywords = [];

            foreach (['Name', 'ShortName'] as $nameKey) {
                $keywords[] = strtolower(trim(preg_replace(["/[^a-zA-Z0-9\-]/ims", "/\s{2,}/"], [" ", " "], htmlspecialchars_decode($provider[$nameKey]))));
            }

            foreach (['Site', 'LoginURL', 'Sites', 'LoginURLs'] as $key) {
                foreach (explode(',', $provider[$key]) as $site) {
                    if (
                        (false !== ($parsed = parse_url($site)))
                        && isset($parsed['host'])
                    ) {
                        $keywords[] = preg_replace('/^www\./ims', '', $parsed['host']);
                    }
                }
            }

            $keywords = $this->provRep->modifyKeywords($provider['KeyWords'], $keywords);

            $this->connection->executeUpdate('
                update Provider
                set KeyWords = ?
                where 
                    ProviderID = ? and ' .
                    (StringUtils::isNotEmpty($provider['KeyWords']) ?
                        'KeyWords = ?' :
                        "(KeyWords = '' or Keywords is null or null = ?)"
                    ),
                [
                    $keywords,
                    $provider['ProviderID'],
                    $provider['KeyWords'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
