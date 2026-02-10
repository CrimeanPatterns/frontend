<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * @author Jarek Kostrz <jkostrz@gmail.com>
 */
class FilterConfirmationNumber extends FunctionNode
{
    public $subject;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->subject = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "REPLACE(" . $this->subject->dispatch($sqlWalker) . ", '-', '')";
    }
}
