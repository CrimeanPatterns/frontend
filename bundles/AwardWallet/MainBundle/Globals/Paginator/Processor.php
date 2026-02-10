<?php

namespace AwardWallet\MainBundle\Globals\Paginator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Processor
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    /**
     * Generates pagination template data.
     *
     * @return array
     */
    public function render(Pagination $pagination, array $queryParams = [], array $viewParams = [])
    {
        $data = $pagination->getPaginationData();

        $data['route'] = $pagination->getRoute();
        $data['query'] = array_merge($pagination->getParams(), $queryParams);

        return array_merge(
            $pagination->getPaginatorOptions(), // options given to paginator when paginated
            $pagination->getCustomParameters(), // all custom parameters for view
            $viewParams, // additional custom parameters for view
            $data // merging base route parameters last, to avoid broke of integrity
        );
    }

    /**
     * Create a sort url for the field named $title.
     *
     * @param string $title
     * @param string $key
     * @param array $options
     * @param array $params
     * @return array
     */
    public function sortable($pagination, $title, $key, $options = [], $params = [])
    {
        $options = array_merge([
            'absolute' => false,
            'translationParameters' => [],
            'translationDomain' => null,
            'translationCount' => null,
        ], $options);

        $params = array_merge($pagination->getParams(), $params);

        $direction = isset($options[$pagination->getPaginatorOption('sort_direction_name')])
            ? $options[$pagination->getPaginatorOption('sort_direction_name')]
            : ($options['defaultDirection'] ?? 'asc')
        ;

        $sorted = $pagination->isSorted($key, $params);

        if ($sorted) {
            $direction = $params[$pagination->getPaginatorOption('sort_direction_name')];
            $direction = (strtolower($direction) == 'asc') ? 'desc' : 'asc';
            $class = $direction == 'asc' ? 'desc' : 'asc';

            if (isset($options['class'])) {
                $options['class'] .= ' ' . $class;
            } else {
                $options['class'] = $class;
            }
        } else {
            $options['class'] = 'sortable';
        }

        if (is_array($title) && array_key_exists($direction, $title)) {
            $title = $title[$direction];
        }

        $params = array_merge(
            $params,
            [
                $pagination->getPaginatorOption('sort_field_name') => $key,
                $pagination->getPaginatorOption('sort_direction_name') => $direction,
                $pagination->getPaginatorOption('page_name') => 1, // reset to 1 on sort
            ]
        );

        $options['href'] = $this->urlGenerator->generate($pagination->getRoute(), $params, $options['absolute'] ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);

        if (null !== $options['translationDomain']) {
            if (null !== $options['translationCount']) {
                $title = $this->translator->trans($title, array_merge($options['translationParameters'], ['%count%' => $options['translationCount']]), $options['translationDomain']);
            } else {
                $title = $this->translator->trans($title, $options['translationParameters'], $options['translationDomain']);
            }
        }

        if (!isset($options['title'])) {
            $options['title'] = $title;
        }

        unset($options['absolute'], $options['translationDomain'], $options['translationParameters']);

        return array_merge(
            $pagination->getPaginatorOptions(),
            $pagination->getCustomParameters(),
            compact('options', 'title', 'direction', 'sorted', 'key')
        );
    }

    /**
     * Create a filter url for the field named $title.
     *
     * @param array $options
     * @param array $params
     * @return array
     */
    public function filter($pagination, array $fields, $options = [], $params = [])
    {
        $options = array_merge([
            'absolute' => false,
            'translationParameters' => [],
            'translationDomain' => null,
            'button' => 'Filter',
        ], $options);

        $params = array_merge($pagination->getParams(), $params);
        $params[$pagination->getPaginatorOption('page_name')] = 1; // reset to 1 on filter

        $filterFieldName = $pagination->getPaginatorOption('filter_field_name');
        $filterValueName = $pagination->getPaginatorOption('filter_value_name');

        $selectedField = $params[$filterFieldName] ?? null;
        $selectedValue = $params[$filterValueName] ?? null;

        $action = $this->urlGenerator->generate($pagination->getRoute(), $params, $options['absolute'] ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);

        foreach ($fields as $field => $title) {
            $fields[$field] = $this->translator->trans($title, $options['translationParameters'], $options['translationDomain']);
        }
        $options['button'] = $this->translator->trans($options['button'], $options['translationParameters'], $options['translationDomain']);

        unset($options['absolute'], $options['translationDomain'], $options['translationParameters']);

        return array_merge(
            $pagination->getPaginatorOptions(),
            $pagination->getCustomParameters(),
            compact('fields', 'action', 'filterFieldName', 'filterValueName', 'selectedField', 'selectedValue', 'options')
        );
    }
}
