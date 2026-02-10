<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Entity\Groupuserlink;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

class AT201GroupAdmin extends AbstractAdmin
{
    public function __construct()
    {
        $this->setUniqId('at-201-group');
    }

    public function getPerPageOptions(): array
    {
        return [100, 500, 1_000, 100_000];
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        parent::configureDefaultSortValues($sortValues);

        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'groupuserlinkid';
        $sortValues[DatagridInterface::PER_PAGE] = 1_000;
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $query->andWhere(
            $query->expr()->eq($query->getRootAliases()[0] . '.sitegroupid', 75)
        );

        return $query;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('userid.email', null, ['label' => 'Email'])
            ->add('userid.login', null, ['label' => 'Username'])
            ->add('userid.firstname', null, ['label' => 'First name'])
            ->add('userid.lastname', null, ['label' => 'Last name']);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('userid.userid', null, ['label' => 'UserID'])
            ->add('userid.fullName', null, ['label' => 'Full Name'])
            ->add('userid.login', null, ['label' => 'Username'])
            ->add('userid.email', null, ['label' => 'Email'])
            ->add('userid.at201ExpirationDate', null, ['label' => 'AT201 Expiration Date'])
            ->add(
                'userid',
                'string',
                [
                    'label' => 'Facebook Link',
                    'template' => '@AwardWalletMain/Sonata/CRUD/AT201Group/list/user.html.twig',
                ]
            )
            ->add(
                'isActiveAT201',
                'boolean',
                [
                    'accessor' => fn (Groupuserlink $entity) => $entity,
                    'label' => 'Active Subscription',
                    'template' => '@AwardWalletMain/Sonata/CRUD/AT201Group/list/active201.html.twig',
                ]
            );
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'at_201_group';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'at-201-group';
    }
}
