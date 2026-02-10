<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class RevealPasswordTransformer implements DataTransformerInterface
{
    public $session;
    public $accountId;

    public function __construct(Session $session, $accountId)
    {
        $this->session = $session;
        $this->accountId = $accountId;
    }

    /**
     * @return array
     */
    public function transform($value)
    {
        $this->session->set('reveal_password', [$this->accountId => $value]);

        return [
            'password' => str_repeat('*', strlen($value)),
            'changed' => '0',
        ];
    }

    public function reverseTransform($value)
    {
        if ($value['changed'] == "1") {
            return $value['password'];
        } else {
            $pass = $this->session->get('reveal_password')[$this->accountId];

            return $pass;
        }
    }
}
