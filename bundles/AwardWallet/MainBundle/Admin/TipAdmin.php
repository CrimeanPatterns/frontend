<?php

namespace AwardWallet\MainBundle\Admin;

use AwardWallet\MainBundle\Form\Type\HtmleditorType;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use AwardWallet\MainBundle\Service\Tip\TipHandlerCollection;
use Doctrine\ORM\EntityManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\RouterInterface;

class TipAdmin extends AbstractAdmin
{
    protected $uniqid = 'tip';

    /** @var RouterInterface */
    protected $router;

    /** @var DataProviderLoader */
    protected $dataProviderLoader;

    /** @var EntityManager */
    protected $em;

    /** @var TipHandlerCollection */
    protected $tipHandler;

    public function __construct(
        RouterInterface $router,
        DataProviderLoader $loader,
        EntityManager $em,
        TipHandlerCollection $tipHandler
    ) {
        $this->router = $router;
        $this->dataProviderLoader = $loader;
        $this->em = $em;
        $this->tipHandler = $tipHandler;
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $replacements = $this->dataProviderLoader->getFreeDataProviders();
        $this->parameters['tplReplacement'] = $replacements[0]['replacements'] ?? 'No replacement';

        $formMapper
            ->tab('Tip options')
                ->with('Basic', [
                    'class' => 'col-md-6',
                    'box_class' => 'box box-solid box-primary',
                ])
                    ->add('title', TextareaType::class, [
                        /** @Ignore */
                        'label' => 'Title',
                        'required' => false,
                        'allow_quotes' => true,
                        'allow_urls' => true,
                        'help' => 'Заголовок в подсказке',
                    ])
                    ->add('reshowinterval', NumberType::class, [
                        /** @Ignore */
                        'label' => 'Reshow interval',
                        'required' => true,
                        'help' => 'Через сколько дней повторно показать подсказку (при выполнении остальных условий)',
                    ])
                    ->add('route', ChoiceType::class, [
                        /** @Ignore */
                        'label' => 'Route name',
                        'required' => false,
                        'choices' => array_flip($this->getRouteList()),
                    ])
                    ->add('element', ChoiceType::class, [
                        /** @Ignore */
                        'label' => 'Element',
                        'required' => false,
                        'choices' => array_combine($this->tipHandler->getElements(), $this->tipHandler->getElements()),
                    ])
                    ->add('enabled', CheckboxType::class, [
                        /** @Ignore */
                        'label' => 'Enable',
                        'required' => false,
                    ])
                ->end()
                ->with('Body', [
                    'class' => 'col-md-12',
                    'box_class' => 'box box-solid box-default',
                ])
                    ->add('description', HtmleditorType::class, [
                        /** @Ignore */
                        'label' => false,
                        'required' => false,
                        'ui_color' => null,
                        'allow_tags' => true,
                        'allow_quotes' => true,
                        'allow_urls' => true,
                        'filebrowser_browse_url' => ['url' => '/elfinder/default'],
                        'on' => '{
                                            pluginsLoaded: function(e) {
                                                e.editor.dataProcessor.dataFilter.addRules({
                                                    elements: {
                                                        $: function(element) {
                                                            if (element.attributes.id) {
                                                                delete element.attributes.id;
                                                            }
                                                            return element;
                                                        }
                                                    }
                                                });
                                            }
                                        }',
                        'help' => 'Описание подсказки',
                    ])
                ->end()
            ->end();
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $this->routeList = $this->getRouteList();
        $listMapper
            ->add('tipId')
            ->addIdentifier('title')
            ->add('route')
            ->add('element')
            ->add('reshowinterval')
            ->add('enabled', 'boolean', ['editable' => true])
            ->add(ListMapper::NAME_ACTIONS, ListMapper::TYPE_ACTIONS, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                    'link' => [
                        'template' => '@AwardWalletMain/Sonata/CRUD/Tip/list_action_tip_testing.html.twig',
                    ],
                ],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('title')
            ->add('route')
            ->add('element');
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'tip';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'tip';
    }

    private function getRouteList()
    {
        /** @var $collection \Symfony\Component\Routing\RouteCollection */
        $collection = $this->router->getRouteCollection();
        $allRoutes = $collection->all();

        $routes = [];

        /** @var $params \Symfony\Component\Routing\Route */
        foreach ($allRoutes as $route => $params) {
            $defaults = $params->getDefaults();

            if (isset($defaults['_controller'])) {
                $controllerAction = explode(':', $defaults['_controller']);
                $controller = $controllerAction[0];

                if (!isset($routes[$controller])) {
                    $routes[$controller] = [];
                }

                $routes[$controller][$route] = $params->getPath();
            }
        }

        $first = [];
        $normalize = [];

        foreach ($routes as $controller => $items) {
            if ('AwardWallet\MainBundle\Controller' === substr($controller, 0, 33)
                && false === strpos($controller, 'Manager\\')
                && false === strpos($controller, 'Sonata\\')
            ) {
                foreach ($items as $k => $item) {
                    $item = ltrim($items[$k], '/');

                    if (empty($item)) {
                        continue;
                    }

                    if (\in_array($k, ['aw_timeline', 'aw_account_list'])) {
                        $first[$k] = $item . ' ';

                        continue;
                    }

                    $normalize[$k] = $item;
                }
            }
        }
        $normalize = array_merge($first, $normalize);

        return $normalize;
    }
}
