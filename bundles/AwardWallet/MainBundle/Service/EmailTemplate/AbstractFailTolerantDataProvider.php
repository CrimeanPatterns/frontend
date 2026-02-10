<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractFailTolerantDataProvider extends AbstractDataProvider
{
    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        parent::__construct($container, $template);

        $this->options[Mailer::OPTION_SEND_ATTEMPTS] = 9;
        $this->options['fail_attempt'] = function ($e, $attempt) {
            sleep(2 ** $attempt);
        };

        if (isset($this->forceTransactional)) {
            $this->options[Mailer::OPTION_TRANSACTIONAL] = $this->forceTransactional;
        }
    }
}
