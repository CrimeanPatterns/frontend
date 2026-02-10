<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserDelete extends AbstractTemplate
{
    /**
     * @var int
     */
    public $accounts = 0;

    /**
     * @var int
     */
    public $trips = 0;

    /**
     * @var int
     */
    public $pays = 0;

    /**
     * @var float
     */
    public $lifetimeContribution = 0;

    /**
     * @var string
     */
    public $reason;

    public static function getDescription(): string
    {
        return 'User account deleted (to AwadWallet)';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->accounts = rand(0, 30);
        $template->trips = rand(0, 30);
        $template->pays = rand(0, 10);
        $template->lifetimeContribution = rand(0, 30);
        $template->reason = 'just because';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
