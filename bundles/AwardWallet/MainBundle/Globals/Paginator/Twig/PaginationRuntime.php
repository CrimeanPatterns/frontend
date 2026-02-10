<?php

namespace AwardWallet\MainBundle\Globals\Paginator\Twig;

use AwardWallet\MainBundle\Globals\Paginator\Processor;
use Twig\Extension\RuntimeExtensionInterface;

class PaginationRuntime implements RuntimeExtensionInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var \AwardWallet\MainBundle\Globals\Paginator\Processor
     */
    protected $processor;

    public function __construct(Processor $processor, \Twig_Environment $environment)
    {
        $this->processor = $processor;
        $this->environment = $environment;
    }

    /**
     * Renders the pagination template.
     *
     * @param string $template
     * @return string
     */
    public function render($pagination, $template = null, array $queryParams = [], array $viewParams = [])
    {
        return $this->environment->render(
            $template ?: $pagination->getTemplate(),
            $this->processor->render($pagination, $queryParams, $viewParams)
        );
    }

    /**
     * Create a sort url for the field named $title.
     *
     * @param string $title
     * @param string $key
     * @param array $options
     * @param array $params
     * @param string $template
     * @return string
     */
    public function sortable($pagination, $title, $key, $options = [], $params = [], $template = null)
    {
        return $this->environment->render(
            $template ?: $pagination->getSortableTemplate(),
            $this->processor->sortable($pagination, $title, $key, $options, $params)
        );
    }

    /**
     * Create a filter url for the field named $title.
     *
     * @param array $options
     * @param array $params
     * @param string $template
     * @return string
     */
    public function filter($pagination, array $fields, $options = [], $params = [], $template = null)
    {
        return $this->environment->render(
            $template ?: $pagination->getFiltrationTemplate(),
            $this->processor->filter($pagination, $fields, $options, $params)
        );
    }
}
