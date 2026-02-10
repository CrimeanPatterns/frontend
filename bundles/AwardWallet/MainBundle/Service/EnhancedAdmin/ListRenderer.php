<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\Config;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\FieldInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;

/**
 * @NoDI
 */
class ListRenderer
{
    private Environment $twig;

    private QueryBuilder $queryBuilder;

    private array $data;

    public function __construct(Environment $twig, QueryBuilder $queryBuilder, $data = [])
    {
        $this->twig = $twig;
        $this->queryBuilder = $queryBuilder;
        $this->data = $data;
    }

    /**
     * @param bool $loadCustomEntryPoint "page-manager/{schema}.list"
     */
    public function render(Config $config, bool $loadCustomEntryPoint = false): Response
    {
        $primaryFields = array_filter($config->getFields(), fn (FieldInterface $field) => $field->isPrimary());

        if (count($primaryFields) !== 1) {
            throw new \InvalidArgumentException('Config must have one primary field');
        }

        $totals = $this->queryBuilder->getTotals($config);
        $pagesCount = ceil($totals / $config->getPageSize());

        if ($config->getPage() > $pagesCount) {
            $config->setPage($pagesCount);
        }

        $data = array_merge($this->data, [
            'config' => $config,
            'items' => $this->queryBuilder->getItems($config),
            'totals' => $totals,
            'maxPage' => $pagesCount,
            'accessor' => PropertyAccess::createPropertyAccessor(),
        ]);

        if (!empty($config->getTitle())) {
            $data['title'] = $config->getTitle();
        }

        if ($loadCustomEntryPoint) {
            $data['customEntryPoint'] = sprintf('page-manager/%s.list', $data['schema']);
        }

        return new Response($this->twig->render('@Module/EnhancedAdmin/Template/list.html.twig', $data));
    }
}
