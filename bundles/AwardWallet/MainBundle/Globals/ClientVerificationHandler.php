<?php

namespace AwardWallet\MainBundle\Globals;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ClientVerificationHandler
{
    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getClientCheck()
    {
        $firstNumber = rand(1, 100);
        $secondNumber = rand(1, 100);
        $expression = $firstNumber . '+' . $secondNumber;
        $result = $firstNumber + $secondNumber;

        $clientCheck = [
            'varName' => 'Dn698tCQ',
            'jsExpression' => $expression,
            'result' => $result,
        ];

        $this->session->set('client_check', $clientCheck);

        return $clientCheck;
    }
}
