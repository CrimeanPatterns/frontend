<?php

namespace AwardWallet\MainBundle\Globals\Paginator;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Paginator
{
    public const HINT_COUNT = 'aw_paginator.count';

    protected $route;
    protected $request;
    protected $params = [];
    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param array $options
     * @return Pagination
     * @throws \LogicException
     */
    public function paginate(\Doctrine\ORM\QueryBuilder $target, $page = 1, $limit = 10, $options = [])
    {
        $options = array_merge($this->options, $options);

        // get items
        $itemsInfo = $this->getItems($target, $page, $limit, $options);

        // pagination
        $pagination = new Pagination($this->params);
        $pagination->setUsedRoute($this->route);
        $pagination->setTemplate($options['defaultPaginationTemplate']);
        $pagination->setSortableTemplate($options['defaultSortableTemplate']);
        $pagination->setFiltrationTemplate($options['defaultFiltrationTemplate']);
        $pagination->setPageRange($options['page_range']);

        $pagination->setCurrentPageNumber($itemsInfo['page']);
        $pagination->setItemNumberPerPage($limit);
        $pagination->setTotalItemCount($itemsInfo['count']);
        $pagination->setPaginatorOptions($options);
        $pagination->setItems($itemsInfo['items']);

        return $pagination;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $this->request = $request = $event->getRequest();

        $this->route = $request->attributes->get('_route');
        $this->params = array_merge($request->query->all(), $request->attributes->all());

        foreach ($this->params as $key => $param) {
            if (substr($key, 0, 1) == '_') {
                unset($this->params[$key]);
            }
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'defaultPaginationTemplate' => '@AwardWalletMain/Utility/Pagination/pagination.html.twig',
            'defaultSortableTemplate' => '@AwardWalletMain/Utility/Pagination/sortable_link.html.twig',
            'defaultFiltrationTemplate' => '@AwardWalletMain/Utility/Pagination/filtration.html.twig',
            'page_range' => 11,
            'sort_field_name' => 'sort',
            'sort_direction_name' => 'direction',
            'filter_field_name' => 'filterField',
            'filter_value_name' => 'filterValue',
            'page_name' => 'page',
            'sort_fields' => [],
            'default_sort' => null,
        ]);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param array $options
     */
    protected function getItems(\Doctrine\ORM\QueryBuilder $target, $page, $limit, $options)
    {
        if ($page < 1) {
            $page = 1;
        }
        $limit = intval(abs($limit));

        if (!$limit) {
            throw new \LogicException("Invalid item per page number, must be a positive number");
        }

        $this->addSort($target);
        $resultArray = [
            'count' => 0,
            'items' => [],
            'page' => &$page,
        ];
        $q = $target->getQuery();

        $offset = abs($page - 1) * $limit;

        if (!is_int($offset)) {
            $offset = PHP_INT_MAX;
        }

        $limitSubQuery = QueryHelper::cloneQuery($q);
        $limitSubQuery
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;
        QueryHelper::addCustomTreeWalker($limitSubQuery, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker');
        $treeWalker = 'AwardWallet\MainBundle\Globals\Paginator\MysqlCountTreeWalker';
        $limitSubQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, $treeWalker);

        $ids = array_map('current', $limitSubQuery->getScalarResult());

        $countResult = $conn = $limitSubQuery->getEntityManager()->getConnection()->query('SELECT FOUND_ROWS()')->fetchColumn(0);
        $resultArray['count'] = intval($countResult);

        $pages = intval(ceil($resultArray['count'] / $limit));

        if ($page > $pages) {
            $page = $pages;
        }

        // process items
        if ($resultArray['count']) {
            // create where-in query
            $whereInQuery = QueryHelper::cloneQuery($q);
            QueryHelper::addCustomTreeWalker($whereInQuery, 'Doctrine\ORM\Tools\Pagination\WhereInWalker');
            $whereInQuery
                ->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids))
                ->setFirstResult(null)
                ->setMaxResults(null)
            ;

            if (version_compare(\Doctrine\ORM\Version::VERSION, '2.3.0', '>=') && sizeof($ids)) {
                $whereInQuery->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $ids);
            } else {
                $type = $limitSubQuery->getHint(LimitSubqueryWalker::IDENTIFIER_TYPE);
                $idAlias = WhereInWalker::PAGINATOR_ID_ALIAS;

                foreach ($ids as $i => $id) {
                    $whereInQuery->setParameter(
                        $idAlias . '_' . ++$i,
                        $id,
                        $type->getName()
                    );
                }
            }
            $result = $whereInQuery->execute();
        } else {
            $result = []; // count is 0
        }
        $resultArray['items'] = $result;

        return $resultArray;
    }

    protected function addSort(\Doctrine\ORM\QueryBuilder $target)
    {
        if (!isset($this->request)) {
            return;
        }
        $request = $this->request->query;
        $sort = $request->get($this->options['sort_field_name']);

        if (empty($sort) && !empty($this->options['default_sort'])) {
            $sort = $this->options['default_sort'];
        }

        if ($sort && is_string($sort)) {
            $dir = $request->get($this->options['sort_direction_name']);
            $dir = (isset($dir) && strtolower($dir) === 'asc') ? 'asc' : 'desc';

            if (isset($this->options['sort_fields'][$sort])) {
                $fields = $this->options['sort_fields'][$sort];

                if (!is_array($fields)) {
                    $fields = [$fields];
                }
                $first = true;

                foreach ($fields as $field) {
                    if ($first) {
                        $target->orderBy($field, $dir);
                        $first = false;
                    } else {
                        $target->addOrderBy($field, $dir);
                    }
                }
            }
        }
    }
}
