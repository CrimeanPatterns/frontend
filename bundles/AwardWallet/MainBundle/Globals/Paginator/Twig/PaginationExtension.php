<?php

namespace AwardWallet\MainBundle\Globals\Paginator\Twig;

use Twig\Extension\AbstractExtension;

class PaginationExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('aw_pagination_render', [PaginationRuntime::class, 'render'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('aw_pagination_sortable', [PaginationRuntime::class, 'sortable'], ['is_safe' => ['html']]),
        ];
    }
}
