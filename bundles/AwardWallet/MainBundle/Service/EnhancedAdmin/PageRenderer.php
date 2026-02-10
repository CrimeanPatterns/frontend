<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @NoDI
 */
class PageRenderer
{
    private Environment $twig;

    private array $data;

    public function __construct(Environment $twig, $data = [])
    {
        $this->twig = $twig;
        $this->data = $data;
    }

    /**
     * @param bool $loadCustomEntryPoint "page-manager/{schema}.{action}"
     */
    public function render(array $data, bool $loadCustomEntryPoint = false): Response
    {
        if ($loadCustomEntryPoint) {
            $data['customEntryPoint'] = sprintf('page-manager/%s.%s', $this->data['schema'], $this->data['action']);
        }

        return new Response($this->twig->render('@Module/EnhancedAdmin/Template/page.html.twig', array_merge(
            $this->data,
            $data
        )));
    }
}
