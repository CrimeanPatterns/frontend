<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\Demo;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\BooleanField;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\Config;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\DateTimeField;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\IntegerField;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\Sortable;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\StringField;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ListRenderer;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoungeEnhancedSchema implements ListActionInterface
{
    private Config $config;

    public function __construct()
    {
        $this->config = Config::create(Lounge::class)
            ->setCallbackQueryBuilder(
                function (QueryBuilder $qb) {
                    $qb->join('e.checkedBy', 'u');
                }
            )
            ->addField(
                IntegerField::create('id')
                    ->setPrimary()
            )
            ->addField($nameField = StringField::create('name'))
            ->addField(StringField::create('airportCode')->setMapper(
                function (string $raw, string $formatted, Lounge $lounge) {
                    return sprintf('<b class="badge badge-info">%s</b>', $formatted);
                },
                true
            ))
            ->addField(BooleanField::create('isAvailable'))
            ->addField(
                DateTimeField::create('createDate')
                    ->setLabel('Created')
                    ->setSortable(false)
            )
            ->addField(
                StringField::create('checkedBy.login')
                    ->setSortable(true, 'u.login')
                    ->setLabel('Checked by')
            )
            ->setSort1(new Sortable($nameField, true));
    }

    public static function getSchema(): string
    {
        return 'Lounge';
    }

    public function listAction(Request $request, ListRenderer $renderer): Response
    {
        return $renderer->render(
            $this->config->handleRequest($request)
        );
    }
}
