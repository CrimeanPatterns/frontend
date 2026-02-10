<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AbShareAdmin extends AbstractAdmin
{
    public function __construct()
    {
        $this->classnameLabel = 'Share accounts';
        $this->setUniqId('abshare');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return bool|void
     */
    public function userNameFilter(ProxyQuery $queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }

        $e = $queryBuilder->expr();
        $queryBuilder->join(Usr::class, 'user', 'WITH', $alias . '.user = user.userid');
        $queryBuilder->andWhere($e->orX(
            $e->like('user.login', $e->literal('%' . $value['value'] . '%')),
            $e->like('user.firstname', $e->literal('%' . $value['value'] . '%')),
            $e->like('user.lastname', $e->literal('%' . $value['value'] . '%'))
        ));

        return true;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->add('approve', $this->getRouterIdParameter() . '/approve')
            ->add('unapprove', $this->getRouterIdParameter() . '/unapprove')
            ->add('impersonate', $this->getRouterIdParameter() . '/impersonate');
    }

    protected function configureBatchActions(array $actions): array
    {
        if ($this->hasRoute('edit') && $this->hasAccess('edit')) {
            $actions['approve'] = [
                'label' => 'Approve',
                'ask_confirmation' => false,
                'translation_domain' => 'admin',
            ];
        }

        return $actions;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('isApproved')
            ->add('userName', CallbackFilter::class, [
                'callback' => [$this, 'userNameFilter'],
                'field_type' => TextType::class,
            ]);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('user.fullName', null, ['label' => 'User'])
            ->add('booker.company', null, ['label' => 'Booker'])
            ->add('requestDate', null, ['label' => 'Request Date'])
            ->add('isApproved', null, ['label' => 'Approved'])
            ->add('approveDate', null, ['label' => 'Approve Date'])
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'approve' => [
                        'template' => '@AwardWalletMain/Sonata/CRUD/AbShare/list_action_approve.html.twig',
                    ],
                    'unapprove' => [
                        'template' => '@AwardWalletMain/Sonata/CRUD/AbShare/list_action_unapprove.html.twig',
                    ],
                    'impersonate' => [
                        'template' => '@AwardWalletMain/Sonata/CRUD/AbShare/list_action_impersonate.html.twig',
                    ],
                ],
            ]);
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'abshare';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'abshare';
    }

    protected function getAccessMapping(): array
    {
        return [
            'approve' => 'EDIT',
            'unapprove' => 'EDIT',
            'impersonate' => 'EDIT',
        ];
    }

    protected function configureDefaultFilterValues(array &$filterValues): void
    {
        parent::configureDefaultFilterValues($filterValues);

        $filterValues['isApproved'] = [
            'type' => EqualOperatorType::TYPE_EQUAL,
            'value' => BooleanType::TYPE_NO,
        ];
    }
}
