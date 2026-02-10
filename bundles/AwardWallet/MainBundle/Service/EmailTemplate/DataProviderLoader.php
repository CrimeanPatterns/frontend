<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\Admin\EmailTemplateAdmin;
use AwardWallet\MainBundle\Entity\EmailTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DataProviderLoader
{
    public const DATA_PROVIDER_NAMESPACE = 'AwardWallet\\MainBundle\\Service\\EmailTemplate\\DataProvider';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFreeDataProviders()
    {
        $emailTempRep = $this->container->get('doctrine.orm.default_entity_manager')
            ->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class);
        $builder = $this->container->get('doctrine.orm.default_entity_manager')
            ->getConnection()->createQueryBuilder();
        $stm = $builder
            ->select('Code', 'EmailTemplateID')
            ->from('EmailTemplate', 't')
            ->execute();
        $used = [];

        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $used[$row['Code']] = $row['EmailTemplateID'];
        }

        $finder = (new Finder())
            ->files()
            ->name('*.php')
            ->in(__DIR__ . '/DataProvider')
            ->depth('== 0')
            ->sortByName();
        $result = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $className = self::DATA_PROVIDER_NAMESPACE . "\\" . $file->getBasename('.php');

            if (!class_exists($className)) {
                continue;
            }
            $reflClass = new \ReflectionClass($className);

            if (!$reflClass->isInstantiable()) {
                continue;
            }
            $code = self::getCodeByClass($className);

            $template = (new EmailTemplate())->setDataProvider($code)->setCode($code);
            $provider = $this->getDataProviderByEmailTemplate($template);
            $desc = $provider->getDescription();
            $title = $provider->getTitle();

            if ($provider->isDeprecated()) {
                $desc = self::deprecateDescription($desc);
                $title = self::deprecateTitle($title);
            }

            $result[] = [
                'code' => $code,
                'replacements' => $provider->getDataReplacements(),
                'testInfo' => $provider->getTestInfo(),
                'title' => $title,
                'desc' => $desc,
                'sortPriority' => $provider->getSortPriority(),
                'group' => $provider->getGroup(),
                'canBeExcluded' => $provider->canBeExcludedInAdminInterface(),
            ];
        }

        return
            it($result)
            ->usort(function (array $providerA, array $providerB) {
                return
                    ($providerB['sortPriority'] <=> $providerA['sortPriority']) ?: // sortPriority DESC
                    \strnatcmp($providerA['code'], $providerB['code']); // code ASC
            })
            ->toArrayWithKeys();
    }

    public function getDataProviderByEmailTemplate(EmailTemplate $template): AbstractDataProvider
    {
        $class = self::getFQCNByCode($template->getDataProvider());

        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf("Class '%s' not found", $class));
        }

        /** @var AbstractDataProvider $provider */
        $provider = new $class($this->container, $template);

        return $provider;
    }

    public static function getCodeByClass($class)
    {
        $parts = explode('\\', $class);
        $class = array_pop($parts);

        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $class));
    }

    public static function getFQCNByCode(string $code): string
    {
        return self::DATA_PROVIDER_NAMESPACE . "\\" . self::getClassNameByCode($code);
    }

    public static function expandExclusions(EmailTemplate $emailTemplate): array
    {
        return
            it($emailTemplate->getExclusions() ?? [])
            ->fold([[], []], function (array $acc, string $exclusion) {
                [$exclusionEmails, $exclusionProviders] = $acc;
                $parts = \explode(':', $exclusion);

                if (\count($parts) !== 2) {
                    throw new \LogicException('Invalid exclusion definition');
                }

                if (EmailTemplateAdmin::EXCLUSION_EMAIL === $parts[0]) {
                    $exclusionEmails[] = $parts[1];
                } elseif (EmailTemplateAdmin::EXCLUSION_PROVIDER === $parts[0]) {
                    $exclusionProviders[] = DataProviderLoader::getFQCNByCode($parts[1]);
                } else {
                    throw new \LogicException('Invalid exclusion definition');
                }

                return [$exclusionEmails, $exclusionProviders];
            });
    }

    private static function deprecateTitle(string $title): string
    {
        return "[DEPRECATED] {$title}";
    }

    private static function deprecateDescription(string $desc): string
    {
        return "
            <span style='color: red; font-weight: bold;'>[DEPRECATED]</span> This data provider is obsolete and yields incorrect results. Contact developer if you want to use it. This data provider is obsolete and yields incorrect results. Contact developer if you want to use it.
            <br/>
            <br/>
            <span style='text-decoration: line-through'>{$desc}</span>
        ";
    }

    private static function getClassNameByCode($code)
    {
        $code[0] = strtoupper($code[0]);

        return preg_replace_callback('/_([a-z])/', function ($v) {
            return strtoupper($v[1]);
        }, $code);
    }
}
