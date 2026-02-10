<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Admin\Filter\NumberRangeFilter;
use AwardWallet\MainBundle\Entity\RetailProvider;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RetailProviderAdmin extends AbstractAdmin
{
    /**
     * @var string
     */
    public $asyncRequestId;

    protected $accessMapping = [
        'import' => 'EDIT',
    ];

    protected $uniqid = 'retail-provider';
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * RetailProviderAdmin constructor.
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $id = $this->getRouterIdParameter();

        $collection
            ->add('import', "{$id}/import");
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('name')
            ->add('initialCode', null, ['disabled' => true, 'required' => false])
            ->add('code', null, ['required' => true])
            ->add('homepage')
            ->add('keywords', null, ['required' => false])
            ->add('regions', null, ['required' => false])
            ->add('state', ChoiceType::class, [
                'choices' => array_flip($this->getChoices()),
            ])
            ->add('comment', TextareaType::class, [
                'allow_tags' => true,
                'allow_quotes' => true,
                'allow_urls' => true,
                'required' => false,
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('retailProviderId', NumberRangeFilter::class)
            ->add('name')
            ->add('code')
            ->add('initialCode')
            ->add('homepage')
            ->add(
                'state',
                StringFilter::class,
                [
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => array_flip($this->getChoices()),
                    ],
                ]
            )
            ->add('keywords')
            ->add('regions')
            ->add('importedProviderId', NumberFilter::class);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('retailProviderId', null, ['label' => 'id'])
            ->add('name', null, [
                'editable' => true,
                'template' => '@AwardWalletMain/Sonata/CRUD/RetailProvider/list_field_google.html.twig',
            ])
            ->add('code', null, [
                'editable' => true,
            ])
            ->add('homepage', null, [
                'editable' => true,
                'template' => '@AwardWalletMain/Sonata/CRUD/RetailProvider/list_field_url_open.html.twig',
            ])
            ->add('keywords', null, ['editable' => true])
            ->add('regions', null, ['editable' => true])
            ->add('state', FieldDescriptionInterface::TYPE_CHOICE, [
                'choices' => $this->getChoices(),
                'editable' => true,
            ])
            ->add('detectedProviderId.shortname', null, [
                'label' => 'Detected',
                'template' => '@AwardWalletMain/Sonata/CRUD/RetailProvider/list_field_provider.html.twig',
            ])
            ->add('importedProviderId.shortname', null, [
                'label' => 'Imported as',
                'template' => '@AwardWalletMain/Sonata/CRUD/RetailProvider/list_field_provider.html.twig',
            ])
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'import' => [
                        'template' => '@AwardWalletMain/Sonata/CRUD/RetailProvider/list_action_import.html.twig',
                    ],
                    'edit' => [],
                ],
            ]);
    }

    protected function getChoices()
    {
        return [
            RetailProvider::STATE_IMPORTED => 'Imported',
            RetailProvider::STATE_INITIAL => 'Initial',
            RetailProvider::STATE_IGNORED => 'Ignored',
            RetailProvider::STATE_WIP => 'WIP',
            RetailProvider::STATE_REFERRAL_DETECTED => 'Referral Detected',
            RetailProvider::STATE_PROVIDER_FOUND => 'Provider detected',
        ];
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'retail_provider';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'retail-provider';
    }
}
