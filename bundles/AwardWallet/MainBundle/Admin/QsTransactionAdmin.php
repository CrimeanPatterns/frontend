<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Entity\QsTransaction;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Routing\RouterInterface;

class QsTransactionAdmin extends AbstractAdmin
{
    protected RouterInterface $router;
    protected DataProviderLoader $dataProviderLoader;
    protected EntityManager $em;

    public function __construct(
        RouterInterface $router,
        DataProviderLoader $loader,
        EntityManager $em
    ) {
        $this->router = $router;
        $this->dataProviderLoader = $loader;
        $this->em = $em;
        $this->setUniqId('qs_transaction');
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query->setMaxResults(10);

        return parent::configureQuery($query);
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $list = parent::configureActionButtons($buttonList, $action, $object);

        $list['custom_action'] = [
            'template' => '@AwardWalletMain/Sonata/CRUD/QsTransaction/upload_transaction.html.twig',
        ];

        $list['list'] = [
            'template' => '@AwardWalletMain/Sonata/CRUD/QsTransaction/list__action.html.twig',
        ];

        return $list;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('source')
            ->add('clickDate');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('account', FieldDescriptionInterface::TYPE_CHOICE, [
                'editable' => true,
                'class' => 'AwardWallet\MainBundle\Entity\QsTransaction',
                'choices' => QsTransaction::ACCOUNTS,
            ])
            ->add('card')
            ->add('user', FieldDescriptionInterface::TYPE_STRING, ['template' => '@AwardWalletMain/Sonata/CRUD/QsTransaction/list/user.html.twig']);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('import');
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'qs_transaction';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'qs_transaction';
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        parent::configureDefaultSortValues($sortValues);

        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }
}
