<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @NoDI
 */
class FormRenderer
{
    private Environment $twig;

    private array $data;

    public function __construct(Environment $twig, $data = [])
    {
        $this->twig = $twig;
        $this->data = $data;
    }

    /**
     * @param bool $loadCustomEntryPoint "page-manager/{schema}.form"
     */
    public function render(FormInterface $form, bool $loadCustomEntryPoint = false): Response
    {
        $data = [
            'form' => $form->createView(),
        ];

        if ($loadCustomEntryPoint) {
            $data['customEntryPoint'] = sprintf('page-manager/%s.form', $this->data['schema']);
        }

        return new Response($this->twig->render('@Module/EnhancedAdmin/Template/edit.html.twig', array_merge(
            $this->data,
            $data
        )));
    }
}
