<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\Trim;

use Twig\Compiler;

class TrimNode extends \Twig_Node
{
    public function __construct(\Twig_Node $body, $lineno, $tag = 'trim')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("echo trim(ob_get_clean());\n")
        ;
    }
}
