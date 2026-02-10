<?php

namespace AwardWallet\WidgetBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\Entity\Tip;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\Translator\EntityTranslator;
use AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement;
use AwardWallet\MainBundle\Service\Tip\Definition\Generic;
use AwardWallet\MainBundle\Service\Tip\TipHandlerCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFunction;

class TipExtension extends TwigExtension
{
    /** @var AwTokenStorage */
    protected $tokenStorage;

    /** @var RequestStack */
    protected $requestStack;

    /** @var TipHandlerCollection */
    protected $tipHandler;

    /** @var EntityTranslator */
    protected $entityTranslator;

    /** @var Replacement */
    private $twigReplacement;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(
        TipHandlerCollection $tipHandler,
        AwTokenStorage $tokenStorage,
        EntityTranslator $entityTranslator,
        RequestStack $requestStack,
        Replacement $twigReplacement,
        EntityManager $entityManager
    ) {
        $this->tipHandler = $tipHandler;
        $this->entityTranslator = $entityTranslator;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->twigReplacement = $twigReplacement;
        $this->entityManager = $entityManager;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('tip', function ($elementName, $options = []) {
                /** @var Generic $definition */
                $definition = $this->tipHandler->findByElement($elementName);

                if (!empty($definition)) {
                    $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route', '');
                    /** @var Tip $tip */
                    $tip = $definition->findTip($routeName);

                    if (empty($tip) && 'aw_account_list_html5' === $routeName) {
                        $tip = $definition->findTip('aw_account_list');
                    }

                    if (empty($tip)) {
                        return '';
                    }

                    $needShow = $this->tokenStorage->getUser() ? $definition->show($this->tokenStorage->getUser(), $routeName) : false;

                    if (null === $needShow) {
                        return '';
                    }

                    $anywayShowElement = $this->requestStack->getMasterRequest()->query->get('tip');
                    $title = $this->entityTranslator->trans($tip, 'title', [], 'tip');
                    $description = $this->entityTranslator->trans($tip, 'description', [], 'tip');

                    if ('title.' . $tip->getId() === $title || 'description.' . $tip->getId() === $description) {
                        $title = $this->entityTranslator->trans($tip, 'title', [], 'tip', 'en');
                        $description = $this->entityTranslator->trans($tip, 'description', [], 'tip', 'en');
                    }
                    $description = \preg_replace_callback('#\{(.*?)\}#s', [$this, 'replaceQuoteInBrackets'], $description);
                    $content = $title . (empty($description) ? '' : '<div class="tip-description">' . $description . '</div>');
                    $content = rawurlencode($this->twigReplacement->render($content, []));

                    $attr = [];
                    $attr['tipid'] = $tip->getId();

                    if (!empty($options)) {
                        if (is_string($options)) {
                            $options = ['position' => $options];
                        }

                        foreach ($options as $key => $value) {
                            $attr[$key] = $value;
                        }
                    }
                    isset($attr['position']) ?: $attr['position'] = 'bottom-middle-aligned';

                    if ((!empty($anywayShowElement) && $definition->getElementId() === $anywayShowElement) || true === $needShow) {
                        $attr['show'] = true;
                    }

                    if (isset($options['output']) && 'javascript' === $options['output']) {
                        if (null === $needShow || !method_exists($definition, 'getSelector')) {
                            return '';
                        }
                        unset($attr['output']);
                        $attr['intro'] = $content;

                        $push = ["selector: '" . $definition->getSelector() . "'"];
                        $push[] = "attr: {" . $this->jsKeys($attr) . "}";

                        if (method_exists($definition, 'jsInit')) {
                            $push[] = 'init: ' . $definition->jsInit();
                        }

                        return '<script>'
                            . "'object' === typeof window.tipJsList ? null : window.tipJsList = [];"
                            . "window.tipJsList.push({" . implode(',', $push) . "});"
                            . '</script>';
                    }

                    return ' data-intro="' . $content . '" ' . $this->attr($attr);
                }

                return '';
            }, ['is_safe' => ['html']]),
        ];
    }

    protected function attr($list)
    {
        $result = [];

        foreach ($list as $key => $value) {
            $result[] = 'data-' . $key . '="' . $value . '"';
        }

        return implode(' ', $result);
    }

    protected function jsKeys($list)
    {
        $result = '';

        foreach ($list as $key => $value) {
            $result .= $key . ':"' . $value . '",';
        }

        return rtrim($result, ',');
    }

    private function replaceQuoteInBrackets($matched): string
    {
        return '{' . str_replace(['&#39;', '&quot;'], ["'", '"'], $matched[1]) . '}';
    }
}
