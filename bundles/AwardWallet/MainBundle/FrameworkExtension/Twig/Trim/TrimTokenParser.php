<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\Trim;

use Twig\Token;

class TrimTokenParser extends \Twig_TokenParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideTrimEnd'], true);
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TrimNode($body, $lineno, $this->getTag());
    }

    public function decideTrimEnd(\Twig_Token $token)
    {
        return $token->test('endtrim');
    }

    public function getTag()
    {
        return 'trim';
    }
}
